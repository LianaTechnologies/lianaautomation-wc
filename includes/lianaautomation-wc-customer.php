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

	$lianaautomation_wc_options = get_option( 'lianaautomation_wc_options' );

	// Gets liana_t tracking cookie if set.
	$liana_t = null;
	if ( isset( $_COOKIE['liana_t'] ) ) {
		$liana_t = sanitize_key( $_COOKIE['liana_t'] );
	}

	if ( $customer instanceof WC_Customer ) {
		$email = $customer->get_email();
	} else {
		$email = $customer['user_email'];
	}

	if ( empty( $email ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
			// phpcs:disable WordPress.PHP.DevelopmentFunctions
			error_log( 'ERROR: No email found on customer data. Bailing out.' );
			// phpcs:enable
		}
		return false;
	}

	if ( $customer instanceof WC_Customer ) {
		$customer_items = array(
			'id'    => $customer_id,
			'login' => $customer->get_username(),
			'role'  => $customer->get_role(),
			'email' => $email,
		);
	} else {
		$customer_items = array(
			'id'    => $customer_id,
			'login' => $customer['user_login'],
			'role'  => $customer['role'],
			'email' => $email,
		);
	}

	$user_meta_keys = array();
	if ( ! empty( $lianaautomation_wc_options['lianaautomation_user_meta_keys'] ) ) {
		$user_meta_keys = explode( ',', $lianaautomation_wc_options['lianaautomation_user_meta_keys'] );
	}

	foreach ( $user_meta_keys as $user_meta_key ) {
		// If the key is separated by comma and space, trim the spaces.
		$user_meta_key = trim( $user_meta_key );
		if ( 'locale' === $user_meta_key ) {
			$user_meta_value = get_user_locale( $customer_id );
		} else {
			$user_meta_value = get_user_meta( $customer_id, $user_meta_key, true );
		}

		// convert arrays and objects to string.
		if ( is_array( $user_meta_value ) || is_object( $user_meta_value ) ) {
			$user_meta_value = wp_json_encode( $user_meta_value );
		}
		// Automation doesnt like if key starts with underscore.
		$customer_items[ ltrim( $user_meta_key, '_' ) ] = $user_meta_value;
	}

	$automation_events   = array();
	$automation_events[] = array(
		'verb'  => 'customer',
		'items' => $customer_items,
	);

	$identity = array(
		'email' => $email,
	);

	// Check if logged in user and has a different email than the order.
	$different_email = false;
	$user = wp_get_current_user();
	if ( $user->exists() ) {
		$different_email = $email !== $user->user_email;
	}
	// Only add token if user email matches subscriber email.
	if ( ! empty( $liana_t ) && ! $different_email ) {
		$identity['token'] = $liana_t;
	}

	LianaAutomationAPI::send( $automation_events, $identity );
}

add_action( 'woocommerce_created_customer', 'lianaautomation_wc_customer', 10, 3 );
add_action( 'woocommerce_update_customer', 'lianaautomation_wc_customer', 10, 2 );
