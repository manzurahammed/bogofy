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
	 * Cart item key for the gift product the shopper chose.
	 *
	 * Set on the gift line of rules that offer several gift products, so the
	 * choice survives the session and cart recalculation.
	 *
	 * @since 2.2.0
	 *
	 * @var string
	 */
	const BOGO_GIFT_CHOICE_KEY = '_bogo_gift_choice';

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

		// With the plugin off nothing prices the gifts, so they must leave the cart
		// rather than be charged at full price.
		$rules = Settings::is_enabled() ? $this->repository->get_active_rules() : array();

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

			if ( $this->is_rule_eligible( $cart, $rule, $eligible_items ) ) {
				$this->discount_applier->sync( $cart, $rule, $eligible_items );
			} else {
				// Remove free items for this rule if no longer eligible.
				$this->free_item_manager->remove_rule_items( $cart, $rule->id );
			}
		}

		$this->syncing = false;
	}

	/**
	 * Switch the gift a rule grants to another of its gift products.
	 *
	 * @since 2.2.0
	 *
	 * @param int $rule_id    Rule ID.
	 * @param int $product_id Chosen gift product or variation ID.
	 *
	 * @return bool Whether the chosen gift is now in the cart.
	 */
	public function switch_gift( $rule_id, $product_id ) {
		$cart = WC()->cart;
		$rule = $this->get_active_rule( $rule_id );

		if ( ! $rule || ! $cart instanceof \WC_Cart ) {
			return false;
		}

		// Adding the new gift fires the add-to-cart sync, which would restore the
		// old choice before the new one is stored.
		$this->syncing = true;
		$switched      = $this->discount_applier->switch_gift( $cart, $rule, $product_id );
		$this->syncing = false;

		// Re-cap the gift against what the cart earns right now.
		$this->sync_free_items();

		return $switched;
	}

	/**
	 * The gift choices on offer for the gift lines in a cart.
	 *
	 * @since 2.2.0
	 *
	 * @param \WC_Cart $cart Cart object.
	 *
	 * @return array[] One entry per rule: rule_id, rule_title, cart_item_key,
	 *                 selected (product ID) and options (id + name).
	 */
	public function get_gift_choices( $cart ) {
		if ( ! $cart instanceof \WC_Cart || ! Settings::is_enabled() ) {
			return array();
		}

		$choices = array();

		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			if ( ! self::is_bogo_item( $cart_item ) || empty( $cart_item[ self::BOGO_RULE_KEY ] ) ) {
				continue;
			}

			$rule_id = (int) $cart_item[ self::BOGO_RULE_KEY ];
			$rule    = isset( $choices[ $rule_id ] ) ? null : $this->get_active_rule( $rule_id );

			if ( ! $rule || ! $rule->offers_gift_choice() ) {
				continue;
			}

			// Offer only gifts that could replace this line right now, so a pick
			// never comes back as an error. The current gift always stays listed.
			$selected = self::gift_product_id( $cart_item );
			$options  = array();
			foreach ( $rule->gift_product_ids() as $gift_id ) {
				$product = wc_get_product( $gift_id );
				if ( $product && $gift_id !== $selected && $rule->has_exclusions() ) {
					$parent_id = $product->get_parent_id() ? (int) $product->get_parent_id() : $gift_id;
					if ( $this->eligibility_checker->is_product_excluded( $gift_id, $parent_id, $rule, $product ) ) {
						continue;
					}
				}

				if ( $product && ( $gift_id === $selected || $this->free_item_manager->can_supply( $cart, $gift_id, array( $cart_item_key ) ) ) ) {
					$options[] = array(
						'id'   => $gift_id,
						'name' => $product->get_name(),
					);
				}
			}

			if ( count( $options ) > 1 ) {
				$choices[ $rule_id ] = array(
					'rule_id'       => $rule_id,
					'rule_title'    => $rule->title,
					'cart_item_key' => $cart_item_key,
					'selected'      => $selected,
					'options'       => $options,
				);
			}
		}

		return array_values( $choices );
	}

	/**
	 * Find an active rule by ID.
	 *
	 * @param int $rule_id Rule ID.
	 *
	 * @return \Bogofy\Models\Rule|null
	 */
	private function get_active_rule( $rule_id ) {
		if ( ! Settings::is_enabled() ) {
			return null;
		}

		foreach ( $this->repository->get_active_rules() as $rule ) {
			if ( (int) $rule->id === (int) $rule_id ) {
				return $rule;
			}
		}

		return null;
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
			// Nothing is discounted now, so a claim from the session would credit
			// the rule with an order it didn't apply to.
			foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
				if ( ! self::is_bogo_item( $cart_item ) ) {
					unset( $cart->cart_contents[ $cart_item_key ][ self::BOGO_RULE_KEY ] );
				}
			}

			return;
		}

		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		$rules    = $this->repository->get_active_rules();
		$rule_map = array();
		$eligible = array();
		$syncing  = $this->syncing;

		// The eligibility filter can veto on state that changes without a gift
		// sync, such as an applied coupon, so a vetoed rule's gifts go here too.
		// The flag stops the removal hook from re-entering the sync.
		$this->syncing = true;
		foreach ( $rules as $rule ) {
			$eligible_items = $this->eligibility_checker->get_eligible_items( $cart, $rule );

			if ( $this->is_rule_eligible( $cart, $rule, $eligible_items ) ) {
				$rule_map[ (int) $rule->id ] = $rule;
				$eligible[ (int) $rule->id ] = $eligible_items;
			} else {
				$this->free_item_manager->remove_rule_items( $cart, (int) $rule->id );
			}
		}
		$this->syncing = $syncing;

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

			// Paid lines are re-claimed below; a stale claim would misattribute the order.
			if ( ! self::is_bogo_item( $cart_item ) ) {
				unset( $cart->cart_contents[ $cart_item_key ][ self::BOGO_RULE_KEY ] );
			}
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
		foreach ( $rule_map as $rule ) {
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
			$claimed = $this->discount_applier->apply_discounted( $cart, $rule, $eligible[ (int) $rule->id ], $claimed );
		}
	}

	/**
	 * Whether a rule applies to the cart, after developer overrides.
	 *
	 * @param \WC_Cart            $cart           Cart object.
	 * @param \Bogofy\Models\Rule $rule           Rule object.
	 * @param array               $eligible_items Eligible cart items.
	 *
	 * @return bool
	 */
	private function is_rule_eligible( $cart, $rule, $eligible_items ) {
		/**
		 * Filters whether a rule applies to the cart.
		 *
		 * Return false to veto a rule (its gifts are removed and it discounts
		 * nothing) or true to force it. A forced rule with no qualifying items
		 * earns no free units unless `bogofy_free_quantity` grants some.
		 *
		 * @since 2.2.0
		 *
		 * @param bool                $eligible Whether any cart item qualifies for the rule.
		 * @param \Bogofy\Models\Rule $rule     Rule object.
		 * @param \WC_Cart            $cart     Cart object.
		 */
		return (bool) apply_filters( 'bogofy_rule_is_eligible', ! empty( $eligible_items ), $rule, $cart );
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
	 * Reconcile free items after the classic cart form is submitted.
	 *
	 * WooCommerce recalculates totals only when this filter returns truthy.
	 *
	 * @param bool $cart_updated Whether cart was updated.
	 *
	 * @return bool
	 */
	public function on_cart_updated( $cart_updated ) {
		if ( $cart_updated ) {
			$this->sync_free_items();
		}

		return $cart_updated;
	}

	/**
	 * Drop per-request pricing state from a cart item restored from the session.
	 *
	 * The baseline must come from the product's current price; a stored one would
	 * keep charging the old price after the product price changes.
	 *
	 * @param array $cart_item Cart item restored from the session.
	 *
	 * @return array
	 */
	public function clean_session_item( $cart_item ) {
		unset( $cart_item[ self::BOGO_BASELINE_KEY ], $cart_item[ self::BOGO_DISCOUNT_KEY ] );

		return $cart_item;
	}

	/**
	 * Lock the quantity of free items in the block cart.
	 *
	 * @param bool        $editable  Whether the quantity is editable.
	 * @param \WC_Product $product   Product object.
	 * @param array       $cart_item Cart item, when the Store API provides one.
	 *
	 * @return bool
	 */
	public function filter_quantity_editable( $editable, $product = null, $cart_item = array() ) {
		return self::is_bogo_item( $cart_item ) ? false : $editable;
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
	 * The gift product a free line holds: the shopper's choice, else its product.
	 *
	 * @since 2.2.0
	 *
	 * @param array $cart_item Cart item data.
	 *
	 * @return int
	 */
	public static function gift_product_id( $cart_item ) {
		if ( ! empty( $cart_item[ self::BOGO_GIFT_CHOICE_KEY ] ) ) {
			return (int) $cart_item[ self::BOGO_GIFT_CHOICE_KEY ];
		}

		return (int) ( ! empty( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : ( $cart_item['product_id'] ?? 0 ) );
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
