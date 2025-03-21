<?php
/**
 * LianaAutomation for WooCommerce API handler
 *
 * PHP Version 8.1
 *
 * @package  LianaAutomation
 * @license  https://www.gnu.org/licenses/gpl-3.0-standalone.html GPL-3.0-or-later
 * @link     https://www.lianatech.com
 */

namespace LianaAutomation;

/**
 * LianaAutomation for WooCommerce API handler
 */
class LianaAutomationAPI {
	/**
	 * The options array.
	 *
	 * @var array
	 */
	private static $options = array();

	/**
	 * Get the Liana Automation options.
	 */
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

	/**
	 * Send the automation events to the API.
	 *
	 * @param array $automation_events The automation events to send.
	 * @param array $identity The identity of the user.
	 */
	public static function send( $automation_events, $identity ) {
		$options = self::get_options();
		if ( empty( $options ) ) {
			return false;
		}

		// Import Data.
		$action = 'import';
		$data = array(
			'channel'       => $options['channel'],
			'no_duplicates' => false,
			'data'          => array(
				array(
					'identity' => $identity,
					'events'   => $automation_events,
				),
			),
		);

		return self::request( $action, $data );
	}

	/**
	 * Get selected channel's GTM URL.
	 */
	public static function get_channel_gtm_url() {
		$gtm_cached = get_transient( 'lianaautomation_gtm_url' );
		if ( false !== $gtm_cached ) {
			return $gtm_cached;
		}
		$options = self::get_options();
		if ( empty( $options ) ) {
			return false;
		}
		$channel = intval( $options['channel'] ?? 0 );
		$action = 'channel/list';
		$channels = self::request( $action );
		if ( ! $channels ) {
			return false;
		}
		foreach ( $channels as $ch ) {
			if ( $ch['id'] === $channel ) {
				set_transient( 'lianaautomation_gtm_url', $ch['gtm_url'], DAY_IN_SECONDS );
				return $ch['gtm_url'];
			}
		}
		return false;
	}

	/**
	 * Send the automation events to the API.
	 *
	 * @param string $api_action The action to perform.
	 * @param array  $data The data to send.
	 */
	public static function request( $api_action, $data = null ) {
		$options = self::get_options();
		if ( empty( $options ) ) {
			return false;
		}

		$user    = $options['user'];
		$secret  = $options['secret'];
		$url     = $options['url'];
		$realm   = $options['realm'];

		/**
		* General variables
		*/
		$base_path    = 'rest';              // Base path of the api end points.
		$content_type = 'application/json';  // Content will be send as json.
		$method       = 'POST';              // Method is always POST.
		$path         = 'v1/' . $api_action; // The action we are performing.

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

		return $response;
	}
}