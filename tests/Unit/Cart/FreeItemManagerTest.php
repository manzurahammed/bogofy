<?php
/**
 * FreeItemManager tests.
 *
 * @package Bogofy\Tests\Unit\Cart
 */

namespace {

	if ( ! class_exists( 'Bogo_Fake_Cart' ) ) {
		class Bogo_Fake_Cart {
			public $contents;
			public $removed = array();
			public $added   = array();
			public $cart_contents = array();
			public $variation     = array();
			public $add_result    = 'new_key';

			public function __construct( $contents = array() ) {
				$this->contents = $contents;
			}

			public function get_cart() {
				return $this->contents;
			}

			public function remove_cart_item( $key ) {
				$this->removed[] = $key;
				unset( $this->contents[ $key ] );
			}

			public function add_to_cart( $product_id, $quantity, $variation_id = 0, $variation = array() ) {
				$this->added[ $product_id ] = $quantity;
				$this->variation            = $variation;
				return $this->add_result;
			}
		}
	}

	if ( ! class_exists( 'Bogo_Fake_Gift_Product' ) ) {
		class Bogo_Fake_Gift_Product {
			public $in_stock = true;
			public $max      = -1;
			public $type     = 'simple';
			public $stock_id = 7;
			public $attributes = array();

			public function __construct( $stock_id = 7 ) {
				$this->stock_id = $stock_id;
			}

			public function get_parent_id() {
				return 100;
			}

			public function get_variation_attributes() {
				return $this->attributes;
			}

			public function get_stock_managed_by_id() {
				return $this->stock_id;
			}

			public function is_in_stock() {
				return $this->in_stock;
			}

			public function is_purchasable() {
				return true;
			}

			public function is_type( $type ) {
				return $type === $this->type;
			}

			public function get_max_purchase_quantity() {
				return $this->max;
			}
		}
	}
}

namespace Bogofy\Tests\Unit\Cart {

	use Bogofy\Cart\CartHandler;
	use Bogofy\Cart\FreeItemManager;
	use Bogofy\Models\Rule;
	use Brain\Monkey\Functions;
	use PHPUnit\Framework\TestCase;

	/**
	 * Class FreeItemManagerTest
	 */
	class FreeItemManagerTest extends TestCase {

		/**
		 * Set up test fixtures.
		 */
		protected function setUp(): void {
			parent::setUp();
			bogo_tests_setup();

			Functions\when( 'wc_get_notices' )->justReturn( array() );
			Functions\when( 'wc_set_notices' )->justReturn( null );
			Functions\when( 'wc_get_held_stock_quantity' )->justReturn( 0 );
			Functions\when( 'WC' )->justReturn( (object) array( 'session' => null ) );
		}

		/**
		 * Tear down test fixtures.
		 */
		protected function tearDown(): void {
			bogo_tests_teardown();
			parent::tearDown();
		}

		/**
		 * Build a cart with two BOGO gift lines (rules 5 and 7) and one normal line.
		 *
		 * @return \Bogo_Fake_Cart
		 */
		private function make_cart() {
			return new \Bogo_Fake_Cart(
				array(
					'gift_a' => array(
						CartHandler::BOGO_ITEM_KEY => true,
						CartHandler::BOGO_RULE_KEY => 5,
					),
					'gift_b' => array(
						CartHandler::BOGO_ITEM_KEY => true,
						CartHandler::BOGO_RULE_KEY => 7,
					),
					'normal' => array( 'product_id' => 99 ),
				)
			);
		}

		/**
		 * Lines whose rule is not active are removed; active + normal lines stay.
		 */
		public function test_removes_only_inactive_rule_lines() {
			$cart = $this->make_cart();

			( new FreeItemManager() )->remove_items_for_inactive_rules( $cart, array( 5 ) );

			$this->assertSame( array( 'gift_b' ), $cart->removed );
			$this->assertArrayHasKey( 'gift_a', $cart->contents );
			$this->assertArrayHasKey( 'normal', $cart->contents );
		}

		/**
		 * With no active rules, every BOGO gift line is dropped (normal line stays).
		 */
		public function test_removes_all_gift_lines_when_no_active_rules() {
			$cart = $this->make_cart();

			( new FreeItemManager() )->remove_items_for_inactive_rules( $cart, array() );

			$this->assertContains( 'gift_a', $cart->removed );
			$this->assertContains( 'gift_b', $cart->removed );
			$this->assertArrayHasKey( 'normal', $cart->contents );
		}

		/**
		 * Build a rule with an ID.
		 *
		 * @return Rule
		 */
		private function make_rule() {
			$rule     = new Rule();
			$rule->id = 5;

			return $rule;
		}

		/**
		 * A gift that sold out is dropped, so it cannot block checkout.
		 */
		public function test_out_of_stock_gift_line_is_removed() {
			$product           = new \Bogo_Fake_Gift_Product();
			$product->in_stock = false;
			Functions\when( 'wc_get_product' )->justReturn( $product );

			$cart = new \Bogo_Fake_Cart(
				array(
					'gift' => array(
						'product_id'               => 7,
						'variation_id'             => 0,
						'quantity'                 => 1,
						CartHandler::BOGO_ITEM_KEY => true,
						CartHandler::BOGO_RULE_KEY => 5,
					),
				)
			);

			$this->assertFalse( ( new FreeItemManager() )->add_free_item( $cart, 7, 1, $this->make_rule() ) );
			$this->assertSame( array( 'gift' ), $cart->removed );
		}

		/**
		 * The gift shares stock with paid lines of the same product.
		 */
		public function test_gift_quantity_is_capped_by_remaining_stock() {
			$product      = new \Bogo_Fake_Gift_Product();
			$product->max = 4;
			Functions\when( 'wc_get_product' )->justReturn( $product );

			$cart = new \Bogo_Fake_Cart(
				array(
					'paid' => array(
						'product_id'   => 7,
						'variation_id' => 0,
						'quantity'     => 3,
						'data'         => new \Bogo_Fake_Gift_Product( 7 ),
					),
				)
			);

			( new FreeItemManager() )->add_free_item( $cart, 7, 3, $this->make_rule() );

			$this->assertSame( array( 7 => 1 ), $cart->added );
		}

		/**
		 * Sibling variations draw on the parent's stock, so a paid variation counts
		 * against a gift of a different variation.
		 */
		public function test_gift_quantity_counts_sibling_variations_sharing_parent_stock() {
			$gift      = new \Bogo_Fake_Gift_Product( 100 );
			$gift->max = 4;
			Functions\when( 'wc_get_product' )->justReturn( $gift );

			$cart = new \Bogo_Fake_Cart(
				array(
					'paid_sibling' => array(
						'product_id'   => 100,
						'variation_id' => 101,
						'quantity'     => 2,
						'data'         => new \Bogo_Fake_Gift_Product( 100 ),
					),
					'unrelated'    => array(
						'product_id'   => 200,
						'variation_id' => 0,
						'quantity'     => 5,
						'data'         => new \Bogo_Fake_Gift_Product( 200 ),
					),
				)
			);

			( new FreeItemManager() )->add_free_item( $cart, 102, 3, $this->make_rule() );

			$this->assertSame( array( 102 => 2 ), $cart->added );
		}

		/**
		 * Stock held for another customer's order is unavailable at checkout.
		 */
		public function test_gift_quantity_allows_for_held_stock() {
			$product      = new \Bogo_Fake_Gift_Product();
			$product->max = 3;
			Functions\when( 'wc_get_product' )->justReturn( $product );
			Functions\when( 'wc_get_held_stock_quantity' )->justReturn( 2 );

			$cart = new \Bogo_Fake_Cart();

			( new FreeItemManager() )->add_free_item( $cart, 7, 2, $this->make_rule() );

			$this->assertSame( array( 7 => 1 ), $cart->added );
		}

		/**
		 * The customer's own pending order is excluded from held stock.
		 */
		public function test_held_stock_excludes_the_customers_own_order() {
			$product      = new \Bogo_Fake_Gift_Product();
			$product->max = 3;
			$excluded     = null;
			$session      = new class() {
				public function get( $key, $fallback = null ) {
					return 'order_awaiting_payment' === $key ? 55 : $fallback;
				}
			};

			Functions\when( 'wc_get_product' )->justReturn( $product );
			Functions\when( 'WC' )->justReturn( (object) array( 'session' => $session ) );
			Functions\when( 'absint' )->alias( 'intval' );
			Functions\when( 'wc_get_held_stock_quantity' )->alias(
				function ( $held_product, $order_id ) use ( &$excluded ) {
					$excluded = $order_id;
					return 0;
				}
			);

			( new FreeItemManager() )->add_free_item( new \Bogo_Fake_Cart(), 7, 1, $this->make_rule() );

			$this->assertSame( 55, $excluded );
		}

		/**
		 * An "Any" variation keeps the attributes chosen on the purchased line.
		 */
		public function test_any_variation_uses_the_chosen_attributes() {
			$product             = new \Bogo_Fake_Gift_Product();
			$product->type       = 'variation';
			$product->attributes = array( 'attribute_size' => '' );
			Functions\when( 'wc_get_product' )->justReturn( $product );

			$cart = new \Bogo_Fake_Cart();

			( new FreeItemManager() )->add_free_item( $cart, 7, 1, $this->make_rule(), array( 'attribute_size' => 'L' ) );

			$this->assertSame( array( 100 => 1 ), $cart->added );
			$this->assertSame( array( 'attribute_size' => 'L' ), $cart->variation );
		}

		/**
		 * With no chosen value the gift is skipped, not sent to WooCommerce to fail.
		 */
		public function test_any_variation_without_a_chosen_value_is_skipped() {
			$product             = new \Bogo_Fake_Gift_Product();
			$product->type       = 'variation';
			$product->attributes = array( 'attribute_size' => '' );
			Functions\when( 'wc_get_product' )->justReturn( $product );

			$cart = new \Bogo_Fake_Cart();

			$this->assertFalse( ( new FreeItemManager() )->add_free_item( $cart, 7, 1, $this->make_rule() ) );
			$this->assertSame( array(), $cart->added );
		}

		/**
		 * Swapping a gift for a sibling variation reuses the replaced gift's stock.
		 */
		public function test_replacement_gift_reuses_the_replaced_gift_stock() {
			$gift      = new \Bogo_Fake_Gift_Product( 100 );
			$gift->max = 1;
			Functions\when( 'wc_get_product' )->justReturn( $gift );

			$old_gift = array(
				'product_id'               => 100,
				'variation_id'             => 101,
				'quantity'                 => 1,
				'data'                     => new \Bogo_Fake_Gift_Product( 100 ),
				CartHandler::BOGO_ITEM_KEY => true,
				CartHandler::BOGO_RULE_KEY => 5,
			);

			// Counted against the new gift, the last unit is already taken.
			$blocked = new \Bogo_Fake_Cart( array( 'old_gift' => $old_gift ) );
			$this->assertFalse( ( new FreeItemManager() )->add_free_item( $blocked, 102, 1, $this->make_rule() ) );
			$this->assertSame( array(), $blocked->removed );

			$cart = new \Bogo_Fake_Cart( array( 'old_gift' => $old_gift ) );
			( new FreeItemManager() )->add_free_item( $cart, 102, 1, $this->make_rule(), array(), array( 'old_gift' ) );

			$this->assertSame( array( 102 => 1 ), $cart->added );
		}

		/**
		 * A gift whose stock paid lines already use can't be offered.
		 */
		public function test_can_supply_allows_for_stock_used_by_other_lines() {
			$gift      = new \Bogo_Fake_Gift_Product( 7 );
			$gift->max = 2;
			Functions\when( 'wc_get_product' )->justReturn( $gift );

			$cart = new \Bogo_Fake_Cart(
				array(
					'paid' => array(
						'product_id'   => 7,
						'variation_id' => 0,
						'quantity'     => 2,
						'data'         => new \Bogo_Fake_Gift_Product( 7 ),
					),
				)
			);

			$this->assertFalse( ( new FreeItemManager() )->can_supply( $cart, 7 ) );

			$gift->max = 3;
			$this->assertTrue( ( new FreeItemManager() )->can_supply( $cart, 7 ) );
		}

		/**
		 * An "Any" variation can't be offered, since it would always be rejected.
		 */
		public function test_can_supply_rejects_any_variation_without_a_value() {
			$gift             = new \Bogo_Fake_Gift_Product();
			$gift->type       = 'variation';
			$gift->attributes = array( 'attribute_size' => '' );
			Functions\when( 'wc_get_product' )->justReturn( $gift );

			$this->assertFalse( ( new FreeItemManager() )->can_supply( new \Bogo_Fake_Cart(), 7 ) );

			$gift->attributes = array( 'attribute_size' => 'L' );
			$this->assertTrue( ( new FreeItemManager() )->can_supply( new \Bogo_Fake_Cart(), 7 ) );
		}

		/**
		 * The error from a failed add must not reach checkout.
		 */
		public function test_failed_add_restores_the_notices() {
			$before = array( 'success' => array( array( 'notice' => 'Added' ) ) );
			$saved  = null;

			Functions\when( 'wc_get_product' )->justReturn( new \Bogo_Fake_Gift_Product() );
			Functions\when( 'wc_get_notices' )->justReturn( $before );
			Functions\when( 'wc_set_notices' )->alias(
				function ( $notices ) use ( &$saved ) {
					$saved = $notices;
				}
			);

			$cart             = new \Bogo_Fake_Cart();
			$cart->add_result = false;

			$this->assertFalse( ( new FreeItemManager() )->add_free_item( $cart, 7, 1, $this->make_rule() ) );
			$this->assertSame( $before, $saved );
		}

		/**
		 * A variable parent cannot be added without a variation, so it is skipped.
		 */
		public function test_variable_parent_is_not_added() {
			$product       = new \Bogo_Fake_Gift_Product();
			$product->type = 'variable';
			Functions\when( 'wc_get_product' )->justReturn( $product );

			$cart = new \Bogo_Fake_Cart();

			$this->assertFalse( ( new FreeItemManager() )->add_free_item( $cart, 7, 1, $this->make_rule() ) );
			$this->assertSame( array(), $cart->added );
		}
	}
}
