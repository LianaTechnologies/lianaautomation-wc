<?php
/**
 * LianaAutomation for WooCommerce Added to cart handler
 *
 * PHP Version 8.1
 *
 * @package  LianaAutomation
 * @license  https://www.gnu.org/licenses/gpl-3.0-standalone.html GPL-3.0-or-later
 * @link     https://www.lianatech.com
 */

use LianaAutomation\LianaAutomationAPI;

/**
 * Define the lianaautomation_wc_addedtocart callback
 *
 * @param int    $product_id WooCommerce product id (of the added product).
 *
 * @return bool
 */
if ( ! function_exists( 'lianaautomation_wc_addedtocart' ) ) {
	/**
	 * Add to cart function, send Automation add to cart data.
	 *
	 * @param string $cart_item_key Cart item key.
	 * @param int    $product_id Product ID.
	 * @param int    $quantity Quantity.
	 * @param int    $variation_id Variation ID.
	 * @param array  $variation Variation.
	 * @param array  $cart_item_data Cart item data.
	 *
	 * @return bool
	 */
	function lianaautomation_wc_addedtocart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
		// Gets liana_t tracking cookie if set.
		$liana_t = null;
		if ( isset( $_COOKIE['liana_t'] ) ) {
			$liana_t = sanitize_key( $_COOKIE['liana_t'] );
		}

		$api_available = LianaAutomationAPI::get_options();
		if ( empty( $api_available ) ) {
			return false;
		}

		$automation_events = array();

		// Get user email from WooCommerce session.
		$user       = wp_get_current_user();
		$user_email = $user->user_email ?? null;

		$identity = array();
		if ( $user_email ) {
			$identity['email'] = $user_email;
		}
		if ( $liana_t ) {
			$identity['token'] = $liana_t;
		}

		// Let's not track users without any identity.
		if ( empty( $identity ) ) {
			return false;
		}

		$item = wc_get_product( $product_id );
		// Get product categories.
		$category_terms = get_the_terms( $product_id, 'product_cat' );
		$category_names = array();
		foreach ( $category_terms as $category_term ) {
			$category_names[] = $category_term->name;
		}
		$categories_string = implode( ',', $category_names );
		// Get first product parent categories (string).
		$category_parents_names = get_term_parents_list(
			$category_terms[0]->term_id,
			'product_cat',
			array(
				'separator' => ',',
				'link'      => false,
				'inclusive' => true,
			)
		);

		$automation_events[] = array(
			'verb'  => 'addtocart',
			'items' => array(
				'product'                  => $item->get_name(),
				'product_id'               => $product_id,
				'product_name'             => $item->get_name(),
				'total'                    => $item->get_price(),
				'taxclass'                 => $item->get_tax_class(),
				'taxstat'                  => $item->get_tax_status(),
				'product_categories'       => $categories_string,
				'product_category_parents' => $category_parents_names,
			),
		);

		/**
		* Retrieve Liana Options values (Array of All Options)
		*/
		LianaAutomationAPI::send( $automation_events, $identity );

		return true;
	}
}

// Add the action.
add_action( 'woocommerce_add_to_cart', 'lianaautomation_wc_addedtocart', 10, 6 );
