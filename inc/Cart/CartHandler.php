<?php
/**
 * Cart handler for BOGO logic.
 *
 * @package BuyOneGetOne\Cart
 */

namespace BuyOneGetOne\Cart;

use BuyOneGetOne\Admin\Settings;
use BuyOneGetOne\Models\RuleRepository;

/**
 * Class CartHandler
 *
 * Handles BOGO cart operations.
 */
class CartHandler {

	/**
	 * Cart item key for BOGO items.
	 *
	 * @var string
	 */
	const BOGO_ITEM_KEY = '_bogo_free_item';

	/**
	 * Cart item key for rule ID.
	 *
	 * @var string
	 */
	const BOGO_RULE_KEY = '_bogo_rule_id';

	/**
	 * Cart item key for discount amount.
	 *
	 * @var string
	 */
	const BOGO_DISCOUNT_KEY = '_bogo_discount';

	/**
	 * Rule repository.
	 *
	 * @var RuleRepository
	 */
	private $repository;

	/**
	 * Eligibility checker.
	 *
	 * @var EligibilityChecker
	 */
	private $eligibility_checker;

	/**
	 * Discount applier.
	 *
	 * @var DiscountApplier
	 */
	private $discount_applier;

	/**
	 * Free item manager.
	 *
	 * @var FreeItemManager
	 */
	private $free_item_manager;

	/**
	 * Flag to prevent recursion.
	 *
	 * @var bool
	 */
	private $processing = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->repository          = new RuleRepository();
		$this->eligibility_checker = new EligibilityChecker();
		$this->discount_applier    = new DiscountApplier();
		$this->free_item_manager   = new FreeItemManager();
	}

	/**
	 * Apply BOGO rules to cart.
	 *
	 * @param \WC_Cart $cart Cart object.
	 *
	 * @return void
	 */
	public function apply_bogo_rules( $cart ) {
		if ( $this->processing ) {
			return;
		}

		if ( ! Settings::is_enabled() ) {
			return;
		}

		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) {
			return;
		}

		$this->processing = true;

		// Get active rules.
		$rules = $this->repository->get_active_rules();

		if ( empty( $rules ) ) {
			$this->processing = false;
			return;
		}

		// Check eligibility and apply rules.
		foreach ( $rules as $rule ) {
			$eligible_items = $this->eligibility_checker->get_eligible_items( $cart, $rule );

			if ( ! empty( $eligible_items ) ) {
				$this->discount_applier->apply( $cart, $rule, $eligible_items );
			} else {
				// Remove free items for this rule if no longer eligible.
				$this->free_item_manager->remove_rule_items( $cart, $rule->id );
			}
		}

		$this->processing = false;
	}

	/**
	 * Handle add to cart event.
	 *
	 * @param string $cart_item_key Cart item key.
	 * @param int    $product_id    Product ID.
	 * @param int    $quantity      Quantity.
	 * @param int    $variation_id  Variation ID.
	 * @param array  $variation     Variation data.
	 * @param array  $cart_item_data Cart item data.
	 *
	 * @return void
	 */
	public function on_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
		if ( ! Settings::is_enabled() ) {
			return;
		}

		// Recalculate totals to trigger rule application.
		WC()->cart->calculate_totals();
	}

	/**
	 * Handle cart item removed event.
	 *
	 * @param string   $cart_item_key Removed item key.
	 * @param \WC_Cart $cart          Cart object.
	 *
	 * @return void
	 */
	public function on_cart_item_removed( $cart_item_key, $cart ) {
		if ( ! Settings::is_enabled() ) {
			return;
		}

		// Check if removed item was a "buy" item that triggered a free item.
		$this->free_item_manager->cleanup_orphaned_items( $cart );

		// Recalculate to re-evaluate rules.
		$cart->calculate_totals();
	}

	/**
	 * Handle cart updated event.
	 *
	 * @param bool $cart_updated Whether cart was updated.
	 *
	 * @return void
	 */
	public function on_cart_updated( $cart_updated ) {
		if ( ! Settings::is_enabled() ) {
			return;
		}

		if ( $cart_updated ) {
			WC()->cart->calculate_totals();
		}
	}

	/**
	 * Filter cart item quantity to prevent modification of free items.
	 *
	 * @param string $product_quantity Quantity HTML.
	 * @param string $cart_item_key    Cart item key.
	 * @param array  $cart_item        Cart item data.
	 *
	 * @return string
	 */
	public function filter_cart_item_quantity( $product_quantity, $cart_item_key, $cart_item ) {
		if ( isset( $cart_item[ self::BOGO_ITEM_KEY ] ) && $cart_item[ self::BOGO_ITEM_KEY ] ) {
			// Return quantity as plain text (not editable).
			return sprintf(
				'<span class="bogo-free-qty">%d</span>',
				$cart_item['quantity']
			);
		}

		return $product_quantity;
	}

	/**
	 * Check if cart item is a BOGO free item.
	 *
	 * @param array $cart_item Cart item data.
	 *
	 * @return bool
	 */
	public static function is_bogo_item( $cart_item ) {
		return isset( $cart_item[ self::BOGO_ITEM_KEY ] ) && $cart_item[ self::BOGO_ITEM_KEY ];
	}

	/**
	 * Get BOGO discount amount for cart item.
	 *
	 * @param array $cart_item Cart item data.
	 *
	 * @return float
	 */
	public static function get_bogo_discount( $cart_item ) {
		return isset( $cart_item[ self::BOGO_DISCOUNT_KEY ] ) ? (float) $cart_item[ self::BOGO_DISCOUNT_KEY ] : 0;
	}
}
