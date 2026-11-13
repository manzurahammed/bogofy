<?php
/**
 * Store API (block cart/checkout) data extension.
 *
 * @package Bogofy\Frontend
 */

namespace Bogofy\Frontend;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Bogofy\Cart\CartHandler;

/**
 * Class StoreApi
 */
class StoreApi {

	/**
	 * Cart handler.
	 *
	 * @var CartHandler|null
	 */
	private $cart_handler;

	/**
	 * Constructor.
	 *
	 * @param CartHandler|null $cart_handler Cart handler, for the gift picker.
	 */
	public function __construct( ?CartHandler $cart_handler = null ) {
		$this->cart_handler = $cart_handler;
	}

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

		// The Cart block switches the gift through `extensionCartUpdate`, which the
		// Store API protects with its own nonce.
		if ( function_exists( 'woocommerce_store_api_register_update_callback' ) ) {
			woocommerce_store_api_register_update_callback(
				array(
					'namespace' => 'bogofy',
					'callback'  => array( $this, 'switch_gift' ),
				)
			);
		}
	}

	/**
	 * Switch the gift a rule grants, from the Cart block.
	 *
	 * @since 2.2.0
	 *
	 * @param array $data Request data: rule_id and product_id.
	 *
	 * @return void
	 *
	 * @throws RouteException When the gift can't be selected.
	 */
	public function switch_gift( $data ) {
		$rule_id    = isset( $data['rule_id'] ) ? absint( $data['rule_id'] ) : 0;
		$product_id = isset( $data['product_id'] ) ? absint( $data['product_id'] ) : 0;

		if ( $this->cart_handler && $this->cart_handler->switch_gift( $rule_id, $product_id ) ) {
			return;
		}

		if ( class_exists( RouteException::class ) ) {
			throw new RouteException( 'bogofy_invalid_gift', esc_html__( 'That gift could not be selected.', 'bogofy' ), 400 );
		}
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
			'savings_minor'               => (int) round( $savings * ( 10 ** $decimals ) ),
			'currency_code'               => get_woocommerce_currency(),
			'currency_minor_unit'         => $decimals,
			'currency_prefix'             => $prefix,
			'currency_suffix'             => $suffix,
			'currency_decimal_separator'  => wc_get_price_decimal_separator(),
			'currency_thousand_separator' => wc_get_price_thousand_separator(),
			'gift_choices'                => $this->cart_handler ? $this->cart_handler->get_gift_choices( WC()->cart ) : array(),
		);
	}

	/**
	 * Schema for the cart-level data.
	 *
	 * @return array
	 */
	public function cart_schema() {
		return array(
			'savings_minor'               => array(
				'description' => __( 'Total BOGO savings, in the currency minor unit.', 'bogofy' ),
				'type'        => 'integer',
				'readonly'    => true,
			),
			'currency_code'               => array(
				'description' => __( 'ISO currency code.', 'bogofy' ),
				'type'        => 'string',
				'readonly'    => true,
			),
			'currency_minor_unit'         => array(
				'description' => __( 'Number of decimals in the currency.', 'bogofy' ),
				'type'        => 'integer',
				'readonly'    => true,
			),
			'currency_prefix'             => array(
				'description' => __( 'Text placed before the amount.', 'bogofy' ),
				'type'        => 'string',
				'readonly'    => true,
			),
			'currency_suffix'             => array(
				'description' => __( 'Text placed after the amount.', 'bogofy' ),
				'type'        => 'string',
				'readonly'    => true,
			),
			'currency_decimal_separator'  => array(
				'description' => __( 'Decimal separator.', 'bogofy' ),
				'type'        => 'string',
				'readonly'    => true,
			),
			'currency_thousand_separator' => array(
				'description' => __( 'Thousand separator.', 'bogofy' ),
				'type'        => 'string',
				'readonly'    => true,
			),
			'gift_choices'                => array(
				'description' => __( 'Gift products the shopper can choose between, per rule.', 'bogofy' ),
				'type'        => 'array',
				'readonly'    => true,
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'rule_id'       => array( 'type' => 'integer' ),
						'rule_title'    => array( 'type' => 'string' ),
						'cart_item_key' => array( 'type' => 'string' ),
						'selected'      => array( 'type' => 'integer' ),
						'options'       => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'id'   => array( 'type' => 'integer' ),
									'name' => array( 'type' => 'string' ),
								),
							),
						),
					),
				),
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

			// Save against the BOGO baseline (the customer's effective price before
			// the promotion), so an existing sale price isn't double-counted.
			$baseline = isset( $cart_item[ CartHandler::BOGO_BASELINE_KEY ] )
				? (float) $cart_item[ CartHandler::BOGO_BASELINE_KEY ]
				: (float) $cart_item['data']->get_regular_price();
			$current  = (float) $cart_item['data']->get_price();

			if ( $baseline > $current ) {
				$savings += ( $baseline - $current ) * (int) $cart_item['quantity'];
			}
		}

		return $savings;
	}
}
