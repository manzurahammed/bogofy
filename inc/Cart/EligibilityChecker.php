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
		$sets = floor( $total_quantity / $rule->buy_quantity );

		// Calculate total free items.
		$free_quantity = $sets * $rule->free_quantity;

		// Apply max limit if set.
		if ( null !== $rule->max_free_qty && $free_quantity > $rule->max_free_qty ) {
			$free_quantity = $rule->max_free_qty;
		}

		return $free_quantity;
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
