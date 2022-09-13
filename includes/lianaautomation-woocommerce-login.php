<?php
/**
 * LianaAutomation WooCommerce Login tracker
 *
 * PHP Version 7.4
 *
 * @category Components
 * @package  WordPress
 * @author   Liana Technologies <websites@lianatech.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0-standalone.html GPL-3.0-or-later
 * @link     https://www.lianatech.com
 */

/**
 * Login tracker function
 *
 * Send event when a WordPress user successfully logs in
 *
 * @param string  $user_login    User's login name
 * @param WP_User $loggingInUser User's WP_User object
 *
 * @return bool
 */
function Liana_Automation_WooCommerce_Login_send($user_login, $loggingInUser)
{
    // Gets liana_t tracking cookie if set
    $liana_t = null;
    if (isset($_COOKIE['liana_t'])) {
        $liana_t = $_COOKIE['liana_t'];
    } else {
        // liana_t cookie not found, unable to track. Bailing out.
        return false;
    }

    // Get current page URL
    global $wp;
    $current_url = home_url(add_query_arg(array(), $wp->request));

    /** 
    * Retrieve Liana Options values (Array of All Options)
    */
    $lianaautomation_woocommerce_options
        = get_option('lianaautomation_woocommerce_options');

    if (empty($lianaautomation_woocommerce_options)) {
        error_log("lianaautomation_woocommerce_options was empty");
        return false;
    }

    // The user id, integer
    if (empty($lianaautomation_woocommerce_options['lianaautomation_user'])) {
        error_log("lianaautomation_woocommerce_options lianaautomation_user empty");
        return false;
    }
    $user   = $lianaautomation_woocommerce_options['lianaautomation_user'];

    // Hexadecimal secret string
    if (empty($lianaautomation_woocommerce_options['lianaautomation_key'])) {
        error_log("lianaautomation_woocommerce_options lianaautomation_key empty");
        return false;
    }
    $secret = $lianaautomation_woocommerce_options['lianaautomation_key'];

    // The base url for our API installation
    if (empty($lianaautomation_woocommerce_options['lianaautomation_url'])) {
        error_log("lianaautomation_woocommerce_options lianaautomation_url empty");
        return false;
    }
    $url    = $lianaautomation_woocommerce_options['lianaautomation_url'];

    // The realm of our API installation, all caps alphanumeric string
    if (empty($lianaautomation_woocommerce_options['lianaautomation_realm'])) {
        error_log("lianaautomation_woocommerce_options lianaautomation_realm empty");
        return false;
    }
    $realm  = $lianaautomation_woocommerce_options['lianaautomation_realm'];

    // The channel ID of our automation
    if (empty($lianaautomation_woocommerce_options['lianaautomation_channel'])) {
        error_log(
            "lianaautomation_woocommerce_options "
            ."lianaautomation_channel empty"
        );
        return false;
    }
    $channel  = $lianaautomation_woocommerce_options['lianaautomation_channel'];

    if (empty($loggingInUser)) {
        error_log("LianaAutomation-WooCommerce ERROR: loggingInUser was empty");
        return false;
    }
    if (!in_array('customer', $loggingInUser->roles)) {
        // User was not WooCommerce default customer role, bailing out.
        return false;
    }
    $current_user_email = $loggingInUser->user_email;
    if (empty($current_user_email)) {
        error_log("LianaAutomation-WooCommerce ERROR: user_email was empty");
        return false;
    }

    /**
    * General variables
    */
    $basePath    = 'rest';             // Base path of the api end points
    $contentType = 'application/json'; // Content will be send as json
    $method      = 'POST';             // Method is always POST

    /**
     * Send a API request to LianaAutomation
     *
     * This function will add the required headers and 
     * calculates the signature for the authorization header
     *
     * @param string $path The path of the end point
     * @param array  $data The content body (data) of the request
     * 
     * @return mixed
     */
    // Import Data
    $path = 'v1/import';

    // Build the identity array
    $identity = array();
    if (!empty($current_user_email)) {
        $identity["email"] = $current_user_email;
    }
    if (!empty($liana_t)) {
        $identity["token"] = $liana_t;
    }    
    // Bail out if no identities found
    if (empty($identity)) {
        return false;
    }

    $data = array(
                "channel" => $channel,
                "no_duplicates" => false,
                "data" => [
                    [
                        "identity" => $identity,
                        "events" => [
                            [
                                "verb" => "login",
                                "items" => [
                                    "url" => $current_url,
                                    "username" => $user_login,
                                ],
                            ]
                        ]
                    ],
                ]
            );

    // Encode our body content data
    $data = json_encode($data);
    // Get the current datetime in ISO 8601
    $date = date('c');
    // md5 hash our body content
    $contentMd5 = md5($data);
    // Create our signature
    $signatureContent = implode(
        "\n",
        [
            $method,
            $contentMd5,
            $contentType,
            $date,
            $data,
            "/{$basePath}/{$path}"
        ],
    );
    $signature = hash_hmac('sha256', $signatureContent, $secret);
    // Create the authorization header value
    $auth = "{$realm} {$user}:" . $signature;

    // Create our full stream context with all required headers
    $ctx = stream_context_create(
        [
        'http' => [
            'method' => $method,
            'header' => implode(
                "\r\n",
                [
                "Authorization: {$auth}",
                "Date: {$date}",
                "Content-md5: {$contentMd5}",
                "Content-Type: {$contentType}"
                ]
            ),
            'content' => $data
        ]
        ]
    );

    // Build full path, open a data stream, and decode the json response
    $fullPath = "{$url}/{$basePath}/{$path}";
    $fp = fopen($fullPath, 'rb', false, $ctx);
    if (!$fp) {
        // API failed to connect
        return null;
    }
    $response = stream_get_contents($fp);
    $response = json_decode($response, true);

    //if (!empty($response)) {
    //    error_log(print_r($response, true));
    //}
}

add_action('wp_login', 'Liana_Automation_WooCommerce_Login_send', 10, 2);
