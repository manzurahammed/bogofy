<?php
/**
 * Discount applier for BOGO rules.
 *
 * @package BuyOneGetOne\Cart
 */

namespace BuyOneGetOne\Cart;

use BuyOneGetOne\Models\Rule;

/**
 * Class DiscountApplier
 *
 * Applies BOGO discounts to cart.
 */
class DiscountApplier {

	/**
	 * Free item manager.
	 *
	 * @var FreeItemManager
	 */
	private $free_item_manager;

	/**
	 * Eligibility checker.
	 *
	 * @var EligibilityChecker
	 */
	private $eligibility_checker;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->free_item_manager   = new FreeItemManager();
		$this->eligibility_checker = new EligibilityChecker();
	}

	/**
	 * Apply BOGO rule to cart.
	 *
	 * @param \WC_Cart $cart           Cart object.
	 * @param Rule     $rule           Rule object.
	 * @param array    $eligible_items Eligible cart items.
	 *
	 * @return void
	 */
	public function apply( $cart, Rule $rule, $eligible_items ) {
		switch ( $rule->rule_type ) {
			case Rule::TYPE_BUY_X_GET_X:
				$this->apply_buy_x_get_x( $cart, $rule, $eligible_items );
				break;

			case Rule::TYPE_BUY_X_GET_Y:
				$this->apply_buy_x_get_y( $cart, $rule, $eligible_items );
				break;

			case Rule::TYPE_BUY_CAT_GET_FREE:
				$this->apply_buy_cat_get_free( $cart, $rule, $eligible_items );
				break;

			case Rule::TYPE_BUY_X_GET_X_DISCOUNTED:
				$this->apply_buy_x_get_x_discounted( $cart, $rule, $eligible_items );
				break;
		}
	}

	/**
	 * Apply Buy X Get X Free rule.
	 *
	 * @param \WC_Cart $cart           Cart object.
	 * @param Rule     $rule           Rule object.
	 * @param array    $eligible_items Eligible cart items.
	 *
	 * @return void
	 */
	private function apply_buy_x_get_x( $cart, Rule $rule, $eligible_items ) {
		$free_quantity = $this->eligibility_checker->calculate_free_quantity( $eligible_items, $rule );

		if ( $free_quantity <= 0 ) {
			$this->free_item_manager->remove_rule_items( $cart, $rule->id );
			return;
		}

		// For Buy X Get X with specific products, add a free copy of the same product.
		if ( Rule::APPLY_SPECIFIC_PRODUCTS === $rule->apply_to && ! empty( $rule->buy_product_ids ) ) {
			$free_product_id = $rule->buy_product_ids[0];
			$this->free_item_manager->add_free_item( $cart, $free_product_id, $free_quantity, $rule );
			return;
		}

		// For all products / categories, apply discount to cheapest eligible items.
		$this->apply_discount_to_cheapest( $cart, $rule, $eligible_items, $free_quantity );
	}

	/**
	 * Apply Buy X Get Y Free rule.
	 *
	 * @param \WC_Cart $cart           Cart object.
	 * @param Rule     $rule           Rule object.
	 * @param array    $eligible_items Eligible cart items.
	 *
	 * @return void
	 */
	private function apply_buy_x_get_y( $cart, Rule $rule, $eligible_items ) {
		$free_quantity = $this->eligibility_checker->calculate_free_quantity( $eligible_items, $rule );

		if ( $free_quantity <= 0 || empty( $rule->free_product_ids ) ) {
			$this->free_item_manager->remove_rule_items( $cart, $rule->id );
			return;
		}

		// Add the specified free product.
		$free_product_id = $rule->free_product_ids[0]; // Use first free product.
		$this->free_item_manager->add_free_item( $cart, $free_product_id, $free_quantity, $rule );
	}

	/**
	 * Apply Buy from Category Get Free rule.
	 *
	 * @param \WC_Cart $cart           Cart object.
	 * @param Rule     $rule           Rule object.
	 * @param array    $eligible_items Eligible cart items.
	 *
	 * @return void
	 */
	private function apply_buy_cat_get_free( $cart, Rule $rule, $eligible_items ) {
		$free_quantity = $this->eligibility_checker->calculate_free_quantity( $eligible_items, $rule );

		if ( $free_quantity <= 0 || empty( $rule->free_product_ids ) ) {
			$this->free_item_manager->remove_rule_items( $cart, $rule->id );
			return;
		}

		// Add the specified free product.
		$free_product_id = $rule->free_product_ids[0];
		$this->free_item_manager->add_free_item( $cart, $free_product_id, $free_quantity, $rule );
	}

	/**
	 * Apply Buy X Get X Discounted rule.
	 *
	 * @param \WC_Cart $cart           Cart object.
	 * @param Rule     $rule           Rule object.
	 * @param array    $eligible_items Eligible cart items.
	 *
	 * @return void
	 */
	private function apply_buy_x_get_x_discounted( $cart, Rule $rule, $eligible_items ) {
		$free_quantity = $this->eligibility_checker->calculate_free_quantity( $eligible_items, $rule );

		if ( $free_quantity <= 0 ) {
			$this->free_item_manager->remove_rule_items( $cart, $rule->id );
			return;
		}

		// Apply percentage discount to cheapest items.
		$this->apply_discount_to_cheapest( $cart, $rule, $eligible_items, $free_quantity );
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

			$product       = $cart_item['data'];
			$item_quantity = $cart_item['quantity'];
			$original_price = (float) $product->get_regular_price();

			// Calculate how many items to discount in this line.
			$items_to_discount = min( $item_quantity, $remaining_discount );
			$remaining_discount -= $items_to_discount;

			// Calculate discounted price.
			$discount_amount = ( $original_price * $discount_percent ) / 100;
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
