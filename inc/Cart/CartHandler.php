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
	 * Flag to prevent recursion while syncing free items.
	 *
	 * @var bool
	 */
	private $syncing = false;

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
	 * Reconcile BOGO free items in the cart.
	 *
	 * Adds, updates, or removes free item lines based on the currently eligible
	 * cart contents. This runs on cart-mutation hooks (add to cart, remove, quantity
	 * update) and when the cart is loaded from the session, so it never adds items
	 * during totals calculation.
	 *
	 * Accepts (and ignores) any arguments so it can be attached directly to hooks
	 * with differing signatures.
	 *
	 * @return void
	 */
	public function sync_free_items() {
		if ( ! Settings::is_enabled() ) {
			return;
		}

		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		if ( $this->syncing ) {
			return;
		}

		$cart = WC()->cart;

		if ( ! $cart instanceof \WC_Cart ) {
			return;
		}

		$rules = $this->repository->get_active_rules();

		if ( empty( $rules ) ) {
			return;
		}

		$this->syncing = true;

		foreach ( $rules as $rule ) {
			$eligible_items = $this->eligibility_checker->get_eligible_items( $cart, $rule );

			if ( ! empty( $eligible_items ) ) {
				$this->discount_applier->sync( $cart, $rule, $eligible_items );
			} else {
				// Remove free items for this rule if no longer eligible.
				$this->free_item_manager->remove_rule_items( $cart, $rule->id );
			}
		}

		$this->syncing = false;
	}

	/**
	 * Apply BOGO prices during totals calculation.
	 *
	 * Only sets prices on items already in the cart. Free item lines are priced to
	 * zero (or their configured discount) and "discounted" rule types reduce the
	 * price of the cheapest eligible items. No items are added or removed here.
	 *
	 * @param \WC_Cart $cart Cart object.
	 *
	 * @return void
	 */
	public function apply_bogo_prices( $cart ) {
		if ( ! Settings::is_enabled() ) {
			return;
		}

		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		$rules = $this->repository->get_active_rules();

		if ( empty( $rules ) ) {
			return;
		}

		// Index rules by ID for quick lookup when pricing free lines.
		$rule_map = array();
		foreach ( $rules as $rule ) {
			$rule_map[ (int) $rule->id ] = $rule;
		}

		// Price every free item line according to its owning rule.
		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			if ( ! self::is_bogo_item( $cart_item ) || ! isset( $cart_item[ self::BOGO_RULE_KEY ] ) ) {
				continue;
			}

			$rule_id = (int) $cart_item[ self::BOGO_RULE_KEY ];

			if ( isset( $rule_map[ $rule_id ] ) ) {
				$this->free_item_manager->apply_free_price( $cart, $cart_item_key, $rule_map[ $rule_id ] );
			}
		}

		// Apply "Buy X Get X Discounted" rules to existing eligible lines.
		foreach ( $rules as $rule ) {
			if ( \BuyOneGetOne\Models\Rule::TYPE_BUY_X_GET_X_DISCOUNTED !== $rule->rule_type ) {
				continue;
			}

			$eligible_items = $this->eligibility_checker->get_eligible_items( $cart, $rule );

			if ( ! empty( $eligible_items ) ) {
				$this->discount_applier->apply_discounted( $cart, $rule, $eligible_items );
			}
		}
	}

	/**
	 * Handle add to cart event.
	 *
	 * @param string $cart_item_key  Cart item key.
	 * @param int    $product_id     Product ID.
	 * @param int    $quantity       Quantity.
	 * @param int    $variation_id   Variation ID.
	 * @param array  $variation      Variation data.
	 * @param array  $cart_item_data Cart item data.
	 *
	 * @return void
	 */
	public function on_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
		$this->sync_free_items();
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

		// Remove any free items whose triggering products are no longer present.
		$this->free_item_manager->cleanup_orphaned_items( $cart );

		// Re-evaluate rules against the remaining cart contents.
		$this->sync_free_items();
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
			$this->sync_free_items();
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
