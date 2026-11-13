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
				if ( ! isset( $this->contents[ $key ] ) ) {
					return false;
				}

				$this->removed[] = $key;
				unset( $this->contents[ $key ] );

				return true;
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
	use Brain\Monkey\Actions;
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

		/**
		 * Adding a new gift line announces it with the gift and its rule.
		 */
		public function test_new_gift_fires_added_action() {
			Functions\when( 'wc_get_product' )->justReturn( new \Bogo_Fake_Gift_Product() );

			$rule = $this->make_rule();

			Actions\expectDone( 'bogofy_gift_added' )->once()->with( 'new_key', 7, 2, $rule );
			Actions\expectDone( 'bogofy_gift_removed' )->never();

			$this->assertSame( 'new_key', ( new FreeItemManager() )->add_free_item( new \Bogo_Fake_Cart(), 7, 2, $rule ) );
		}

		/**
		 * Re-syncing an existing gift line's quantity is not an add.
		 */
		public function test_existing_gift_quantity_change_fires_no_action() {
			Functions\when( 'wc_get_product' )->justReturn( new \Bogo_Fake_Gift_Product() );

			$cart                = new \Bogo_Fake_Cart(
				array(
					'gift' => array(
						'product_id'               => 7,
						'variation_id'             => 0,
						'quantity'                 => 1,
						'data'                     => new \Bogo_Fake_Gift_Product( 99 ),
						CartHandler::BOGO_ITEM_KEY => true,
						CartHandler::BOGO_RULE_KEY => 5,
					),
				)
			);
			$cart->cart_contents = $cart->contents;

			Actions\expectDone( 'bogofy_gift_added' )->never();
			Actions\expectDone( 'bogofy_gift_removed' )->never();

			$this->assertSame( 'gift', ( new FreeItemManager() )->add_free_item( $cart, 7, 3, $this->make_rule() ) );
			$this->assertSame( 3, $cart->cart_contents['gift']['quantity'] );
		}

		/**
		 * A failed add is not announced.
		 */
		public function test_failed_add_fires_no_action() {
			Functions\when( 'wc_get_product' )->justReturn( new \Bogo_Fake_Gift_Product() );

			$cart             = new \Bogo_Fake_Cart();
			$cart->add_result = false;

			Actions\expectDone( 'bogofy_gift_added' )->never();

			$this->assertFalse( ( new FreeItemManager() )->add_free_item( $cart, 7, 1, $this->make_rule() ) );
		}

		/**
		 * Each removed gift line is announced with the rule that granted it.
		 */
		public function test_removed_gifts_fire_removed_action() {
			Actions\expectDone( 'bogofy_gift_removed' )->once()->with( 'gift_b', 7 );

			$cart = $this->make_cart();
			( new FreeItemManager() )->remove_items_for_inactive_rules( $cart, array( 5 ) );

			$this->assertSame( array( 'gift_b' ), $cart->removed );
		}

		/**
		 * Removing a rule's gifts announces only that rule's lines.
		 */
		public function test_remove_rule_items_fires_removed_action() {
			Actions\expectDone( 'bogofy_gift_removed' )->once()->with( 'gift_a', 5 );

			$cart = $this->make_cart();
			( new FreeItemManager() )->remove_rule_items( $cart, 5 );

			$this->assertSame( array( 'gift_a' ), $cart->removed );
		}

		/**
		 * A queued line a listener already removed is not announced by Bogofy.
		 */
		public function test_line_removed_by_a_listener_is_not_announced() {
			$cart = $this->make_cart();

			Actions\expectDone( 'bogofy_gift_removed' )
				->once()
				->with( 'gift_a', 5 )
				->whenHappen(
					function ( $cart_item_key ) use ( $cart ) {
						if ( 'gift_a' === $cart_item_key ) {
							$cart->remove_cart_item( 'gift_b' );
						}
					}
				);

			( new FreeItemManager() )->remove_items_for_inactive_rules( $cart, array() );

			$this->assertSame( array( 'gift_a', 'gift_b' ), $cart->removed );
		}
	}
}
