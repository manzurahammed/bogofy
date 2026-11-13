<?php
/**
 * Eligibility checker for BOGO rules.
 *
 * @package Bogofy\Cart
 */

namespace Bogofy\Cart;

use Bogofy\Cart\Contracts\EligibilityCheckerInterface;
use Bogofy\Models\Rule;

/**
 * Class EligibilityChecker
 *
 * Checks if cart items are eligible for BOGO rules.
 */
class EligibilityChecker implements EligibilityCheckerInterface {

	/**
	 * Get eligible cart items for a rule.
	 *
	 * @param \WC_Cart $cart Cart object.
	 * @param Rule     $rule Rule object.
	 *
	 * @return array Array of eligible cart items.
	 */
	public function get_eligible_items( $cart, Rule $rule ) {
		$eligible_items = array();

		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			// Skip BOGO free items.
			if ( CartHandler::is_bogo_item( $cart_item ) ) {
				continue;
			}

			$product_id = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];

			if ( $this->is_product_eligible( $product_id, $cart_item['product_id'], $rule ) ) {
				$eligible_items[ $cart_item_key ] = $cart_item;
			}
		}

		return $eligible_items;
	}

	/**
	 * Check if a product is eligible for a rule.
	 *
	 * @param int  $product_id   Product or variation ID.
	 * @param int  $parent_id    Parent product ID.
	 * @param Rule $rule         Rule object.
	 *
	 * @return bool
	 */
	public function is_product_eligible( $product_id, $parent_id, Rule $rule ) {
		switch ( $rule->apply_to ) {
			case Rule::APPLY_ALL_PRODUCTS:
				return true;

			case Rule::APPLY_SPECIFIC_PRODUCTS:
				return $this->is_in_product_list( $product_id, $parent_id, $rule->buy_product_ids );

			case Rule::APPLY_SPECIFIC_CATEGORIES:
				return $this->is_in_category_list( $parent_id, $rule->category_ids );

			default:
				return false;
		}
	}

	/**
	 * Check if product is in the buy product list.
	 *
	 * @param int   $product_id  Product or variation ID.
	 * @param int   $parent_id   Parent product ID.
	 * @param array $product_ids List of product IDs.
	 *
	 * @return bool
	 */
	private function is_in_product_list( $product_id, $parent_id, $product_ids ) {
		return in_array( $product_id, $product_ids, true ) ||
				in_array( $parent_id, $product_ids, true );
	}

	/**
	 * Check if product is in the category list.
	 *
	 * @param int   $product_id   Product ID.
	 * @param array $category_ids List of category IDs.
	 *
	 * @return bool
	 */
	private function is_in_category_list( $product_id, $category_ids ) {
		$product_categories = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );

		if ( is_wp_error( $product_categories ) ) {
			return false;
		}

		return ! empty( array_intersect( $product_categories, $category_ids ) );
	}

	/**
	 * Calculate how many free items the customer is entitled to.
	 *
	 * @param array $eligible_items Eligible cart items.
	 * @param Rule  $rule           Rule object.
	 *
	 * @return int Number of free items.
	 */
	public function calculate_free_quantity( $eligible_items, Rule $rule ) {
		$total_quantity = 0;

		foreach ( $eligible_items as $cart_item ) {
			$total_quantity += $cart_item['quantity'];
		}

		// Calculate how many "sets" of buy_quantity are in the cart.
		$sets = (int) floor( $total_quantity / max( 1, $rule->buy_quantity ) );

		// Calculate total free items.
		$free_quantity = $sets * $rule->free_quantity;

		// Apply max limit if set.
		if ( null !== $rule->max_free_qty && $free_quantity > $rule->max_free_qty ) {
			$free_quantity = $rule->max_free_qty;
		}

		return $free_quantity;
	}

	/**
	 * Work out what the next partly-met set of a rule would unlock.
	 *
	 * Uses the same set math as calculate_free_quantity(): with a "buy 2" rule and
	 * 3 eligible items, 1 more item completes the next set. Returns null when the
	 * cart has no eligible items, sits exactly on a set boundary, or the rule's
	 * max free quantity is already reached. For discounted rules the unlocked
	 * quantity is limited to items that will actually be in the cart.
	 *
	 * @since 2.2.0
	 *
	 * @param array $eligible_items Eligible cart items.
	 * @param Rule  $rule           Rule object.
	 *
	 * @return array{remaining:int,free_qty:int}|null
	 */
	public function get_next_unlock( $eligible_items, Rule $rule ) {
		$total_quantity = 0;

		foreach ( $eligible_items as $cart_item ) {
			$total_quantity += (int) $cart_item['quantity'];
		}

		$buy_quantity = max( 1, (int) $rule->buy_quantity );
		$into_set     = $total_quantity % $buy_quantity;

		if ( 0 === $total_quantity || 0 === $into_set ) {
			return null;
		}

		$remaining    = $buy_quantity - $into_set;
		$current_free = $this->calculate_free_quantity( $eligible_items, $rule );
		$next_total   = $total_quantity + $remaining;
		$next_free    = $this->calculate_free_quantity( array( array( 'quantity' => $next_total ) ), $rule );

		// Discounted rules discount the bought items themselves, so they can never
		// discount more items than are in the cart.
		if ( Rule::TYPE_BUY_X_GET_X_DISCOUNTED === $rule->rule_type ) {
			$current_free = min( $current_free, $total_quantity );
			$next_free    = min( $next_free, $next_total );
		}

		// What the next set adds, already capped by max_free_qty.
		$free_quantity = $next_free - $current_free;

		if ( $free_quantity < 1 ) {
			return null;
		}

		return array(
			'remaining' => $remaining,
			'free_qty'  => $free_quantity,
		);
	}

	/**
	 * Check if rule is currently active (within date range).
	 *
	 * @param Rule $rule Rule object.
	 *
	 * @return bool
	 */
	public function is_rule_active( Rule $rule ) {
		return $rule->is_active();
	}
}
