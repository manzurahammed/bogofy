<?php
/**
 * Free item manager for BOGO rules.
 *
 * @package Bogofy\Cart
 */

namespace Bogofy\Cart;

use Bogofy\Cart\Contracts\FreeItemManagerInterface;
use Bogofy\Models\Rule;

/**
 * Class FreeItemManager
 *
 * Manages adding and removing free items in cart.
 */
class FreeItemManager implements FreeItemManagerInterface {

	/**
	 * Add free item to cart.
	 *
	 * @param \WC_Cart $cart        Cart object.
	 * @param int      $product_id  Product ID to add.
	 * @param int      $quantity    Quantity to add.
	 * @param Rule     $rule        Rule object.
	 *
	 * @return bool|string Cart item key or false on failure.
	 */
	public function add_free_item( $cart, $product_id, $quantity, Rule $rule ) {
		$product = wc_get_product( $product_id );

		if ( ! $product || ! $product->is_in_stock() ) {
			return false;
		}

		// Generate unique cart item key for this BOGO item.
		$bogo_key = $this->generate_bogo_key( $product_id, $rule->id );

		// Check if we already have this free item in cart.
		$existing_key = $this->find_bogo_item( $cart, $product_id, $rule->id );

		if ( $existing_key ) {
			// Update quantity if different.
			$current_qty = $cart->cart_contents[ $existing_key ]['quantity'];
			if ( $current_qty !== $quantity ) {
				$cart->cart_contents[ $existing_key ]['quantity'] = $quantity;
			}

			// Update price to free/discounted.
			$this->apply_free_price( $cart, $existing_key, $rule );
			return $existing_key;
		}

		// Add new free item.
		$cart_item_data = array(
			CartHandler::BOGO_ITEM_KEY => true,
			CartHandler::BOGO_RULE_KEY => $rule->id,
			'_bogo_cart_key'           => $bogo_key,
		);

		$variation_id = 0;
		$variation    = array();

		if ( $product->is_type( 'variation' ) ) {
			$variation_id = $product_id;
			$product_id   = $product->get_parent_id();
			$variation    = $product->get_variation_attributes();
		}

		$cart_item_key = $cart->add_to_cart(
			$product_id,
			$quantity,
			$variation_id,
			$variation,
			$cart_item_data
		);

		if ( $cart_item_key ) {
			$this->apply_free_price( $cart, $cart_item_key, $rule );
		}

		return $cart_item_key;
	}

	/**
	 * Remove all BOGO items for a specific rule.
	 *
	 * @param \WC_Cart $cart    Cart object.
	 * @param int      $rule_id Rule ID.
	 *
	 * @return void
	 */
	public function remove_rule_items( $cart, $rule_id ) {
		$items_to_remove = array();

		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			if (
				isset( $cart_item[ CartHandler::BOGO_ITEM_KEY ] ) &&
				$cart_item[ CartHandler::BOGO_ITEM_KEY ] &&
				isset( $cart_item[ CartHandler::BOGO_RULE_KEY ] ) &&
				(int) $cart_item[ CartHandler::BOGO_RULE_KEY ] === $rule_id
			) {
				$items_to_remove[] = $cart_item_key;
			}
		}

		foreach ( $items_to_remove as $key ) {
			$cart->remove_cart_item( $key );
		}
	}

	/**
	 * Remove BOGO items for a rule except for the given product IDs.
	 *
	 * Keeps free items whose product is still being granted and removes the rest,
	 * so stale free lines are cleaned up when the eligible products change.
	 *
	 * @param \WC_Cart $cart             Cart object.
	 * @param int      $rule_id          Rule ID.
	 * @param array    $keep_product_ids Product/variation IDs to keep.
	 *
	 * @return void
	 */
	public function remove_rule_items_except( $cart, $rule_id, $keep_product_ids ) {
		$keep_product_ids = array_map( 'intval', (array) $keep_product_ids );
		$items_to_remove  = array();

		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			if (
				CartHandler::is_bogo_item( $cart_item ) &&
				isset( $cart_item[ CartHandler::BOGO_RULE_KEY ] ) &&
				(int) $cart_item[ CartHandler::BOGO_RULE_KEY ] === (int) $rule_id
			) {
				$item_product_id = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
				if ( ! in_array( (int) $item_product_id, $keep_product_ids, true ) ) {
					$items_to_remove[] = $cart_item_key;
				}
			}
		}

		foreach ( $items_to_remove as $key ) {
			$cart->remove_cart_item( $key );
		}
	}

	/**
	 * Cleanup orphaned BOGO items.
	 *
	 * @param \WC_Cart $cart Cart object.
	 *
	 * @return void
	 */
	public function cleanup_orphaned_items( $cart ) {
		// Get list of rule IDs for items still in cart.
		$active_rules = array();

		foreach ( $cart->get_cart() as $cart_item ) {
			if (
				! CartHandler::is_bogo_item( $cart_item ) &&
				isset( $cart_item[ CartHandler::BOGO_RULE_KEY ] )
			) {
				$active_rules[] = (int) $cart_item[ CartHandler::BOGO_RULE_KEY ];
			}
		}

		// Remove BOGO items whose rules are no longer active.
		$items_to_remove = array();

		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			if (
				CartHandler::is_bogo_item( $cart_item ) &&
				isset( $cart_item[ CartHandler::BOGO_RULE_KEY ] ) &&
				! in_array( (int) $cart_item[ CartHandler::BOGO_RULE_KEY ], $active_rules, true )
			) {
				$items_to_remove[] = $cart_item_key;
			}
		}

		foreach ( $items_to_remove as $key ) {
			$cart->remove_cart_item( $key );
		}
	}

	/**
	 * Generate unique BOGO cart item key.
	 *
	 * @param int $product_id Product ID.
	 * @param int $rule_id    Rule ID.
	 *
	 * @return string
	 */
	private function generate_bogo_key( $product_id, $rule_id ) {
		return 'bogo_' . $product_id . '_' . $rule_id;
	}

	/**
	 * Find existing BOGO item in cart.
	 *
	 * @param \WC_Cart $cart       Cart object.
	 * @param int      $product_id Product ID.
	 * @param int      $rule_id    Rule ID.
	 *
	 * @return string|false Cart item key or false.
	 */
	private function find_bogo_item( $cart, $product_id, $rule_id ) {
		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			if (
				CartHandler::is_bogo_item( $cart_item ) &&
				isset( $cart_item[ CartHandler::BOGO_RULE_KEY ] ) &&
				(int) $cart_item[ CartHandler::BOGO_RULE_KEY ] === $rule_id
			) {
				$item_product_id = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
				if ( (int) $item_product_id === $product_id ) {
					return $cart_item_key;
				}
			}
		}

		return false;
	}

	/**
	 * Apply free/discounted price to cart item.
	 *
	 * @param \WC_Cart $cart          Cart object.
	 * @param string   $cart_item_key Cart item key.
	 * @param Rule     $rule          Rule object.
	 *
	 * @return void
	 */
	public function apply_free_price( $cart, $cart_item_key, Rule $rule ) {
		if ( ! isset( $cart->cart_contents[ $cart_item_key ] ) ) {
			return;
		}

		$product        = $cart->cart_contents[ $cart_item_key ]['data'];
		$original_price = (float) $product->get_regular_price();

		if ( Rule::DISCOUNT_FREE === $rule->discount_type ) {
			$product->set_price( 0 );
			$cart->cart_contents[ $cart_item_key ][ CartHandler::BOGO_DISCOUNT_KEY ] = $original_price;
		} else {
			$discount_amount = ( $original_price * $rule->discount_value ) / 100;
			$new_price       = $original_price - $discount_amount;
			$product->set_price( $new_price );
			$cart->cart_contents[ $cart_item_key ][ CartHandler::BOGO_DISCOUNT_KEY ] = $discount_amount;
		}
	}
}
