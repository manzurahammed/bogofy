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
	 * A cart below the rule's minimum subtotal or item count has no eligible
	 * items, so the rule grants nothing.
	 *
	 * @param \WC_Cart $cart Cart object.
	 * @param Rule     $rule Rule object.
	 *
	 * @return array Array of eligible cart items.
	 */
	public function get_eligible_items( $cart, Rule $rule ) {
		$eligible_items = array();

		if ( ! $this->meets_cart_minimums( $cart, $rule ) ) {
			return $eligible_items;
		}

		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			// Skip BOGO free items.
			if ( CartHandler::is_bogo_item( $cart_item ) ) {
				continue;
			}

			$product_id = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];

			if ( $this->is_product_eligible( $product_id, $cart_item['product_id'], $rule, $cart_item['data'] ?? null ) ) {
				$eligible_items[ $cart_item_key ] = $cart_item;
			}
		}

		return $eligible_items;
	}

	/**
	 * Check whether the cart reaches the rule's minimum subtotal and item count.
	 *
	 * Both are measured over the paid lines the rule doesn't exclude: BOGO gift
	 * lines and excluded products never count. The subtotal uses each line's
	 * price before any BOGO discount (its baseline) times its quantity, so a
	 * rule's own discount can't push the cart back under its minimum.
	 *
	 * @since 2.2.0
	 *
	 * @param \WC_Cart $cart Cart object.
	 * @param Rule     $rule Rule object.
	 *
	 * @return bool
	 */
	public function meets_cart_minimums( $cart, Rule $rule ) {
		$min_subtotal = (float) $rule->conditions['min_subtotal'];
		$min_items    = (int) $rule->conditions['min_items'];

		if ( $min_subtotal <= 0 && $min_items <= 0 ) {
			return true;
		}

		$subtotal = 0.0;
		$items    = 0;

		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			if ( CartHandler::is_bogo_item( $cart_item ) ) {
				continue;
			}

			$product_id = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];

			if ( $this->is_product_excluded( $product_id, $cart_item['product_id'], $rule, $cart_item['data'] ?? null ) ) {
				continue;
			}

			$quantity  = (int) $cart_item['quantity'];
			$items    += $quantity;
			$subtotal += $this->baseline_price( $cart, $cart_item_key, $cart_item ) * $quantity;
		}

		// Compare whole currency amounts: an unrounded float sum such as
		// 3 x 0.30 lands just under 0.90 and would miss an exact minimum.
		$decimals = function_exists( 'wc_get_price_decimals' ) ? absint( wc_get_price_decimals() ) : 2;

		return round( $subtotal, $decimals ) >= round( $min_subtotal, $decimals ) && $items >= $min_items;
	}

	/**
	 * Check if a product is eligible for a rule.
	 *
	 * @param int              $product_id Product or variation ID.
	 * @param int              $parent_id  Parent product ID.
	 * @param Rule             $rule       Rule object.
	 * @param \WC_Product|null $product    Product object, when the caller has one.
	 *
	 * @return bool
	 */
	public function is_product_eligible( $product_id, $parent_id, Rule $rule, $product = null ) {
		if ( ! $this->is_targeted( $product_id, $parent_id, $rule ) ) {
			return false;
		}

		return ! $this->is_product_excluded( $product_id, $parent_id, $rule, $product );
	}

	/**
	 * Check if a product matches the rule's trigger target.
	 *
	 * @param int  $product_id Product or variation ID.
	 * @param int  $parent_id  Parent product ID.
	 * @param Rule $rule       Rule object.
	 *
	 * @return bool
	 */
	private function is_targeted( $product_id, $parent_id, Rule $rule ) {
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
	 * Check if the rule's exclusions rule out a product.
	 *
	 * Excluded products never count toward the offer or receive it as a gift.
	 *
	 * @since 2.2.0
	 *
	 * @param int              $product_id Product or variation ID.
	 * @param int              $parent_id  Parent product ID.
	 * @param Rule             $rule       Rule object.
	 * @param \WC_Product|null $product    Product object, when the caller has one.
	 *
	 * @return bool
	 */
	public function is_product_excluded( $product_id, $parent_id, Rule $rule, $product = null ) {
		if ( ! $rule->has_exclusions() ) {
			return false;
		}

		$category_ids = array();
		if ( ! empty( $rule->conditions['excluded_category_ids'] ) ) {
			// Variations carry no categories of their own; the parent's apply.
			$terms        = wp_get_post_terms( $parent_id, 'product_cat', array( 'fields' => 'ids' ) );
			$category_ids = is_wp_error( $terms ) ? array() : $terms;
		}

		$on_sale = false;
		if ( $rule->conditions['exclude_sale_items'] ) {
			if ( null === $product && function_exists( 'wc_get_product' ) ) {
				$product = wc_get_product( $product_id );
			}

			$on_sale = $product && $product->is_on_sale();
		}

		return $rule->excludes_product( array( $product_id, $parent_id ), $category_ids, $on_sale );
	}

	/**
	 * The line's price before any BOGO discount.
	 *
	 * @param \WC_Cart $cart          Cart object.
	 * @param string   $cart_item_key Cart item key.
	 * @param array    $cart_item     Cart item.
	 *
	 * @return float
	 */
	private function baseline_price( $cart, $cart_item_key, $cart_item ) {
		if ( isset( $cart->cart_contents[ $cart_item_key ][ CartHandler::BOGO_BASELINE_KEY ] ) ) {
			return (float) $cart->cart_contents[ $cart_item_key ][ CartHandler::BOGO_BASELINE_KEY ];
		}

		return isset( $cart_item['data'] ) ? (float) $cart_item['data']->get_price() : 0.0;
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

		/**
		 * Filters how many free units a rule grants.
		 *
		 * Applied after the rule's maximum, so a filter may exceed it. Negative
		 * results are treated as zero.
		 *
		 * @since 2.2.0
		 *
		 * @param int   $free_quantity  Free units earned by the cart.
		 * @param Rule  $rule           Rule object.
		 * @param array $eligible_items Eligible cart items, keyed by cart item key.
		 */
		$free_quantity = apply_filters( 'bogofy_free_quantity', $free_quantity, $rule, $eligible_items );

		return max( 0, (int) $free_quantity );
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
