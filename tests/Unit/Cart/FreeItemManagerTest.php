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
		}
	}
}

namespace Bogofy\Tests\Unit\Cart {

	use Bogofy\Cart\CartHandler;
	use Bogofy\Cart\FreeItemManager;
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
	}
}
