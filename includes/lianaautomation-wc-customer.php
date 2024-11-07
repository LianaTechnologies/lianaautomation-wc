<?php
/**
 * LianaAutomation for WooCommerce Customer handler
 *
 * PHP Version 8.1
 *
 * @package  LianaAutomation
 * @license  https://www.gnu.org/licenses/gpl-3.0-standalone.html GPL-3.0-or-later
 * @link     https://www.lianatech.com
 */

use LianaAutomation\LianaAutomationAPI;

/**
 * Define the lianaautomation_wc_orderstatus callback
 *
 * @param int    $customer_id WooCommerce customer id (of the new customer).
 * @param object $customer    WooCommerce customer object (of the new customer).
 *
 * @return bool
 */
function lianaautomation_wc_customer( $customer_id, $customer ) {
	$options = LianaAutomationAPI::get_options();
	// API not available, bail out.
	if ( empty( $options ) ) {
		return false;
	}

	// Gets liana_t tracking cookie if set.
	$liana_t = null;
	if ( isset( $_COOKIE['liana_t'] ) ) {
		$liana_t = sanitize_key( $_COOKIE['liana_t'] );
	}

	$email = $customer['user_email'];

	if ( empty( $email ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
			// phpcs:disable WordPress.PHP.DevelopmentFunctions
			error_log( 'ERROR: No email found on customer data. Bailing out.' );
			// phpcs:enable
		}
		return false;
	}

	$automation_events   = array();
	$automation_events[] = array(
		'verb'  => 'customer',
		'items' => array(
			'id'    => $customer_id,
			'login' => $customer['user_login'],
			'role'  => $customer['role'],
			'email' => $customer['user_email'],
		),
	);

	$identity = array(
		'email' => $email,
	);
	if ( ! empty( $liana_t ) ) {
		$identity['token'] = $liana_t;
	}

	LianaAutomationAPI::send( $automation_events, $identity );
}

add_action(
	'woocommerce_created_customer',
	'lianaautomation_wc_customer',
	10,
	3
);
