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
	 * Handles the rule types that grant an additional free item. The "discounted"
	 * rule type does not add a line; it is priced during totals calculation via
	 * {@see DiscountApplier::apply_discounted()}.
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
	 *
	 * @return void
	 */
	public function apply_discounted( $cart, Rule $rule, $eligible_items ) {
		$free_quantity = $this->eligibility_checker->calculate_free_quantity( $eligible_items, $rule );

		if ( $free_quantity <= 0 ) {
			return;
		}

		$this->apply_discount_to_cheapest( $cart, $rule, $eligible_items, $free_quantity );
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
	 * Used for "Buy X Get X Free" rules that apply to all products or categories,
	 * where no specific free product is configured. A free duplicate of each eligible
	 * product is added to the cart (cheapest first) until the free quantity is met.
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

			// Never grant more free units of a line than were purchased.
			$free_qty = min( (int) $cart_item['quantity'], $remaining );

			if ( $free_qty <= 0 ) {
				continue;
			}

			$this->free_item_manager->add_free_item( $cart, $product_id, $free_qty, $rule );
			$free_added_ids[] = (int) $product_id;
			$remaining       -= $free_qty;
		}

		// Remove any previously-added free items for this rule that are no longer valid.
		$this->free_item_manager->remove_rule_items_except( $cart, $rule->id, $free_added_ids );
	}

	/**
	 * Reconcile the free item for a rule that grants a specific free product.
	 *
	 * Used by "Buy X Get Y Free" and "Buy from Category Get Free" rules.
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

		// Add the specified free product (first configured free product).
		$free_product_id = (int) $rule->free_product_ids[0];
		$this->free_item_manager->add_free_item( $cart, $free_product_id, $free_quantity, $rule );

		// Drop any stale free lines for this rule (e.g. if the configured product changed).
		$this->free_item_manager->remove_rule_items_except( $cart, $rule->id, array( $free_product_id ) );
	}

	/**
	 * Apply discount to cheapest eligible items.
	 *
	 * @param \WC_Cart $cart           Cart object.
	 * @param Rule     $rule           Rule object.
	 * @param array    $eligible_items Eligible cart items.
	 * @param int      $discount_qty   Quantity to discount.
	 *
	 * @return void
	 */
	private function apply_discount_to_cheapest( $cart, Rule $rule, $eligible_items, $discount_qty ) {
		// Sort items by price (cheapest first).
		uasort(
			$eligible_items,
			function ( $a, $b ) {
				$price_a = $a['data']->get_price();
				$price_b = $b['data']->get_price();
				return $price_a <=> $price_b;
			}
		);

		$remaining_discount = $discount_qty;
		$discount_percent   = Rule::DISCOUNT_FREE === $rule->discount_type ? 100 : $rule->discount_value;

		foreach ( $eligible_items as $cart_item_key => $cart_item ) {
			if ( $remaining_discount <= 0 ) {
				break;
			}

			$product        = $cart_item['data'];
			$item_quantity  = $cart_item['quantity'];
			$original_price = (float) $product->get_regular_price();

			// Calculate how many items to discount in this line.
			$items_to_discount   = min( $item_quantity, $remaining_discount );
			$remaining_discount -= $items_to_discount;

			// Calculate discounted price.
			$discount_amount  = ( $original_price * $discount_percent ) / 100;
			$discounted_price = $original_price - $discount_amount;

			// If discounting partial quantity, we need to split the line item.
			if ( $items_to_discount < $item_quantity ) {
				// Calculate weighted average price.
				$regular_qty = $item_quantity - $items_to_discount;
				$total_price = ( $regular_qty * $original_price ) + ( $items_to_discount * $discounted_price );
				$avg_price   = $total_price / $item_quantity;
				$product->set_price( $avg_price );
			} else {
				// All items in this line get discount.
				$product->set_price( $discounted_price );
			}

			// Store discount info in cart item.
			$cart->cart_contents[ $cart_item_key ][ CartHandler::BOGO_DISCOUNT_KEY ] = $discount_amount * $items_to_discount;
			$cart->cart_contents[ $cart_item_key ][ CartHandler::BOGO_RULE_KEY ]     = $rule->id;
		}
	}
}
