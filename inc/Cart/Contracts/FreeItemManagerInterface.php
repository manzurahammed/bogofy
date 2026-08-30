<?php
/**
 * Contract for managing BOGO free item lines in the cart.
 *
 * @package Bogofy\Cart\Contracts
 */

namespace Bogofy\Cart\Contracts;

use Bogofy\Models\Rule;

/**
 * Interface FreeItemManagerInterface
 *
 * Adds, removes, and prices the free item lines that a rule grants.
 */
interface FreeItemManagerInterface {

	/**
	 * Add (or update) a free item in the cart.
	 *
	 * @param \WC_Cart $cart       Cart object.
	 * @param int      $product_id Product ID to add.
	 * @param int      $quantity   Quantity to add.
	 * @param Rule     $rule       Rule object.
	 *
	 * @return bool|string Cart item key or false on failure.
	 */
	public function add_free_item( $cart, $product_id, $quantity, Rule $rule );

	/**
	 * Remove all BOGO items for a specific rule.
	 *
	 * @param \WC_Cart $cart    Cart object.
	 * @param int      $rule_id Rule ID.
	 *
	 * @return void
	 */
	public function remove_rule_items( $cart, $rule_id );

	/**
	 * Remove BOGO items for a rule except for the given product IDs.
	 *
	 * @param \WC_Cart $cart             Cart object.
	 * @param int      $rule_id          Rule ID.
	 * @param array    $keep_product_ids Product/variation IDs to keep.
	 *
	 * @return void
	 */
	public function remove_rule_items_except( $cart, $rule_id, $keep_product_ids );

	/**
	 * Remove orphaned BOGO items whose triggering rule is no longer present.
	 *
	 * @param \WC_Cart $cart Cart object.
	 *
	 * @return void
	 */
	public function cleanup_orphaned_items( $cart );

	/**
	 * Apply the free/discounted price to a cart item line.
	 *
	 * @param \WC_Cart $cart          Cart object.
	 * @param string   $cart_item_key Cart item key.
	 * @param Rule     $rule          Rule object.
	 *
	 * @return void
	 */
	public function apply_free_price( $cart, $cart_item_key, Rule $rule );
}
