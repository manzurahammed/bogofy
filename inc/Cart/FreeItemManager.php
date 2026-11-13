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
	 * @param array    $variation   Chosen attributes, for "Any" variations.
	 *
	 * @return bool|string Cart item key or false on failure.
	 */
	public function add_free_item( $cart, $product_id, $quantity, Rule $rule, $variation = array() ) {
		$product_id   = (int) $product_id;
		$product      = wc_get_product( $product_id );
		$existing_key = $this->find_bogo_item( $cart, $product_id, (int) $rule->id );

		if ( $product && $product->is_type( 'variation' ) ) {
			$variation = array_merge( $product->get_variation_attributes(), $variation );
		}

		// An "Any" variation with no chosen value can't be added to the cart.
		$quantity = in_array( '', $variation, true )
			? 0
			: $this->grantable_quantity( $cart, $product, (int) $quantity, $existing_key );

		if ( $quantity < 1 ) {
			// A gift that can no longer be supplied must not block checkout.
			if ( $existing_key ) {
				$this->remove_gift( $cart, $existing_key, (int) $rule->id );
			}

			return false;
		}

		$gift_id = $product_id;

		// Generate unique cart item key for this BOGO item.
		$bogo_key = $this->generate_bogo_key( $product_id, $rule->id );

		if ( $existing_key ) {
			// Update quantity if different. Pricing happens later in the totals
			// hook so the BOGO baseline is captured from the natural price first.
			$current_qty = $cart->cart_contents[ $existing_key ]['quantity'];
			if ( $current_qty !== $quantity ) {
				$cart->cart_contents[ $existing_key ]['quantity'] = $quantity;
			}

			return $existing_key;
		}

		// Add new free item.
		$cart_item_data = array(
			CartHandler::BOGO_ITEM_KEY => true,
			CartHandler::BOGO_RULE_KEY => $rule->id,
			'_bogo_cart_key'           => $bogo_key,
		);

		$variation_id = 0;

		if ( $product->is_type( 'variation' ) ) {
			$variation_id = $product_id;
			$product_id   = $product->get_parent_id();
		}

		$notices = wc_get_notices();

		$cart_item_key = $cart->add_to_cart(
			$product_id,
			$quantity,
			$variation_id,
			$variation,
			$cart_item_data
		);

		// WooCommerce reports a failed add as an error, which would stop checkout
		// for the paid items. A gift the store can't supply must not do that.
		if ( ! $cart_item_key ) {
			wc_set_notices( $notices );

			return $cart_item_key;
		}

		/**
		 * Fires after Bogofy adds a new gift line to the cart.
		 *
		 * Only a new line fires this. Bogofy re-syncing the quantity of an
		 * existing gift line fires neither this nor `bogofy_gift_removed`.
		 *
		 * @since 2.2.0
		 *
		 * @param string $cart_item_key Cart item key of the gift line.
		 * @param int    $gift_id       Gift product ID, or variation ID for a variation.
		 * @param int    $quantity      Quantity added.
		 * @param Rule   $rule          Rule that granted the gift.
		 */
		do_action( 'bogofy_gift_added', $cart_item_key, $gift_id, $quantity, $rule );

		// Pricing is applied in the totals hook (apply_bogo_prices) so the BOGO
		// baseline is captured from the natural price first.
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
			$this->remove_gift( $cart, $key, (int) $rule_id );
		}
	}

	/**
	 * Remove BOGO items for a rule except for the given product IDs.
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
			$this->remove_gift( $cart, $key, (int) $rule_id );
		}
	}

	/**
	 * Remove BOGO free lines whose owning rule is no longer active.
	 *
	 * Handles rules that were deactivated, deleted, expired, or removed entirely
	 * (in which case $active_rule_ids is empty and every BOGO line is dropped).
	 *
	 * @param \WC_Cart $cart            Cart object.
	 * @param int[]    $active_rule_ids IDs of the currently active rules.
	 *
	 * @return void
	 */
	public function remove_items_for_inactive_rules( $cart, array $active_rule_ids ) {
		$active_rule_ids = array_map( 'intval', $active_rule_ids );
		$items_to_remove = array();

		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			if (
				CartHandler::is_bogo_item( $cart_item ) &&
				isset( $cart_item[ CartHandler::BOGO_RULE_KEY ] ) &&
				! in_array( (int) $cart_item[ CartHandler::BOGO_RULE_KEY ], $active_rule_ids, true )
			) {
				$items_to_remove[ $cart_item_key ] = (int) $cart_item[ CartHandler::BOGO_RULE_KEY ];
			}
		}

		foreach ( $items_to_remove as $key => $rule_id ) {
			$this->remove_gift( $cart, $key, $rule_id );
		}
	}

	/**
	 * Remove a gift line from the cart.
	 *
	 * @param \WC_Cart $cart          Cart object.
	 * @param string   $cart_item_key Cart item key of the gift line.
	 * @param int      $rule_id       ID of the rule that granted the gift.
	 *
	 * @return void
	 */
	private function remove_gift( $cart, $cart_item_key, $rule_id ) {
		$cart->remove_cart_item( $cart_item_key );

		/**
		 * Fires after Bogofy removes a gift line from the cart.
		 *
		 * Covers gifts the cart no longer earns, gifts that can no longer be
		 * supplied, and gifts of rules that are vetoed, inactive, or deleted.
		 *
		 * @since 2.2.0
		 *
		 * @param string $cart_item_key Cart item key of the removed gift line.
		 * @param int    $rule_id       ID of the rule that granted the gift.
		 */
		do_action( 'bogofy_gift_removed', $cart_item_key, $rule_id );
	}

	/**
	 * How many units of a gift can actually be supplied.
	 *
	 * Other lines drawing on the same stock are deducted, which includes sibling
	 * variations when the parent manages stock, and so is stock held for other
	 * customers' pending orders, which WooCommerce counts at checkout.
	 *
	 * @param \WC_Cart          $cart         Cart object.
	 * @param \WC_Product|false $product      Product object.
	 * @param int               $quantity     Requested quantity.
	 * @param string|false      $existing_key Existing gift line key.
	 *
	 * @return int
	 */
	private function grantable_quantity( $cart, $product, $quantity, $existing_key ) {
		if ( ! $product || $product->is_type( 'variable' ) || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
			return 0;
		}

		$max = (int) $product->get_max_purchase_quantity();

		if ( $max < 0 ) {
			return $quantity;
		}

		$max     -= wc_get_held_stock_quantity( $product, $this->current_order_id() );
		$stock_id = $product->get_stock_managed_by_id();

		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			if ( $cart_item_key !== $existing_key && $cart_item['data']->get_stock_managed_by_id() === $stock_id ) {
				$max -= (int) $cart_item['quantity'];
			}
		}

		return min( $quantity, max( 0, $max ) );
	}

	/**
	 * The customer's own pending order, whose held stock is theirs to use.
	 *
	 * @return int
	 */
	private function current_order_id() {
		$session = WC()->session;

		if ( ! $session ) {
			return 0;
		}

		$order_id = absint( $session->get( 'order_awaiting_payment' ) );

		return $order_id ? $order_id : absint( $session->get( 'store_api_draft_order', 0 ) );
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

		$item = $cart->cart_contents[ $cart_item_key ];

		// Discount from the BOGO baseline (the customer's effective price) so an
		// existing sale price is respected and "You saved" is accurate.
		$baseline = isset( $item[ CartHandler::BOGO_BASELINE_KEY ] )
			? (float) $item[ CartHandler::BOGO_BASELINE_KEY ]
			: (float) $item['data']->get_price();

		if ( Rule::DISCOUNT_FREE === $rule->discount_type ) {
			$item['data']->set_price( 0 );
			$cart->cart_contents[ $cart_item_key ][ CartHandler::BOGO_DISCOUNT_KEY ] = $baseline;
		} else {
			$discount_amount = ( $baseline * $rule->discount_value ) / 100;
			$item['data']->set_price( $baseline - $discount_amount );
			$cart->cart_contents[ $cart_item_key ][ CartHandler::BOGO_DISCOUNT_KEY ] = $discount_amount;
		}
	}
}
