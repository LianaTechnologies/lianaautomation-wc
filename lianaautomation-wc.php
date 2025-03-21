<?php
/**
 * Plugin Name:       LianaAutomation for WooCommerce
 * Description:       LianaAutomation for WooCommerce integrates the LianaAutomation marketing automation platform with a WordPress site with the WooCommerce plugin.
 * Version:           1.1.6
 * Requires at least: 5.2
 * Requires PHP:      8.1
 * Author:            Liana Technologies Oy
 * Author URI:        https://www.lianatech.com
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0-standalone.html
 * Text Domain:       lianaautomation-wc
 * Domain Path:       /languages
 *
 * PHP Version 8.1
 *
 * @package  LianaAutomation
 * @license  https://www.gnu.org/licenses/gpl-3.0-standalone.html GPL-3.0-or-later
 * @link     https://www.lianatech.com
 */

define( 'LIANAAUTOMATION_WC_VERSION', '1.1.6' );

// define plugin url
define( 'LIANAAUTOMATION_WC_URL', plugin_dir_url( __FILE__ ) );

function lianaautomation_wc_deprecate_mp() {
	// Merge deprecated permission meta key to user meta keys.
	$options    = get_option( 'lianaautomation_wc_options' );
	$deprecated = $options['lianaautomation_marketing_permission'] ?? '';
	$migrate_to = $options['lianaautomation_user_meta_keys'] ?? '';

	if ( $deprecated ) {
		$value   = explode( ',', $migrate_to );
		$value[] = $deprecated;
		$value   = implode( ',', $value );
		// Save the value from marketing_permission to user_meta_keys.
		$options['lianaautomation_user_meta_keys'] = $value;

		// Remove the deprecated key.
		unset( $options['lianaautomation_marketing_permission'] );

		// Update the options.
		update_option( 'lianaautomation_wc_options', $options );
	}
}
register_activation_hook( __FILE__, 'lianaautomation_wc_deprecate_mp' );

/**
 * Include WooCommerce Cart Actions handler code
 */
require_once __DIR__ . '/includes/lianaautomation-api.php';

/**
 * Include cookie handler code
 */
require_once __DIR__ . '/includes/lianaautomation-cookie.php';

/**
 * Include WooCommerce Cart Actions handler code
 */
require_once __DIR__ . '/includes/lianaautomation-wc-cartactions.php';

/**
 * Include WooCommerce Order Status handler code
 */
require_once __DIR__ . '/includes/lianaautomation-wc-orderstatus.php';

/**
 * Include WooCommerce Login handler code
 */
require_once __DIR__ . '/includes/lianaautomation-wc-login.php';

/**
 * Include WooCommerce Customer handler code
 */
require_once __DIR__ . '/includes/lianaautomation-wc-customer.php';

/**
 * Conditionally include admin panel code
 */
if ( is_admin() ) {
	require_once __DIR__ . '/admin/class-lianaautomation-wc.php';
}
