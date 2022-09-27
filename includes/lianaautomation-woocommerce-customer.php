<?php
/**
 * LianaAutomation WooCommerce Customer handler
 *
 * PHP Version 7.4
 *
 * @package  LianaAutomation
 * @license  https://www.gnu.org/licenses/gpl-3.0-standalone.html GPL-3.0-or-later
 * @link     https://www.lianatech.com
 */

/**
 * Define the LianaAutomation_WooCommerce_orderstatus callback
 *
 * @param int    $customer_id WooCommerce customer id (of the new customer).
 * @param object $customer    WooCommerce customer object (of the new customer).
 *
 * @return bool
 */
function LianaAutomation_WooCommerce_customer( $customer_id, $customer ) {

	$email = $customer['user_email'];

	if ( empty( $email ) ) {
		error_log( 'ERROR: No email found on customer data. Bailing out.' );
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

	/**
	* Retrieve Liana Options values (Array of All Options)
	*/
	$lianaautomation_woocommerce_options
		= get_option( 'lianaautomation_woocommerce_options' );

	if ( empty( $lianaautomation_woocommerce_options ) ) {
		error_log( 'lianaautomation_woocommerce_options was empty' );
		return false;
	}

	// The user id, integer
	if ( empty( $lianaautomation_woocommerce_options['lianaautomation_user'] ) ) {
		error_log( 'lianaautomation_woocommerce_options lianaautomation_user empty' );
		return false;
	}
	$user = $lianaautomation_woocommerce_options['lianaautomation_user'];

	// Hexadecimal secret string
	if ( empty( $lianaautomation_woocommerce_options['lianaautomation_key'] ) ) {
		error_log( 'lianaautomation_woocommerce_options lianaautomation_key empty' );
		return false;
	}
	$secret = $lianaautomation_woocommerce_options['lianaautomation_key'];

	// The base url for our API installation
	if ( empty( $lianaautomation_woocommerce_options['lianaautomation_url'] ) ) {
		error_log( 'lianaautomation_woocommerce_options lianaautomation_url empty' );
		return false;
	}
	$url = $lianaautomation_woocommerce_options['lianaautomation_url'];

	// The realm of our API installation, all caps alphanumeric string
	if ( empty( $lianaautomation_woocommerce_options['lianaautomation_realm'] ) ) {
		error_log( 'lianaautomation_woocommerce_options lianaautomation_realm empty' );
		return false;
	}
	$realm = $lianaautomation_woocommerce_options['lianaautomation_realm'];

	// The channel ID of our automation
	if ( empty( $lianaautomation_woocommerce_options['lianaautomation_channel'] ) ) {
		error_log(
			'lianaautomation_woocommerce_options '
			. 'lianaautomation_channel empty'
		);
		return false;
	}
	$channel = $lianaautomation_woocommerce_options['lianaautomation_channel'];

	/**
	* General variables
	*/
	$basePath    = 'rest';             // Base path of the api end points
	$contentType = 'application/json'; // Content will be send as json
	$method      = 'POST';             // Method is always POST

	// Import Data
	$path = 'v1/import';

	$data = array(
		'channel'       => $channel,
		'no_duplicates' => false,
		'data'          => array(
			array(
				'identity' => array(
					'email' => $email,
				),
				'events'   => $automation_events,
			),
		),
	);

	// Encode our body content data
	$data = json_encode( $data );
	// Get the current datetime in ISO 8601
	$date = date( 'c' );
	// md5 hash our body content
	$contentMd5 = md5( $data );
	// Create our signature
	$signatureContent = implode(
		"\n",
		array(
			$method,
			$contentMd5,
			$contentType,
			$date,
			$data,
			"/{$basePath}/{$path}",
		),
	);
	$signature        = hash_hmac( 'sha256', $signatureContent, $secret );
	// Create the authorization header value
	$auth = "{$realm} {$user}:" . $signature;

	// Create our full stream context with all required headers
	$ctx = stream_context_create(
		array(
			'http' => array(
				'method'  => $method,
				'header'  => implode(
					"\r\n",
					array(
						"Authorization: {$auth}",
						"Date: {$date}",
						"Content-md5: {$contentMd5}",
						"Content-Type: {$contentType}",
					)
				),
				'content' => $data,
			),
		)
	);

	// Build full path, open a data stream, and decode the json response
	$fullPath = "{$url}/{$basePath}/{$path}";
	$fp       = fopen( $fullPath, 'rb', false, $ctx );
	if ( ! $fp ) {
		// API failed to connect
		return null;
	}
	$response = stream_get_contents( $fp );
	$response = json_decode( $response, true );

};

add_action(
	'woocommerce_created_customer',
	'LianaAutomation_WooCommerce_customer',
	10,
	3
);

