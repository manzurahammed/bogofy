<?php
/**
 * OrderTracker tests.
 *
 * @package Bogofy\Tests\Unit\Orders
 */

namespace {

	// Lightweight stand-ins for the WooCommerce objects OrderTracker touches.
	if ( ! class_exists( 'Bogo_Test_Product' ) ) {
		class Bogo_Test_Product {
			private $regular;
			private $price;

			public function __construct( $regular, $price = null ) {
				$this->regular = $regular;
				$this->price   = null === $price ? $regular : $price;
			}

			public function get_regular_price() {
				return $this->regular;
			}

			public function get_price() {
				return $this->price;
			}
		}
	}

	if ( ! class_exists( 'Bogo_Test_Order_Item' ) ) {
		class Bogo_Test_Order_Item {
			public $meta = array();
			private $quantity;

			public function __construct( $quantity = 1 ) {
				$this->quantity = $quantity;
			}

			public function add_meta_data( $key, $value, $unique = false ) {
				$this->meta[ $key ] = $value;
			}

			public function get_meta( $key ) {
				return isset( $this->meta[ $key ] ) ? $this->meta[ $key ] : '';
			}

			public function get_quantity() {
				return $this->quantity;
			}
		}
	}

	if ( ! class_exists( 'WC_Order' ) ) {
		class WC_Order {
			public $items = array();
			public $meta  = array();
			public $saves = 0;

			public function get_items() {
				return $this->items;
			}

			public function get_meta( $key ) {
				return isset( $this->meta[ $key ] ) ? $this->meta[ $key ] : '';
			}

			public function update_meta_data( $key, $value ) {
				$this->meta[ $key ] = $value;
			}

			public function delete_meta_data( $key ) {
				unset( $this->meta[ $key ] );
			}

			public function save() {
				++$this->saves;
			}
		}
	}
}

namespace Bogofy\Tests\Unit\Orders {

	use Bogofy\Cart\CartHandler;
	use Bogofy\Models\RuleRepository;
	use Bogofy\Orders\OrderTracker;
	use PHPUnit\Framework\TestCase;

	/**
	 * Repository double that records the stat calls instead of touching the DB.
	 */
	class FakeRepo extends RuleRepository {
		public $added   = array();
		public $removed = array();

		public function add_order_stats( $rule_id, $revenue ) {
			$this->added[] = array( (int) $rule_id, (float) $revenue );
		}

		public function remove_order_stats( $rule_id, $revenue ) {
			$this->removed[] = array( (int) $rule_id, (float) $revenue );
		}
	}

	/**
	 * Class OrderTrackerTest
	 */
	class OrderTrackerTest extends TestCase {

		/**
		 * Set up test fixtures.
		 */
		protected function setUp(): void {
			parent::setUp();
			bogo_tests_setup();
		}

		/**
		 * Tear down test fixtures.
		 */
		protected function tearDown(): void {
			bogo_tests_teardown();
			parent::tearDown();
		}

		/**
		 * Build an order item already stamped with a rule + base price.
		 *
		 * @param int   $rule_id  Rule ID.
		 * @param float $base     Base price.
		 * @param int   $quantity Quantity.
		 *
		 * @return \Bogo_Test_Order_Item
		 */
		private function stamped_item( $rule_id, $base, $quantity ) {
			$item = new \Bogo_Test_Order_Item( $quantity );
			$item->add_meta_data( OrderTracker::RULE_ITEM_META, $rule_id );
			$item->add_meta_data( OrderTracker::BASE_ITEM_META, $base );
			return $item;
		}

		/**
		 * persist_line_meta stamps the rule id and base price on a BOGO line item.
		 */
		public function test_persist_line_meta_stamps_rule_and_base_price() {
			$item    = new \Bogo_Test_Order_Item( 2 );
			$values  = array(
				CartHandler::BOGO_RULE_KEY => 4,
				'data'                     => new \Bogo_Test_Product( 25.0 ),
				'quantity'                 => 2,
			);
			$tracker = new OrderTracker( new FakeRepo() );

			$tracker->persist_line_meta( $item, 'key', $values );

			$this->assertSame( 4, $item->get_meta( OrderTracker::RULE_ITEM_META ) );
			$this->assertSame( 25.0, $item->get_meta( OrderTracker::BASE_ITEM_META ) );
		}

		/**
		 * persist_line_meta leaves non-BOGO line items untouched.
		 */
		public function test_persist_line_meta_ignores_non_bogo_item() {
			$item    = new \Bogo_Test_Order_Item( 1 );
			$values  = array(
				'data'     => new \Bogo_Test_Product( 9.0 ),
				'quantity' => 1,
			);
			$tracker = new OrderTracker( new FakeRepo() );

			$tracker->persist_line_meta( $item, 'key', $values );

			$this->assertSame( '', $item->get_meta( OrderTracker::RULE_ITEM_META ) );
		}

		/**
		 * persist_line_meta falls back to the active price when regular price is 0.
		 */
		public function test_persist_line_meta_falls_back_to_price_when_regular_zero() {
			$item    = new \Bogo_Test_Order_Item( 1 );
			$values  = array(
				CartHandler::BOGO_RULE_KEY => 5,
				'data'                     => new \Bogo_Test_Product( 0.0, 15.0 ),
				'quantity'                 => 1,
			);
			$tracker = new OrderTracker( new FakeRepo() );

			$tracker->persist_line_meta( $item, 'key', $values );

			$this->assertSame( 15.0, $item->get_meta( OrderTracker::BASE_ITEM_META ) );
		}

		/**
		 * revenue_by_rule sums base price * quantity per rule, ignoring other lines.
		 */
		public function test_revenue_by_rule_sums_base_price_times_quantity() {
			$order          = new \WC_Order();
			$order->items   = array(
				$this->stamped_item( 4, 25.0, 2 ), // 50
				$this->stamped_item( 7, 10.0, 1 ), // 10
				new \Bogo_Test_Order_Item( 3 ),    // non-BOGO
			);
			$tracker = new OrderTracker( new FakeRepo() );

			$revenue = $tracker->revenue_by_rule( $order );

			$this->assertSame( 50.0, $revenue[4] );
			$this->assertSame( 10.0, $revenue[7] );
			$this->assertCount( 2, $revenue );
		}

		/**
		 * revenue_by_rule returns an empty map when given a non-order.
		 */
		public function test_revenue_by_rule_handles_non_order() {
			$tracker = new OrderTracker( new FakeRepo() );

			$this->assertSame( array(), $tracker->revenue_by_rule( null ) );
		}

		/**
		 * Completing an order folds each rule's base-price revenue into the totals.
		 */
		public function test_records_stats_on_completed() {
			$order        = new \WC_Order();
			$order->items = array(
				$this->stamped_item( 4, 25.0, 2 ),
				$this->stamped_item( 7, 10.0, 1 ),
			);
			$repo    = new FakeRepo();
			$tracker = new OrderTracker( $repo );

			$tracker->on_status_changed( 1, 'processing', 'completed', $order );

			$this->assertCount( 2, $repo->added );
			$this->assertContains( array( 4, 50.0 ), $repo->added );
			$this->assertContains( array( 7, 10.0 ), $repo->added );
			$this->assertSame( 1, $order->get_meta( OrderTracker::RECORDED_META ) );
		}

		/**
		 * A second completion does not double count (guard meta).
		 */
		public function test_completion_is_idempotent() {
			$order          = new \WC_Order();
			$order->items   = array( $this->stamped_item( 4, 25.0, 1 ) );
			$order->meta[ OrderTracker::RECORDED_META ] = 1;
			$repo    = new FakeRepo();
			$tracker = new OrderTracker( $repo );

			$tracker->on_status_changed( 1, 'completed', 'completed', $order );

			$this->assertCount( 0, $repo->added );
		}

		/**
		 * Leaving the completed status rolls the recorded stats back.
		 */
		public function test_rolls_back_when_leaving_completed() {
			$order          = new \WC_Order();
			$order->items   = array( $this->stamped_item( 4, 25.0, 2 ) );
			$order->meta[ OrderTracker::RECORDED_META ] = 1;
			$repo    = new FakeRepo();
			$tracker = new OrderTracker( $repo );

			$tracker->on_status_changed( 1, 'completed', 'refunded', $order );

			$this->assertContains( array( 4, 50.0 ), $repo->removed );
			$this->assertSame( '', $order->get_meta( OrderTracker::RECORDED_META ) );
		}

		/**
		 * Rollback is a no-op if the order was never recorded.
		 */
		public function test_rollback_noop_when_not_recorded() {
			$order        = new \WC_Order();
			$order->items = array( $this->stamped_item( 4, 25.0, 2 ) );
			$repo    = new FakeRepo();
			$tracker = new OrderTracker( $repo );

			$tracker->on_status_changed( 1, 'completed', 'cancelled', $order );

			$this->assertCount( 0, $repo->removed );
		}
	}
}
