<?php
/**
 * Tracks BOGO rule usage on orders.
 *
 * @package Bogofy\Orders
 */

namespace Bogofy\Orders;

use Bogofy\Cart\CartHandler;
use Bogofy\Models\RuleRepository;

/**
 * Class OrderTracker
 */
class OrderTracker {

	/**
	 * Order-item meta key holding the owning rule ID.
	 *
	 * @var string
	 */
	const RULE_ITEM_META = CartHandler::BOGO_RULE_KEY;

	/**
	 * Order-item meta key holding the unit base (regular) price of the item.
	 *
	 * @var string
	 */
	const BASE_ITEM_META = '_bogo_base_price';

	/**
	 * Order meta flag marking that stats have been recorded for the order.
	 *
	 * @var string
	 */
	const RECORDED_META = '_bogo_stats_recorded';

	/**
	 * Rule repository.
	 *
	 * @var RuleRepository
	 */
	private $rules;

	/**
	 * Constructor.
	 *
	 * @param RuleRepository $rules Rule repository.
	 */
	public function __construct( RuleRepository $rules ) {
		$this->rules = $rules;
	}

	/**
	 * Stamp the owning rule and base price onto a BOGO order line item.
	 *
	 * @param \WC_Order_Item_Product $item          Order line item.
	 * @param string                 $cart_item_key Cart item key.
	 * @param array                  $values        Cart item data.
	 *
	 * @return void
	 */
	public function persist_line_meta( $item, $cart_item_key, $values ) {
		if ( empty( $values[ CartHandler::BOGO_RULE_KEY ] ) || empty( $values['data'] ) ) {
			return;
		}

		$product    = $values['data'];
		$base_price = (float) $product->get_regular_price();
		if ( $base_price <= 0 ) {
			$base_price = (float) $product->get_price();
		}

		$item->add_meta_data( self::RULE_ITEM_META, (int) $values[ CartHandler::BOGO_RULE_KEY ], true );
		$item->add_meta_data( self::BASE_ITEM_META, $base_price, true );
	}

	/**
	 * Record or roll back rule stats as the order status changes.
	 *
	 * @param int       $order_id Order ID.
	 * @param string    $from     Previous status.
	 * @param string    $to       New status.
	 * @param \WC_Order $order    Order object.
	 *
	 * @return void
	 */
	public function on_status_changed( $order_id, $from, $to, $order ) {
		if ( 'completed' === $to ) {
			$this->record_order( $order );
		} elseif ( 'completed' === $from ) {
			$this->rollback_order( $order );
		}
	}

	/**
	 * Sum the base-price revenue each rule earned in an order, from its line items.
	 *
	 * @param \WC_Order $order Order object.
	 *
	 * @return array Map of rule_id => base-price revenue.
	 */
	public function revenue_by_rule( $order ) {
		$revenue = array();

		if ( ! $order instanceof \WC_Order ) {
			return $revenue;
		}

		foreach ( $order->get_items() as $item ) {
			$rule_id = (int) $item->get_meta( self::RULE_ITEM_META );
			if ( ! $rule_id ) {
				continue;
			}

			$base_price = (float) $item->get_meta( self::BASE_ITEM_META );
			$quantity   = (int) $item->get_quantity();

			if ( ! isset( $revenue[ $rule_id ] ) ) {
				$revenue[ $rule_id ] = 0.0;
			}
			$revenue[ $rule_id ] += $base_price * $quantity;
		}

		return $revenue;
	}

	/**
	 * Fold an order's per-rule base-price revenue into each rule's running totals.
	 *
	 * @param \WC_Order $order Order object.
	 *
	 * @return void
	 */
	private function record_order( $order ) {
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		// Avoid double counting if the completed hook fires more than once.
		if ( $order->get_meta( self::RECORDED_META ) ) {
			return;
		}

		$revenue = $this->revenue_by_rule( $order );
		if ( empty( $revenue ) ) {
			return;
		}

		foreach ( $revenue as $rule_id => $amount ) {
			$this->rules->add_order_stats( (int) $rule_id, (float) $amount );
		}

		$order->update_meta_data( self::RECORDED_META, 1 );
		$order->save();
	}

	/**
	 * Subtract a previously-recorded order from each rule's running totals.
	 *
	 * @param \WC_Order $order Order object.
	 *
	 * @return void
	 */
	private function rollback_order( $order ) {
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		// Only roll back what was actually recorded.
		if ( ! $order->get_meta( self::RECORDED_META ) ) {
			return;
		}

		foreach ( $this->revenue_by_rule( $order ) as $rule_id => $amount ) {
			$this->rules->remove_order_stats( (int) $rule_id, (float) $amount );
		}

		$order->delete_meta_data( self::RECORDED_META );
		$order->save();
	}
}
