<?php
/**
 * Cart display modifications.
 *
 * @package BuyOneGetOne\Frontend
 */

namespace BuyOneGetOne\Frontend;

use BuyOneGetOne\Admin\Settings;
use BuyOneGetOne\Cart\CartHandler;

/**
 * Class CartDisplay
 *
 * Handles cart display modifications for BOGO items.
 */
class CartDisplay {

	/**
	 * Add BOGO label to cart item data.
	 *
	 * @param array $item_data Cart item data.
	 * @param array $cart_item Cart item.
	 *
	 * @return array
	 */
	public function add_bogo_label( $item_data, $cart_item ) {
		if ( CartHandler::is_bogo_item( $cart_item ) ) {
			$label = Settings::get( 'free_item_label', __( 'FREE (Bogofy Deal)', 'bogofy' ) );

			$item_data[] = array(
				'key'   => __( 'Offer', 'bogofy' ),
				'value' => $label,
			);
		}

		return $item_data;
	}

	/**
	 * Modify price display for free items.
	 *
	 * @param string $price_html    Price HTML.
	 * @param array  $cart_item     Cart item.
	 * @param string $cart_item_key Cart item key.
	 *
	 * @return string
	 */
	public function modify_free_item_price_display( $price_html, $cart_item, $cart_item_key ) {
		if ( ! CartHandler::is_bogo_item( $cart_item ) ) {
			return $price_html;
		}

		$product        = $cart_item['data'];
		$original_price = $product->get_regular_price();
		$current_price  = $product->get_price();

		// If free (price is 0).
		if ( 0 === (int) $current_price ) {
			return sprintf(
				'<del>%s</del> <ins class="bogo-free-price">%s</ins>',
				wc_price( $original_price ),
				esc_html__( 'FREE', 'bogofy' )
			);
		}

		// If discounted.
		if ( $current_price < $original_price ) {
			return sprintf(
				'<del>%s</del> <ins>%s</ins>',
				wc_price( $original_price ),
				wc_price( $current_price )
			);
		}

		return $price_html;
	}
}
