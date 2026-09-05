<?php
/**
 * Store API (block cart/checkout) data extension.
 *
 * @package Bogofy\Frontend
 */

namespace Bogofy\Frontend;

use Bogofy\Cart\CartHandler;

/**
 * Class StoreApi
 *
 * Exposes BOGO data to the block cart/checkout via the WooCommerce Store API so
 * the client-side JS can render the free-item price, gift accent and a
 * "Bogo savings" totals row.
 */
class StoreApi {

	/**
	 * Register the endpoint data extensions.
	 *
	 * Hooked to `woocommerce_blocks_loaded`.
	 *
	 * @return void
	 */
	public function register() {
		if ( ! function_exists( 'woocommerce_store_api_register_endpoint_data' ) ) {
			return;
		}

		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => 'cart-item',
				'namespace'       => 'bogofy',
				'data_callback'   => array( $this, 'cart_item_data' ),
				'schema_callback' => array( $this, 'cart_item_schema' ),
				'schema_type'     => ARRAY_A,
			)
		);

		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => 'cart',
				'namespace'       => 'bogofy',
				'data_callback'   => array( $this, 'cart_data' ),
				'schema_callback' => array( $this, 'cart_schema' ),
				'schema_type'     => ARRAY_A,
			)
		);
	}

	/**
	 * Per-item BOGO data for the block cart.
	 *
	 * @param array $cart_item Cart item.
	 *
	 * @return array
	 */
	public function cart_item_data( $cart_item ) {
		return array(
			'is_free' => (bool) CartHandler::is_bogo_item( $cart_item ),
		);
	}

	/**
	 * Schema for the per-item data.
	 *
	 * @return array
	 */
	public function cart_item_schema() {
		return array(
			'is_free' => array(
				'description' => __( 'Whether this line is a free BOGO gift.', 'bogofy' ),
				'type'        => 'boolean',
				'readonly'    => true,
			),
		);
	}

	/**
	 * Cart-level BOGO data (total savings).
	 *
	 * @return array
	 */
	public function cart_data() {
		$savings = $this->get_cart_savings();

		return array(
			'savings'      => $savings,
			'savings_html' => $savings > 0 ? wc_price( $savings ) : '',
		);
	}

	/**
	 * Schema for the cart-level data.
	 *
	 * @return array
	 */
	public function cart_schema() {
		return array(
			'savings'      => array(
				'description' => __( 'Total BOGO savings in the cart.', 'bogofy' ),
				'type'        => 'number',
				'readonly'    => true,
			),
			'savings_html' => array(
				'description' => __( 'Formatted total BOGO savings.', 'bogofy' ),
				'type'        => 'string',
				'readonly'    => true,
			),
		);
	}

	/**
	 * Total base-price savings across all BOGO lines in the cart.
	 *
	 * @return float
	 */
	private function get_cart_savings() {
		$cart = WC()->cart;

		if ( ! $cart instanceof \WC_Cart ) {
			return 0.0;
		}

		$savings = 0.0;
		foreach ( $cart->get_cart() as $cart_item ) {
			if ( empty( $cart_item[ CartHandler::BOGO_RULE_KEY ] ) || empty( $cart_item['data'] ) ) {
				continue;
			}

			$product = $cart_item['data'];
			$regular = (float) $product->get_regular_price();
			$current = (float) $product->get_price();

			if ( $regular > $current ) {
				$savings += ( $regular - $current ) * (int) $cart_item['quantity'];
			}
		}

		return $savings;
	}
}
