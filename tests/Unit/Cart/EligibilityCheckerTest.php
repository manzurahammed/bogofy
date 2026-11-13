<?php
/**
 * EligibilityChecker cart condition tests.
 *
 * @package Bogofy\Tests\Unit\Cart
 */

namespace {

	if ( ! class_exists( 'WC_Cart' ) ) {
		class WC_Cart {
			public $cart_contents;

			public function __construct( $items = array() ) {
				$this->cart_contents = $items;
			}

			public function get_cart() {
				return $this->cart_contents;
			}

			public function remove_cart_item( $key ) {
				unset( $this->cart_contents[ $key ] );
				return true;
			}
		}
	}

	if ( ! class_exists( 'Bogo_Fake_Sale_Product' ) ) {
		/**
		 * Cart line product that knows whether it is on sale.
		 */
		class Bogo_Fake_Sale_Product extends Bogo_Fake_Priced_Product {
			public $on_sale;

			public function __construct( $price, $on_sale = false ) {
				parent::__construct( $price );
				$this->on_sale = $on_sale;
			}

			public function is_on_sale() {
				return $this->on_sale;
			}
		}
	}
}

namespace Bogofy\Tests\Unit\Cart {

	use Bogofy\Cart\CartHandler;
	use Bogofy\Cart\EligibilityChecker;
	use Bogofy\Models\Rule;
	use Brain\Monkey\Functions;
	use PHPUnit\Framework\TestCase;

	/**
	 * Class EligibilityCheckerTest
	 */
	class EligibilityCheckerTest extends TestCase {

		/**
		 * Set up test fixtures.
		 */
		protected function setUp(): void {
			parent::setUp();
			bogo_tests_setup();
			Functions\when( 'is_wp_error' )->justReturn( false );
			Functions\when( 'wc_get_price_decimals' )->justReturn( 2 );
			Functions\when( 'wp_get_post_terms' )->alias(
				static function ( $product_id ) {
					// Product 30 sits in category 7; everything else in category 1.
					return 30 === (int) $product_id ? array( 7 ) : array( 1 );
				}
			);
		}

		/**
		 * Tear down test fixtures.
		 */
		protected function tearDown(): void {
			bogo_tests_teardown();
			parent::tearDown();
		}

		/**
		 * Build an "all products" rule with the given conditions.
		 *
		 * @param array $conditions Condition overrides.
		 *
		 * @return Rule
		 */
		private function rule( array $conditions = array() ) {
			return Rule::from_request(
				array(
					'title'      => 'Rule',
					'apply_to'   => Rule::APPLY_ALL_PRODUCTS,
					'conditions' => $conditions,
				)
			);
		}

		/**
		 * Build a cart line.
		 *
		 * @param int   $id       Product ID.
		 * @param int   $quantity Quantity.
		 * @param float $price    Price.
		 * @param bool  $on_sale  Whether the product is on sale.
		 *
		 * @return array
		 */
		private function line( $id, $quantity, $price, $on_sale = false ) {
			return array(
				'product_id'   => $id,
				'variation_id' => 0,
				'quantity'     => $quantity,
				'data'         => new \Bogo_Fake_Sale_Product( $price, $on_sale ),
			);
		}

		/**
		 * The rule grants nothing until the cart reaches the minimum subtotal.
		 */
		public function test_min_subtotal_blocks_until_reached() {
			$checker = new EligibilityChecker();
			$rule    = $this->rule( array( 'min_subtotal' => 50 ) );

			$cart = new \WC_Cart( array( 'a' => $this->line( 10, 2, 20 ) ) );
			$this->assertSame( array(), $checker->get_eligible_items( $cart, $rule ) );

			$cart = new \WC_Cart( array( 'a' => $this->line( 10, 3, 20 ) ) );
			$this->assertArrayHasKey( 'a', $checker->get_eligible_items( $cart, $rule ) );
		}

		/**
		 * The subtotal is taken before BOGO discounts and ignores gift lines.
		 */
		public function test_min_subtotal_uses_baseline_and_skips_gift_lines() {
			$checker = new EligibilityChecker();
			$rule    = $this->rule( array( 'min_subtotal' => 50 ) );

			// Discounted to 10 by a rule, but its baseline (pre-BOGO) price is 30.
			$paid                                   = $this->line( 10, 2, 10 );
			$paid[ CartHandler::BOGO_BASELINE_KEY ] = 30.0;

			$gift                               = $this->line( 11, 5, 100 );
			$gift[ CartHandler::BOGO_ITEM_KEY ] = true;

			$cart = new \WC_Cart( array( 'paid' => $paid ) );
			$this->assertTrue( $checker->meets_cart_minimums( $cart, $rule ) );

			$paid[ CartHandler::BOGO_BASELINE_KEY ] = 20.0;
			$cart                                   = new \WC_Cart(
				array(
					'paid' => $paid,
					'gift' => $gift,
				)
			);
			$this->assertFalse( $checker->meets_cart_minimums( $cart, $rule ) );
		}

		/**
		 * A cart that exactly meets a decimal minimum passes, despite float drift.
		 */
		public function test_min_subtotal_exact_decimal_threshold_passes() {
			$checker = new EligibilityChecker();
			$cart    = new \WC_Cart( array( 'a' => $this->line( 10, 3, 0.30 ) ) );

			// 3 * 0.30 is 0.8999999999999999 as a float.
			$this->assertTrue( $checker->meets_cart_minimums( $cart, $this->rule( array( 'min_subtotal' => 0.90 ) ) ) );
			$this->assertFalse( $checker->meets_cart_minimums( $cart, $this->rule( array( 'min_subtotal' => 0.91 ) ) ) );
		}

		/**
		 * The rule grants nothing until the cart holds the minimum item count.
		 */
		public function test_min_items_blocks_until_reached() {
			$checker = new EligibilityChecker();
			$rule    = $this->rule( array( 'min_items' => 3 ) );

			$cart = new \WC_Cart(
				array(
					'a' => $this->line( 10, 1, 5 ),
					'b' => $this->line( 12, 1, 5 ),
				)
			);
			$this->assertSame( array(), $checker->get_eligible_items( $cart, $rule ) );

			$cart->cart_contents['b']['quantity'] = 2;
			$this->assertCount( 2, $checker->get_eligible_items( $cart, $rule ) );
		}

		/**
		 * An excluded product neither qualifies nor counts toward the minimums.
		 */
		public function test_excluded_product_never_counts_or_qualifies() {
			$checker = new EligibilityChecker();
			$rule    = $this->rule(
				array(
					'min_items'            => 2,
					'excluded_product_ids' => array( 20 ),
				)
			);

			$cart = new \WC_Cart(
				array(
					'a' => $this->line( 10, 1, 5 ),
					'x' => $this->line( 20, 5, 5 ),
				)
			);
			$this->assertSame( array(), $checker->get_eligible_items( $cart, $rule ) );

			$cart->cart_contents['a']['quantity'] = 2;
			$eligible                             = $checker->get_eligible_items( $cart, $rule );
			$this->assertArrayHasKey( 'a', $eligible );
			$this->assertArrayNotHasKey( 'x', $eligible );
		}

		/**
		 * Excluding a parent product also excludes its variations.
		 */
		public function test_excluded_parent_excludes_its_variations() {
			$rule = $this->rule( array( 'excluded_product_ids' => array( 20 ) ) );

			$this->assertFalse( ( new EligibilityChecker() )->is_product_eligible( 21, 20, $rule ) );
		}

		/**
		 * Products in an excluded category never qualify.
		 */
		public function test_excluded_category_never_qualifies() {
			$checker = new EligibilityChecker();
			$rule    = $this->rule( array( 'excluded_category_ids' => array( 7 ) ) );

			$cart = new \WC_Cart(
				array(
					'a' => $this->line( 10, 1, 5 ),
					'c' => $this->line( 30, 1, 5 ),
				)
			);

			$this->assertSame( array( 'a' ), array_keys( $checker->get_eligible_items( $cart, $rule ) ) );
		}

		/**
		 * "Exclude sale items" skips products that are on sale.
		 */
		public function test_sale_items_are_skipped_when_excluded() {
			$checker = new EligibilityChecker();
			$cart    = new \WC_Cart(
				array(
					'a'    => $this->line( 10, 1, 5 ),
					'sale' => $this->line( 12, 1, 5, true ),
				)
			);

			$this->assertCount( 2, $checker->get_eligible_items( $cart, $this->rule() ) );

			$eligible = $checker->get_eligible_items( $cart, $this->rule( array( 'exclude_sale_items' => true ) ) );
			$this->assertSame( array( 'a' ), array_keys( $eligible ) );
		}

		/**
		 * A rule with no conditions (a legacy row) behaves as before.
		 */
		public function test_rule_without_conditions_is_unchanged() {
			$checker = new EligibilityChecker();
			$rule    = Rule::from_db(
				array(
					'title'    => 'Legacy',
					'apply_to' => Rule::APPLY_ALL_PRODUCTS,
				)
			);

			$cart = new \WC_Cart( array( 'a' => $this->line( 10, 1, 1, true ) ) );

			$this->assertArrayHasKey( 'a', $checker->get_eligible_items( $cart, $rule ) );
		}
	}
}
