<?php
/**
 * LianaAutomation WooCommerce admin panel
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
 * LianaAutomation / WooCommerce options panel class
 *
 * @category Components
 * @package  WordPress
 * @author   Liana Technologies <websites@lianatech.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0-standalone.html GPL-3.0-or-later 
 * @link     https://www.lianatech.com
 */
class LianaAutomationWooCommerce
{
    private $_lianaautomation_woocommerce_options;

    /**
     * Constructor
     */
    public function __construct()
    {
        add_action(
            'admin_menu',
            array( $this, 'lianaAutomationWooCommerceAddPluginPage' )
        );

        add_action(
            'admin_init',
            array( $this, 'lianaAutomationWooCommercePageInit' )
        );
    }

    /**
     * Add an admin page
     * 
     * @return null
     */
    public function lianaAutomationWooCommerceAddPluginPage()
    {
        global $admin_page_hooks;

        //error_log(print_r($admin_page_hooks, true));

        // Only create the top level menu if it doesn't exist (via another plugin)
        if (!isset($admin_page_hooks['lianaautomation'])) {
            add_menu_page(
                'LianaAutomation', // page_title
                'LianaAutomation', // menu_title
                'manage_options', // capability
                'lianaautomation', // menu_slug
                array( $this, 'lianaAutomationWooCommerceCreateAdminPage' ),
                'dashicons-admin-settings', // icon_url
                65 // position
            );
        }
        add_submenu_page(
            'lianaautomation',
            'WooCommerce',
            'WooCommerce',
            'manage_options',
            'lianaautomationwoocommerce',
            array( $this, 'lianaAutomationWooCommerceCreateAdminPage' ),
        );

        // Remove the duplicate of the top level menu item from the sub menu
        // to make things pretty.
        remove_submenu_page('lianaautomation', 'lianaautomation');

    }

    /**
     * Construct an admin page
     *
     * @return null
     */
    public function lianaAutomationWooCommerceCreateAdminPage()
    {
        $this->lianaautomation_woocommerce_options
            = get_option('lianaautomation_woocommerce_options'); ?>
        <div class="wrap">
            <h2>LianaAutomation API Options for WooCommerce Order Tracking</h2>
            <?php settings_errors(); ?>
            <form method="post" action="options.php">
                <?php
                settings_fields('lianaautomation_woocommerce_option_group');
                do_settings_sections('lianaautomation_woocommerce_admin');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Init a WooCommerce admin page
     *
     * @return null
     */
    public function lianaAutomationWooCommercePageInit() 
    {
        register_setting(
            'lianaautomation_woocommerce_option_group', // option_group
            'lianaautomation_woocommerce_options', // option_name
            array( $this, 'lianaAutomationWooCommerceSanitize' ) // sanitize_callback
        );

        add_settings_section(
            'lianaautomation_woocommerce_section', // id
            '', // empty section title text
            array( $this, 'lianaAutomationWooCommerceSectionInfo' ), // callback
            'lianaautomation_woocommerce_admin' // page
        );

        add_settings_field(
            'lianaautomation_woocommerce_url', // id
            'Automation API URL', // title
            array( $this, 'lianaAutomationWooCommerceURLCallback' ), // callback
            'lianaautomation_woocommerce_admin', // page
            'lianaautomation_woocommerce_section' // section
        );

        add_settings_field(
            'lianaautomation_woocommerce_realm', // id
            'Automation Realm', // title
            array( $this, 'lianaAutomationWooCommerceRealmCallback' ), // callback
            'lianaautomation_woocommerce_admin', // page
            'lianaautomation_woocommerce_section' // section
        );

        add_settings_field(
            'lianaautomation_woocommerce_user', // id
            'Automation User', // title
            array( $this, 'lianaAutomationWooCommerceUserCallback' ), // callback
            'lianaautomation_woocommerce_admin', // page
            'lianaautomation_woocommerce_section' // section
        );

        add_settings_field(
            'lianaautomation_woocommerce_key', // id
            'Automation Secret Key', // title
            array( $this, 'lianaAutomationWooCommerceKeyCallback' ), // callback
            'lianaautomation_woocommerce_admin', // page
            'lianaautomation_woocommerce_section' // section
        );

        add_settings_field(
            'lianaautomation_woocommerce_channel', // id
            'Automation Channel ID', // title
            array( $this, 'lianaAutomationWooCommerceChannelCallback' ), // callback
            'lianaautomation_woocommerce_admin', // page
            'lianaautomation_woocommerce_section' // section
        );

        // Status check

        add_settings_field(
            'lianaautomation_woocommerce_status_check', // id
            'LianaAutomation Connection Check', // title
            array(
                $this,
                'lianaAutomationWooCommerceConnectionCheckCallback'
            ), // callback
            'lianaautomation_woocommerce_admin', // page
            'lianaautomation_woocommerce_section' // section
        );

    }

    /** 
     * Basic input sanitization function
     * 
     * @param string $input String to be sanitized.
     * 
     * @return null
     */
    public function lianaAutomationWooCommerceSanitize($input)
    {
        $sanitary_values = array();

        if (isset($input['lianaautomation_url'])) {
            $sanitary_values['lianaautomation_url']
                = sanitize_text_field($input['lianaautomation_url']);
        }
        if (isset($input['lianaautomation_realm'])) {
            $sanitary_values['lianaautomation_realm']
                = sanitize_text_field($input['lianaautomation_realm']);
        }
        if (isset($input['lianaautomation_user'])) {
            $sanitary_values['lianaautomation_user']
                = sanitize_text_field($input['lianaautomation_user']);
        }
        if (isset($input['lianaautomation_key'])) {
            $sanitary_values['lianaautomation_key']
                = sanitize_text_field($input['lianaautomation_key']);
        }
        if (isset($input['lianaautomation_channel'])) {
            $sanitary_values['lianaautomation_channel']
                = sanitize_text_field($input['lianaautomation_channel']);
        }
        return $sanitary_values;
    }

    /** 
     * Empty section info
     * 
     * @return null
     */
    public function lianaAutomationWooCommerceSectionInfo()
    {
        // Intentionally empty section here.
        // Could be used to generate info text.
    }

    /** 
     * Automation URL
     * 
     * @return null
     */
    public function lianaAutomationWooCommerceURLCallback()
    {
        printf(
            '<input class="regular-text" type="text" '
            .'name="lianaautomation_woocommerce_options[lianaautomation_url]" '
            .'id="lianaautomation_url" value="%s">',
            isset($this->lianaautomation_woocommerce_options['lianaautomation_url'])
                ? esc_attr(
                    $this->lianaautomation_woocommerce_options['lianaautomation_url']
                )
                : ''
        );
    }

    /** 
     * Automation Realm
     * 
     * @return null
     */
    public function lianaAutomationWooCommerceRealmCallback()
    {
        printf(
            '<input class="regular-text" type="text" '
            .'name="lianaautomation_woocommerce_options[lianaautomation_realm]" '
            .'id="lianaautomation_realm" value="%s">',
            isset(
                $this->lianaautomation_woocommerce_options['lianaautomation_realm']
            )
            ? esc_attr(
                $this->lianaautomation_woocommerce_options['lianaautomation_realm']
            )
            : ''
        );
    }
    /** 
     * Automation User
     * 
     * @return null
     */
    public function lianaAutomationWooCommerceUserCallback()
    {
        printf(
            '<input class="regular-text" type="text" '
            .'name="lianaautomation_woocommerce_options[lianaautomation_user]" '
            .'id="lianaautomation_user" value="%s">',
            isset(
                $this->lianaautomation_woocommerce_options['lianaautomation_user']
            )
            ? esc_attr(
                $this->lianaautomation_woocommerce_options['lianaautomation_user']
            )
            : ''
        );
    }

    /** 
     * Automation Key
     * 
     * @return null
     */
    public function lianaAutomationWooCommerceKeyCallback()
    {
        printf(
            '<input class="regular-text" type="text" '
            .'name="lianaautomation_woocommerce_options[lianaautomation_key]" '
            .'id="lianaautomation_key" value="%s">',
            isset($this->lianaautomation_woocommerce_options['lianaautomation_key'])
                ? esc_attr(
                    $this->lianaautomation_woocommerce_options['lianaautomation_key']
                )
                : ''
        );
    }

    /** 
     * Automation Channel
     * 
     * @return null
     */
    public function lianaAutomationWooCommerceChannelCallback()
    {
        printf(
            '<input class="regular-text" type="text" '
            .'name="lianaautomation_woocommerce_options[lianaautomation_channel]" '
            .'id="lianaautomation_channel" value="%s">',
            isset(
                $this->lianaautomation_woocommerce_options['lianaautomation_channel']
            )
            ? esc_attr(
                $this->lianaautomation_woocommerce_options['lianaautomation_channel']
            )
            : ''
        );  
    }

    /**
     * LianaAutomation WooCommerce Status check
     *
     * @return null
     */
    public function lianaAutomationWooCommerceConnectionCheckCallback()
    {

        $return = '💥Fail';
        // phpcs:ignore Generic.Files.LineLength.TooLong
        if (empty($this->lianaautomation_woocommerce_options['lianaautomation_user'])) {
            echo $return;
            return null;
        }
        $user
            = $this->lianaautomation_woocommerce_options['lianaautomation_user'];

        // phpcs:ignore Generic.Files.LineLength.TooLong
        if (empty($this->lianaautomation_woocommerce_options['lianaautomation_key'])) {
            echo $return;
            return null;
        }
        $secret
            = $this->lianaautomation_woocommerce_options['lianaautomation_key'];

        // phpcs:ignore Generic.Files.LineLength.TooLong
        if (empty($this->lianaautomation_woocommerce_options['lianaautomation_realm'])) {
            echo $return;
            return null;
        }
        $realm
            = $this->lianaautomation_woocommerce_options['lianaautomation_realm'];

        // phpcs:ignore Generic.Files.LineLength.TooLong
        if (empty($this->lianaautomation_woocommerce_options['lianaautomation_url'])) {
            echo $return;
            return null;
        }
        $url
            = $this->lianaautomation_woocommerce_options['lianaautomation_url'];


        // phpcs:ignore Generic.Files.LineLength.TooLong
        if (empty($this->lianaautomation_woocommerce_options['lianaautomation_channel'])) {
            echo $return;
            return null;
        }
        $channel
            = $this->lianaautomation_woocommerce_options['lianaautomation_channel'];

        /**
        * General variables
        */
        $basePath    = 'rest';             // Base path of the api end points
        $contentType = 'application/json'; // Content will be send as json
        $method      = 'POST';             // Method is always POST

        // Import Data
        $path = 'v1/pingpong';
        $data = array(
            "ping" => "pong"
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
            echo $return;
            return null;
        }

        $response = stream_get_contents($fp);
        $response = json_decode($response, true);

        if (!empty($response)) {
            // error_log(print_r($response, true));
            if (!empty($response['pong'])) {
                $return = '💚 OK';
            }
        }

        echo $return;
    }

}
if (is_admin()) {
    $lianaAutomationWooCommerce = new LianaAutomationWooCommerce();
}
