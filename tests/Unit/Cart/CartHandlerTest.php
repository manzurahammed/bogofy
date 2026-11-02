<?php
/**
 * CartHandler tests.
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
}

namespace Bogofy\Tests\Unit\Cart {

	use Bogofy\Cart\CartHandler;
	use Bogofy\Cart\DiscountApplier;
	use Bogofy\Cart\EligibilityChecker;
	use Bogofy\Cart\FreeItemManager;
	use Bogofy\Models\RuleRepository;
	use Brain\Monkey\Functions;
	use Mockery;
	use PHPUnit\Framework\TestCase;

	/**
	 * Class CartHandlerTest
	 */
	class CartHandlerTest extends TestCase {

		/**
		 * Set up test fixtures.
		 */
		protected function setUp(): void {
			parent::setUp();
			bogo_tests_setup();
			Functions\when( 'is_admin' )->justReturn( false );
		}

		/**
		 * Tear down test fixtures.
		 */
		protected function tearDown(): void {
			Mockery::close();
			bogo_tests_teardown();
			parent::tearDown();
		}

		/**
		 * Build a handler backed by a repository returning the given active rules.
		 *
		 * @param array $rules Active rules.
		 *
		 * @return CartHandler
		 */
		private function make_handler( $rules = array() ) {
			$repository = Mockery::mock( RuleRepository::class );
			$repository->shouldReceive( 'get_active_rules' )->andReturn( $rules );

			$checker = new EligibilityChecker();
			$manager = new FreeItemManager();

			return new CartHandler( $repository, $checker, new DiscountApplier( $checker, $manager ), $manager );
		}

		/**
		 * Turning the plugin off must not leave gifts in the cart at full price.
		 */
		public function test_sync_removes_gifts_when_plugin_disabled() {
			Functions\when( 'get_option' )->justReturn( array( 'enabled' => false ) );

			$cart                = new \WC_Cart();
			$cart->cart_contents = array(
				'gift'   => array(
					CartHandler::BOGO_ITEM_KEY => true,
					CartHandler::BOGO_RULE_KEY => 5,
				),
				'normal' => array( 'product_id' => 99 ),
			);

			Functions\when( 'WC' )->justReturn( (object) array( 'cart' => $cart ) );

			$this->make_handler()->sync_free_items();

			$this->assertArrayNotHasKey( 'gift', $cart->cart_contents );
			$this->assertArrayHasKey( 'normal', $cart->cart_contents );
		}

		/**
		 * The baseline must not survive in the session, or a customer keeps paying
		 * an old price after the product price changes.
		 */
		public function test_session_item_drops_stale_baseline() {
			$item = $this->make_handler()->clean_session_item(
				array(
					'data'                         => new \Bogo_Fake_Priced_Product( 50 ),
					CartHandler::BOGO_BASELINE_KEY => 10.0,
					CartHandler::BOGO_DISCOUNT_KEY => 5.0,
				)
			);

			$this->assertArrayNotHasKey( CartHandler::BOGO_BASELINE_KEY, $item );
			$this->assertArrayNotHasKey( CartHandler::BOGO_DISCOUNT_KEY, $item );
			$this->assertArrayHasKey( 'data', $item );
		}

		/**
		 * A paid line claimed by a discount rule earlier must not stay attributed
		 * to that rule once the rule no longer discounts it.
		 */
		public function test_totals_clear_stale_rule_from_paid_lines() {
			$cart                = new \WC_Cart();
			$cart->cart_contents = array(
				'paid' => array(
					'product_id'               => 1,
					'variation_id'             => 0,
					'quantity'                 => 1,
					'data'                     => new \Bogo_Fake_Priced_Product( 20 ),
					CartHandler::BOGO_RULE_KEY => 5,
				),
			);

			$this->make_handler()->apply_bogo_prices( $cart );

			$this->assertArrayNotHasKey( CartHandler::BOGO_RULE_KEY, $cart->cart_contents['paid'] );
		}

		/**
		 * WooCommerce uses the return value of this filter to recalculate totals.
		 */
		public function test_on_cart_updated_returns_filter_value() {
			Functions\when( 'WC' )->justReturn( (object) array( 'cart' => null ) );

			$this->assertTrue( $this->make_handler()->on_cart_updated( true ) );
			$this->assertFalse( $this->make_handler()->on_cart_updated( false ) );
		}

		/**
		 * Gift quantities are locked in the block cart, as in the classic cart.
		 */
		public function test_store_api_locks_gift_quantity() {
			$handler = $this->make_handler();

			$this->assertFalse( $handler->filter_quantity_editable( true, null, array( CartHandler::BOGO_ITEM_KEY => true ) ) );
			$this->assertTrue( $handler->filter_quantity_editable( true, null, array( 'product_id' => 1 ) ) );
			$this->assertTrue( $handler->filter_quantity_editable( true ) );
		}
	}
}
