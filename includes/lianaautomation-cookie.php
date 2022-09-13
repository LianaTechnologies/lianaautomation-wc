<?php
/**
 * LianaAutomation cookie (avoids redeclaration by other LianaAutomation plugins)
 *
 * PHP Version 7.4
 *
 * @category Components
 * @package  WordPress
 * @author   Liana Technologies <websites@lianatech.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0-standalone.html GPL-3.0-or-later
 * @link     https://www.lianatech.com
 */
if (!function_exists('Liana_Automation_cookie')) {
    /**
     * Cookie Function
     *
     * Provides liana_t cookie functionality
     *
     * @return null
     */
    function Liana_Automation_cookie()
    {
        // Generates liana_t tracking cookie if not set
        if (isset($_COOKIE['liana_t'])) {
            $liana_t = $_COOKIE['liana_t'];
            // error_log("liana_t cookie found: ".$liana_t);
        } else {
            $liana_t = uniqid('', true);
            setcookie(
                'liana_t',
                $liana_t,
                time() + 315569260,
                COOKIEPATH,
                COOKIE_DOMAIN
            );
            // error_log("liana_t cookie set: ".$liana_t);
        }
    }

    add_action('wp_head', 'Liana_Automation_cookie', 1, 0);
}
