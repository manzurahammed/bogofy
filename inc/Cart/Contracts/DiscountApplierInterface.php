<?php
/**
 * Contract for applying BOGO discounts to the cart.
 *
 * @package Bogofy\Cart\Contracts
 */

namespace Bogofy\Cart\Contracts;

use Bogofy\Models\Rule;

/**
 * Interface DiscountApplierInterface
 *
 * Reconciles free item lines and applies discounted pricing for a rule.
 */
interface DiscountApplierInterface {

	/**
	 * Reconcile the free item lines for a rule (add / update / remove).
	 *
	 * @param \WC_Cart $cart           Cart object.
	 * @param Rule     $rule           Rule object.
	 * @param array    $eligible_items Eligible cart items.
	 *
	 * @return void
	 */
	public function sync( $cart, Rule $rule, $eligible_items );

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
	public function apply_discounted( $cart, Rule $rule, $eligible_items, array $claimed = array() );

	/**
	 * Swap a rule's gift line for another of its gift products.
	 *
	 * @since 2.2.0
	 *
	 * @param \WC_Cart $cart       Cart object.
	 * @param Rule     $rule       Rule object.
	 * @param int      $product_id Chosen gift product or variation ID.
	 *
	 * @return bool Whether the chosen gift is now in the cart.
	 */
	public function switch_gift( $cart, Rule $rule, $product_id );
}
