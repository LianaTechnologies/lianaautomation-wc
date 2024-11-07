<?php

namespace LianaAutomation;

class LianaAutomationAPI {
	/**
	 * The options array.
	 *
	 * @var array
	 */
	private static $options = array();

	public static function get_options() {
		if ( ! empty( self::$options ) ) {
			return self::$options;
		}
		/**
		* Retrieve Liana Options values (Array of All Options)
		*/
		$lianaautomation_wc_options = \get_option( 'lianaautomation_wc_options' );
		$options = array();

		if ( empty( $lianaautomation_wc_options ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
				// phpcs:disable WordPress.PHP.DevelopmentFunctions
				error_log( 'lianaautomation_wc_options was empty' );
				// phpcs:enable
			}
			return false;
		}

		// The user id, integer.
		if ( empty( $lianaautomation_wc_options['lianaautomation_user'] ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
				// phpcs:disable WordPress.PHP.DevelopmentFunctions
				error_log( 'lianaautomation_wc_options lianaautomation_user empty' );
				// phpcs:enable
			}
			return false;
		}
		$options['user'] = $lianaautomation_wc_options['lianaautomation_user'];

		// Hexadecimal secret string.
		if ( empty( $lianaautomation_wc_options['lianaautomation_key'] ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
				// phpcs:disable WordPress.PHP.DevelopmentFunctions
				error_log( 'lianaautomation_wc_options lianaautomation_key empty' );
				// phpcs:enable
			}
			return false;
		}
		$options['secret'] = $lianaautomation_wc_options['lianaautomation_key'];

		// The base url for our API installation.
		if ( empty( $lianaautomation_wc_options['lianaautomation_url'] ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
				// phpcs:disable WordPress.PHP.DevelopmentFunctions
				error_log( 'lianaautomation_wc_options lianaautomation_url empty' );
				// phpcs:enable
			}
			return false;
		}
		$options['url'] = $lianaautomation_wc_options['lianaautomation_url'];

		// The realm of our API installation, all caps alphanumeric string.
		if ( empty( $lianaautomation_wc_options['lianaautomation_realm'] ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
				// phpcs:disable WordPress.PHP.DevelopmentFunctions
				error_log( 'lianaautomation_wc_options lianaautomation_realm empty' );
				// phpcs:enable
			}
			return false;
		}
		$options['realm'] = $lianaautomation_wc_options['lianaautomation_realm'];

		// The channel ID of our automation.
		if ( empty( $lianaautomation_wc_options['lianaautomation_channel'] ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
				// phpcs:disable WordPress.PHP.DevelopmentFunctions
				error_log( 'lianaautomation_wc_options lianaautomation_channel empty' );
				// phpcs:enable
			}
			return false;
		}
		$options['channel'] = $lianaautomation_wc_options['lianaautomation_channel'];

		self::$options = $options;

		return self::$options;
	}

	public static function send( $automation_events, $identity ) {
		$options = self::get_options();
		if ( empty( $options ) ) {
			return false;
		}

		$user    = $options['user'];
		$secret  = $options['secret'];
		$url     = $options['url'];
		$realm   = $options['realm'];
		$channel = $options['channel'];

		/**
		* General variables
		*/
		$base_path    = 'rest';             // Base path of the api end points.
		$content_type = 'application/json'; // Content will be send as json.
		$method       = 'POST';             // Method is always POST.

		// Import Data.
		$path = 'v1/import';
		$data = array(
			'channel'       => $channel,
			'no_duplicates' => false,
			'data'          => array(
				array(
					'identity' => $identity,
					'events'   => $automation_events,
				),
			),
		);

		// Encode our body content data.
		$data = wp_json_encode( $data );
		// Get the current datetime in ISO 8601.
		$date = gmdate( 'c' );
		// md5 hash our body content.
		$content_md5 = md5( $data );
		// Create our signature.
		$signature_content = implode(
			"\n",
			array(
				$method,
				$content_md5,
				$content_type,
				$date,
				$data,
				"/{$base_path}/{$path}",
			),
		);

		$signature = hash_hmac( 'sha256', $signature_content, $secret );
		// Create the authorization header value.
		$auth = "{$realm} {$user}:" . $signature;

		// Create our full stream context with all required headers.
		$ctx = stream_context_create(
			array(
				'http' => array(
					'method'  => $method,
					'header'  => implode(
						"\r\n",
						array(
							"Authorization: {$auth}",
							"Date: {$date}",
							"Content-md5: {$content_md5}",
							"Content-Type: {$content_type}",
						)
					),
					'content' => $data,
				),
			)
		);

		// Build full path, open a data stream, and decode the json response.
		$full_path = "{$url}/{$base_path}/{$path}";

		$fp = fopen( $full_path, 'rb', false, $ctx );
		if ( ! $fp ) {
			// API failed to connect.
			return null;
		}
		$response = stream_get_contents( $fp );
		$response = json_decode( $response, true );
	}
}