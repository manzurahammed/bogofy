<?php
/**
 * Discount applier for BOGO rules.
 *
 * @package Bogofy\Cart
 */

namespace Bogofy\Cart;

use Bogofy\Cart\Contracts\DiscountApplierInterface;
use Bogofy\Cart\Contracts\EligibilityCheckerInterface;
use Bogofy\Cart\Contracts\FreeItemManagerInterface;
use Bogofy\Models\Rule;

/**
 * Class DiscountApplier
 *
 * Applies BOGO discounts to cart.
 */
class DiscountApplier implements DiscountApplierInterface {

	/**
	 * Free item manager.
	 *
	 * @var FreeItemManagerInterface
	 */
	private $free_item_manager;

	/**
	 * Eligibility checker.
	 *
	 * @var EligibilityCheckerInterface
	 */
	private $eligibility_checker;

	/**
	 * Constructor.
	 *
	 * @param EligibilityCheckerInterface $eligibility_checker Eligibility checker.
	 * @param FreeItemManagerInterface    $free_item_manager   Free item manager.
	 */
	public function __construct(
		EligibilityCheckerInterface $eligibility_checker,
		FreeItemManagerInterface $free_item_manager
	) {
		$this->eligibility_checker = $eligibility_checker;
		$this->free_item_manager   = $free_item_manager;
	}

	/**
	 * Reconcile the free item lines for a rule (add / update / remove).
	 *
	 * @param \WC_Cart $cart           Cart object.
	 * @param Rule     $rule           Rule object.
	 * @param array    $eligible_items Eligible cart items.
	 *
	 * @return void
	 */
	public function sync( $cart, Rule $rule, $eligible_items ) {
		switch ( $rule->rule_type ) {
			case Rule::TYPE_BUY_X_GET_X:
				$this->sync_buy_x_get_x( $cart, $rule, $eligible_items );
				break;

			case Rule::TYPE_BUY_X_GET_Y:
			case Rule::TYPE_BUY_CAT_GET_FREE:
				$this->sync_free_product( $cart, $rule, $eligible_items );
				break;

			case Rule::TYPE_BUY_X_GET_X_DISCOUNTED:
				// No free line to add; discount is applied during totals calculation.
				break;
		}
	}

	/**
	 * Apply a "Buy X Get X Discounted" rule to existing eligible lines.
	 *
	 * @param \WC_Cart $cart           Cart object.
	 * @param Rule     $rule           Rule object.
	 * @param array    $eligible_items Eligible cart items.
	 * @param array    $claimed        Cart item keys already discounted by a
	 *                                 higher-priority rule.
	 *
	 * @return array The updated list of claimed cart item keys.
	 */
	public function apply_discounted( $cart, Rule $rule, $eligible_items, array $claimed = array() ) {
		$free_quantity = $this->eligibility_checker->calculate_free_quantity( $eligible_items, $rule );

		if ( $free_quantity <= 0 ) {
			return $claimed;
		}

		return $this->apply_discount_to_cheapest( $cart, $rule, $eligible_items, $free_quantity, $claimed );
	}

	/**
	 * Reconcile the free item(s) for a Buy X Get X Free rule.
	 *
	 * @param \WC_Cart $cart           Cart object.
	 * @param Rule     $rule           Rule object.
	 * @param array    $eligible_items Eligible cart items.
	 *
	 * @return void
	 */
	private function sync_buy_x_get_x( $cart, Rule $rule, $eligible_items ) {
		$free_quantity = $this->eligibility_checker->calculate_free_quantity( $eligible_items, $rule );

		if ( $free_quantity <= 0 ) {
			$this->free_item_manager->remove_rule_items( $cart, $rule->id );
			return;
		}

		// Add free copies of the cheapest eligible products the customer actually bought,
		// so they receive an additional item rather than the purchased one becoming free.
		$this->add_free_cheapest( $cart, $rule, $eligible_items, $free_quantity );
	}

	/**
	 * Add free copies of the cheapest eligible products.
	 *
	 * @param \WC_Cart $cart           Cart object.
	 * @param Rule     $rule           Rule object.
	 * @param array    $eligible_items Eligible cart items.
	 * @param int      $free_quantity  Total number of free units to grant.
	 *
	 * @return void
	 */
	private function add_free_cheapest( $cart, Rule $rule, $eligible_items, $free_quantity ) {
		// Sort items by price (cheapest first) so the cheapest products are given for free.
		uasort(
			$eligible_items,
			function ( $a, $b ) {
				return $a['data']->get_price() <=> $b['data']->get_price();
			}
		);

		$remaining  = (int) $free_quantity;
		$allocation = array();
		$variations = array();

		foreach ( $eligible_items as $cart_item ) {
			if ( $remaining <= 0 ) {
				break;
			}

			$product_id = (int) ( $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'] );
			$free_qty   = min( (int) $cart_item['quantity'], $remaining );

			$allocation[ $product_id ] = ( $allocation[ $product_id ] ?? 0 ) + $free_qty;
			$remaining                -= $free_qty;

			// "Any" variations only know their attributes from the purchased line.
			$variations[ $product_id ] = $variations[ $product_id ] ?? ( $cart_item['variation'] ?? array() );
		}

		// "Buy 1 Get 2" can grant more than was bought; the cheapest product takes the rest.
		if ( $remaining > 0 && ! empty( $allocation ) ) {
			$allocation[ array_key_first( $allocation ) ] += $remaining;
		}

		foreach ( $allocation as $product_id => $quantity ) {
			$this->free_item_manager->add_free_item( $cart, $product_id, $quantity, $rule, $variations[ $product_id ] );
		}

		$this->free_item_manager->remove_rule_items_except( $cart, $rule->id, array_keys( $allocation ) );
	}

	/**
	 * Reconcile the free item for a rule that grants a specific free product.
	 *
	 * @param \WC_Cart $cart           Cart object.
	 * @param Rule     $rule           Rule object.
	 * @param array    $eligible_items Eligible cart items.
	 *
	 * @return void
	 */
	private function sync_free_product( $cart, Rule $rule, $eligible_items ) {
		$free_quantity = $this->eligibility_checker->calculate_free_quantity( $eligible_items, $rule );

		if ( $free_quantity <= 0 || empty( $rule->free_product_ids ) ) {
			$this->free_item_manager->remove_rule_items( $cart, $rule->id );
			return;
		}

		// The shopper's chosen gift goes first; the rest, in the merchant's order,
		// stand in when the choice can't be supplied.
		$gift_ids = $rule->gift_product_ids();
		$chosen   = $this->chosen_gift( $cart, $rule );

		if ( in_array( $chosen, $gift_ids, true ) ) {
			$gift_ids = array_values( array_unique( array_merge( array( $chosen ), $gift_ids ) ) );
		}

		foreach ( $gift_ids as $gift_id ) {
			// An excluded gift is never granted.
			if ( $this->is_gift_excluded( $gift_id, $rule ) ) {
				continue;
			}

			$cart_item_key = $this->free_item_manager->add_free_item( $cart, $gift_id, $free_quantity, $rule );

			if ( $cart_item_key ) {
				$this->remember_choice( $cart, $cart_item_key, $rule, $gift_id );
				$this->free_item_manager->remove_rule_items_except( $cart, $rule->id, array( $gift_id ) );
				return;
			}
		}

		// No gift can be granted (all excluded or unavailable), so any copy goes.
		$this->free_item_manager->remove_rule_items( $cart, $rule->id );
	}

	/**
	 * Swap a rule's gift line for another of its gift products.
	 *
	 * The new gift takes the quantity the current gift line already holds, so a
	 * switch never grants more than the rule has earned.
	 *
	 * @since 2.2.0
	 *
	 * @param \WC_Cart $cart       Cart object.
	 * @param Rule     $rule       Rule object.
	 * @param int      $product_id Chosen gift product or variation ID.
	 *
	 * @return bool Whether the chosen gift is now in the cart.
	 */
	public function switch_gift( $cart, Rule $rule, $product_id ) {
		$product_id = (int) $product_id;

		if ( ! $rule->offers_gift_choice() || ! in_array( $product_id, $rule->gift_product_ids(), true ) || $this->is_gift_excluded( $product_id, $rule ) ) {
			return false;
		}

		$quantity  = 0;
		$replacing = array();
		foreach ( $cart->get_cart() as $key => $cart_item ) {
			if ( $this->is_rule_gift( $cart_item, $rule ) ) {
				$quantity   += (int) $cart_item['quantity'];
				$replacing[] = $key;
			}
		}

		// No gift line means the rule hasn't been earned yet.
		if ( $quantity < 1 ) {
			return false;
		}

		// The old gift's stock is freed by the swap, so it doesn't count against
		// the new one; the old line stays if the new gift can't be added.
		$cart_item_key = $this->free_item_manager->add_free_item( $cart, $product_id, $quantity, $rule, array(), $replacing );

		if ( ! $cart_item_key ) {
			return false;
		}

		$this->remember_choice( $cart, $cart_item_key, $rule, $product_id );
		$this->free_item_manager->remove_rule_items_except( $cart, $rule->id, array( $product_id ) );

		return true;
	}

	/**
	 * The gift the shopper currently holds for a rule.
	 *
	 * @param \WC_Cart $cart Cart object.
	 * @param Rule     $rule Rule object.
	 *
	 * @return int Gift product ID, or 0 when the rule has no gift line.
	 */
	private function chosen_gift( $cart, Rule $rule ) {
		foreach ( $cart->get_cart() as $cart_item ) {
			if ( $this->is_rule_gift( $cart_item, $rule ) ) {
				return CartHandler::gift_product_id( $cart_item );
			}
		}

		return 0;
	}

	/**
	 * Store the chosen gift on its cart line, so it survives the session.
	 *
	 * @param \WC_Cart $cart          Cart object.
	 * @param string   $cart_item_key Gift line key.
	 * @param Rule     $rule          Rule object.
	 * @param int      $product_id    Gift product ID.
	 *
	 * @return void
	 */
	private function remember_choice( $cart, $cart_item_key, Rule $rule, $product_id ) {
		if ( $rule->offers_gift_choice() && isset( $cart->cart_contents[ $cart_item_key ] ) ) {
			$cart->cart_contents[ $cart_item_key ][ CartHandler::BOGO_GIFT_CHOICE_KEY ] = (int) $product_id;
		}
	}

	/**
	 * Whether a cart line is a gift granted by the given rule.
	 *
	 * @param array $cart_item Cart item.
	 * @param Rule  $rule      Rule object.
	 *
	 * @return bool
	 */
	private function is_rule_gift( $cart_item, Rule $rule ) {
		return CartHandler::is_bogo_item( $cart_item )
			&& isset( $cart_item[ CartHandler::BOGO_RULE_KEY ] )
			&& (int) $cart_item[ CartHandler::BOGO_RULE_KEY ] === (int) $rule->id;
	}

	/**
	 * Check whether the rule's exclusions rule out its gift product.
	 *
	 * @param int  $product_id Gift product or variation ID.
	 * @param Rule $rule       Rule object.
	 *
	 * @return bool
	 */
	private function is_gift_excluded( $product_id, Rule $rule ) {
		if ( ! $rule->has_exclusions() ) {
			return false;
		}

		$product   = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;
		$product   = $product ? $product : null;
		$parent_id = $product && $product->get_parent_id() ? (int) $product->get_parent_id() : $product_id;

		return $this->eligibility_checker->is_product_excluded( $product_id, $parent_id, $rule, $product );
	}

	/**
	 * Apply discount to cheapest eligible items.
	 *
	 * Prices are calculated from each line's BOGO baseline (its effective,
	 * sale-aware price), and lines already claimed by a higher-priority rule are
	 * skipped so overlapping rules never overwrite each other.
	 *
	 * @param \WC_Cart $cart           Cart object.
	 * @param Rule     $rule           Rule object.
	 * @param array    $eligible_items Eligible cart items.
	 * @param int      $discount_qty   Quantity to discount.
	 * @param array    $claimed        Cart item keys already discounted.
	 *
	 * @return array The updated list of claimed cart item keys.
	 */
	private function apply_discount_to_cheapest( $cart, Rule $rule, $eligible_items, $discount_qty, array $claimed ) {
		// Skip lines a higher-priority rule already claimed.
		foreach ( array_keys( $eligible_items ) as $key ) {
			if ( in_array( $key, $claimed, true ) ) {
				unset( $eligible_items[ $key ] );
			}
		}

		// Sort items by their baseline price (cheapest first).
		uasort(
			$eligible_items,
			function ( $a, $b ) use ( $cart ) {
				return $this->baseline( $cart, $a ) <=> $this->baseline( $cart, $b );
			}
		);

		$remaining_discount = $discount_qty;

		$discount_percent = (float) $rule->discount_value;

		foreach ( $eligible_items as $cart_item_key => $cart_item ) {
			if ( $remaining_discount <= 0 ) {
				break;
			}

			$product       = $cart_item['data'];
			$item_quantity = $cart_item['quantity'];
			$baseline      = $this->baseline( $cart, $cart_item );

			$items_to_discount   = min( $item_quantity, $remaining_discount );
			$remaining_discount -= $items_to_discount;

			$discount_amount  = ( $baseline * $discount_percent ) / 100;
			$discounted_price = $baseline - $discount_amount;

			// If discounting partial quantity, split via a weighted average price.
			if ( $items_to_discount < $item_quantity ) {
				$regular_qty = $item_quantity - $items_to_discount;
				$total_price = ( $regular_qty * $baseline ) + ( $items_to_discount * $discounted_price );
				$product->set_price( $total_price / $item_quantity );
			} else {
				$product->set_price( $discounted_price );
			}

			// Store discount info and claim the line.
			$cart->cart_contents[ $cart_item_key ][ CartHandler::BOGO_DISCOUNT_KEY ] = $discount_amount * $items_to_discount;
			$cart->cart_contents[ $cart_item_key ][ CartHandler::BOGO_RULE_KEY ]     = $rule->id;
			$claimed[] = $cart_item_key;
		}

		return $claimed;
	}

	/**
	 * The BOGO baseline (effective, sale-aware) price for a cart line.
	 *
	 * The totals hook resets every line to this baseline before discounting, so it
	 * reflects the pre-promotion price even across repeated recalculations.
	 *
	 * @param \WC_Cart $cart      Cart object.
	 * @param array    $cart_item Cart item.
	 *
	 * @return float
	 */
	private function baseline( $cart, $cart_item ) {
		$key = isset( $cart_item['key'] ) ? $cart_item['key'] : null;

		if ( $key && isset( $cart->cart_contents[ $key ][ CartHandler::BOGO_BASELINE_KEY ] ) ) {
			return (float) $cart->cart_contents[ $key ][ CartHandler::BOGO_BASELINE_KEY ];
		}

		return (float) $cart_item['data']->get_price();
	}
}
