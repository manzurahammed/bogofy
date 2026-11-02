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

			public function add_to_cart( $product_id, $quantity ) {
				$this->added[ $product_id ] = $quantity;
				return 'new_key';
			}
		}
	}

	if ( ! class_exists( 'Bogo_Fake_Gift_Product' ) ) {
		class Bogo_Fake_Gift_Product {
			public $in_stock = true;
			public $max      = -1;
			public $type     = 'simple';
			public $stock_id = 7;

			public function __construct( $stock_id = 7 ) {
				$this->stock_id = $stock_id;
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
