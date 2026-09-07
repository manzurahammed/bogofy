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

		// For Buy X Get X with specific products, add a free copy of the configured product.
		if ( Rule::APPLY_SPECIFIC_PRODUCTS === $rule->apply_to && ! empty( $rule->buy_product_ids ) ) {
			$free_product_id = (int) $rule->buy_product_ids[0];
			$this->free_item_manager->add_free_item( $cart, $free_product_id, $free_quantity, $rule );
			return;
		}

		// For all products / categories, add free copies of the cheapest eligible products
		// so the customer receives an additional free item rather than the purchased one becoming free.
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

		$remaining      = (int) $free_quantity;
		$free_added_ids = array();

		foreach ( $eligible_items as $cart_item ) {
			if ( $remaining <= 0 ) {
				break;
			}

			$product_id = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
			$free_qty   = min( (int) $cart_item['quantity'], $remaining );

			if ( $free_qty <= 0 ) {
				continue;
			}

			$this->free_item_manager->add_free_item( $cart, $product_id, $free_qty, $rule );
			$free_added_ids[] = (int) $product_id;
			$remaining       -= $free_qty;
		}

		$this->free_item_manager->remove_rule_items_except( $cart, $rule->id, $free_added_ids );
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

		$free_product_id = (int) $rule->free_product_ids[0];
		$this->free_item_manager->add_free_item( $cart, $free_product_id, $free_quantity, $rule );
		$this->free_item_manager->remove_rule_items_except( $cart, $rule->id, array( $free_product_id ) );
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
