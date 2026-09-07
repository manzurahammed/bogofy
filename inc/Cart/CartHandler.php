<?php
/**
 * Cart handler for BOGO logic.
 *
 * @package Bogofy\Cart
 */

namespace Bogofy\Cart;

use Bogofy\Admin\Settings;
use Bogofy\Cart\Contracts\DiscountApplierInterface;
use Bogofy\Cart\Contracts\EligibilityCheckerInterface;
use Bogofy\Cart\Contracts\FreeItemManagerInterface;
use Bogofy\Models\RuleRepository;

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
	 * Cart item key for the BOGO baseline price.
	 *
	 * The line's effective (sale-aware) price before any BOGO promotion. All
	 * promotions are calculated from this so recalculations never compound.
	 *
	 * @var string
	 */
	const BOGO_BASELINE_KEY = '_bogo_baseline';

	/**
	 * Rule repository.
	 *
	 * @var RuleRepository
	 */
	private $repository;

	/**
	 * Eligibility checker.
	 *
	 * @var EligibilityCheckerInterface
	 */
	private $eligibility_checker;

	/**
	 * Discount applier.
	 *
	 * @var DiscountApplierInterface
	 */
	private $discount_applier;

	/**
	 * Free item manager.
	 *
	 * @var FreeItemManagerInterface
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
	 *
	 * @param RuleRepository              $repository          Rule repository.
	 * @param EligibilityCheckerInterface $eligibility_checker Eligibility checker.
	 * @param DiscountApplierInterface    $discount_applier    Discount applier.
	 * @param FreeItemManagerInterface    $free_item_manager   Free item manager.
	 */
	public function __construct(
		RuleRepository $repository,
		EligibilityCheckerInterface $eligibility_checker,
		DiscountApplierInterface $discount_applier,
		FreeItemManagerInterface $free_item_manager
	) {
		$this->repository          = $repository;
		$this->eligibility_checker = $eligibility_checker;
		$this->discount_applier    = $discount_applier;
		$this->free_item_manager   = $free_item_manager;
	}

	/**
	 * Reconcile BOGO free items in the cart.
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

		$this->syncing = true;

		// Reconcile existing BOGO lines against the active rules first, so gifts
		// from rules that were deactivated, deleted, expired, or all removed are
		// dropped even when there are no active rules left to process.
		$active_rule_ids = array_map(
			static function ( $rule ) {
				return (int) $rule->id;
			},
			$rules
		);
		$this->free_item_manager->remove_items_for_inactive_rules( $cart, $active_rule_ids );

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

		$rules    = $this->repository->get_active_rules();
		$rule_map = array();
		foreach ( $rules as $rule ) {
			$rule_map[ (int) $rule->id ] = $rule;
		}

		// 1. Reset every line to its BOGO baseline (its effective, sale-aware price
		// before any promotion) so repeated totals recalculations never compound.
		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			if ( empty( $cart_item['data'] ) ) {
				continue;
			}

			if ( ! isset( $cart->cart_contents[ $cart_item_key ][ self::BOGO_BASELINE_KEY ] ) ) {
				$cart->cart_contents[ $cart_item_key ][ self::BOGO_BASELINE_KEY ] = (float) $cart_item['data']->get_price();
			}

			$cart_item['data']->set_price( (float) $cart->cart_contents[ $cart_item_key ][ self::BOGO_BASELINE_KEY ] );
			unset( $cart->cart_contents[ $cart_item_key ][ self::BOGO_DISCOUNT_KEY ] );
		}

		// 2. Price the free gift lines from their baseline.
		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			if ( ! self::is_bogo_item( $cart_item ) || ! isset( $cart_item[ self::BOGO_RULE_KEY ] ) ) {
				continue;
			}

			$rule_id = (int) $cart_item[ self::BOGO_RULE_KEY ];
			if ( isset( $rule_map[ $rule_id ] ) ) {
				$this->free_item_manager->apply_free_price( $cart, $cart_item_key, $rule_map[ $rule_id ] );
			}
		}

		// 3. "Buy X Get X Discounted" rules: highest priority wins per line, so
		// overlapping rules never overwrite each other. A single allocation pass
		// processes rules in priority order and skips already-claimed lines.
		$discounted_rules = array();
		foreach ( $rules as $rule ) {
			if ( \Bogofy\Models\Rule::TYPE_BUY_X_GET_X_DISCOUNTED === $rule->rule_type ) {
				$discounted_rules[] = $rule;
			}
		}

		usort(
			$discounted_rules,
			static function ( $a, $b ) {
				return (int) $a->priority <=> (int) $b->priority;
			}
		);

		$claimed = array();
		foreach ( $discounted_rules as $rule ) {
			$eligible_items = $this->eligibility_checker->get_eligible_items( $cart, $rule );

			if ( ! empty( $eligible_items ) ) {
				$claimed = $this->discount_applier->apply_discounted( $cart, $rule, $eligible_items, $claimed );
			}
		}
	}

	/**
	 * Handle add to cart event.
	 *
	 * @return void
	 */
	public function on_add_to_cart() {
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
