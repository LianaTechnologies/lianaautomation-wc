<?php
/**
 * LianaAutomation cookie (avoids redeclaration by other LianaAutomation plugins)
 *
 * PHP Version 8.1
 *
 * @package  LianaAutomation
 * @license  https://www.gnu.org/licenses/gpl-3.0-standalone.html GPL-3.0-or-later
 * @link     https://www.lianatech.com
 */

use LianaAutomation\LianaAutomationAPI;

if ( ! function_exists( 'liana_automation_cookie' ) && ! function_exists( 'Liana_Automation_cookie' ) ) {
	/**
	 * Cookie Function
	 *
	 * Provides liana_t cookie functionality
	 *
	 * @return void
	 */
	function liana_automation_cookie(): void {
		$gtm_url = LianaAutomationAPI::get_channel_gtm_url();
		if ( ! $gtm_url ) {
			return;
		}

		$src = LIANAAUTOMATION_WC_URL . 'front/lianaautomation-cookie.js';

		wp_enqueue_script( 'lianaautomation-gtm', $gtm_url, array(), LIANAAUTOMATION_WC_VERSION, true );
		wp_enqueue_script( 'lianaautomation-cookie', $src, array(), LIANAAUTOMATION_WC_VERSION, true );
	}

	add_action( 'wp_head', 'liana_automation_cookie', 1, 0 );
}
