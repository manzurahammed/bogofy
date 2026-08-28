<?php
/**
 * Contract for BOGO eligibility checking.
 *
 * @package Bogofy\Cart\Contracts
 */

namespace Bogofy\Cart\Contracts;

use Bogofy\Models\Rule;

/**
 * Interface EligibilityCheckerInterface
 *
 * Determines which cart items qualify for a rule and how many free units are earned.
 */
interface EligibilityCheckerInterface {

	/**
	 * Get eligible cart items for a rule.
	 *
	 * @param \WC_Cart $cart Cart object.
	 * @param Rule     $rule Rule object.
	 *
	 * @return array Array of eligible cart items keyed by cart item key.
	 */
	public function get_eligible_items( $cart, Rule $rule );

	/**
	 * Check if a product is eligible for a rule.
	 *
	 * @param int  $product_id Product or variation ID.
	 * @param int  $parent_id  Parent product ID.
	 * @param Rule $rule       Rule object.
	 *
	 * @return bool
	 */
	public function is_product_eligible( $product_id, $parent_id, Rule $rule );

	/**
	 * Calculate how many free items the customer is entitled to.
	 *
	 * @param array $eligible_items Eligible cart items.
	 * @param Rule  $rule           Rule object.
	 *
	 * @return int Number of free items.
	 */
	public function calculate_free_quantity( $eligible_items, Rule $rule );

	/**
	 * Check if a rule is currently active (within its date range).
	 *
	 * @param Rule $rule Rule object.
	 *
	 * @return bool
	 */
	public function is_rule_active( Rule $rule );
}
