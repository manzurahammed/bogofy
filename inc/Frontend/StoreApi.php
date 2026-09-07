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
 * Exposes minimal, raw BOGO data to the block cart/checkout via the WooCommerce
 * Store API: a per-item `is_free` flag (to accent the row) and the total savings
 * as raw minor units + currency shape (for an informational "You saved" row).
 * No pre-rendered HTML is sent. The gift note lines are added separately via the
 * woocommerce_get_item_data filter, which the Store API also surfaces.
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
	 * Cart-level BOGO data.
	 *
	 * Returns the total savings as raw minor units plus the store's currency
	 * shape, so the client can format it safely (no pre-rendered HTML).
	 *
	 * @return array
	 */
	public function cart_data() {
		$savings                 = $this->get_cart_savings();
		$decimals                = wc_get_price_decimals();
		list( $prefix, $suffix ) = $this->currency_affixes();

		return array(
			'savings_minor'       => (int) round( $savings * ( 10 ** $decimals ) ),
			'currency_code'       => get_woocommerce_currency(),
			'currency_minor_unit' => $decimals,
			'currency_prefix'     => $prefix,
			'currency_suffix'     => $suffix,
		);
	}

	/**
	 * Schema for the cart-level data.
	 *
	 * @return array
	 */
	public function cart_schema() {
		return array(
			'savings_minor'       => array(
				'description' => __( 'Total BOGO savings, in the currency minor unit.', 'bogofy' ),
				'type'        => 'integer',
				'readonly'    => true,
			),
			'currency_code'       => array(
				'description' => __( 'ISO currency code.', 'bogofy' ),
				'type'        => 'string',
				'readonly'    => true,
			),
			'currency_minor_unit' => array(
				'description' => __( 'Number of decimals in the currency.', 'bogofy' ),
				'type'        => 'integer',
				'readonly'    => true,
			),
			'currency_prefix'     => array(
				'description' => __( 'Text placed before the amount.', 'bogofy' ),
				'type'        => 'string',
				'readonly'    => true,
			),
			'currency_suffix'     => array(
				'description' => __( 'Text placed after the amount.', 'bogofy' ),
				'type'        => 'string',
				'readonly'    => true,
			),
		);
	}

	/**
	 * Currency prefix/suffix for the store's symbol position.
	 *
	 * @return array{0:string,1:string} [ prefix, suffix ].
	 */
	private function currency_affixes() {
		$symbol = html_entity_decode( get_woocommerce_currency_symbol() );
		$nbsp   = "\u{00A0}";

		switch ( get_option( 'woocommerce_currency_pos' ) ) {
			case 'right':
				return array( '', $symbol );
			case 'right_space':
				return array( '', $nbsp . $symbol );
			case 'left_space':
				return array( $symbol . $nbsp, '' );
			case 'left':
			default:
				return array( $symbol, '' );
		}
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
