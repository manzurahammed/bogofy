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
	use Bogofy\Models\Rule;
	use Bogofy\Models\RuleRepository;
	use Brain\Monkey\Actions;
	use Brain\Monkey\Filters;
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
		 * With the plugin off a session claim would credit an order the rule didn't
		 * discount, so it goes, while the gift line keeps its rule until sync drops it.
		 */
		public function test_totals_drop_paid_claims_when_plugin_disabled() {
			Functions\when( 'get_option' )->justReturn( array( 'enabled' => false ) );

			$cart                = new \WC_Cart();
			$cart->cart_contents = array(
				'paid' => array( CartHandler::BOGO_RULE_KEY => 5 ),
				'gift' => array(
					CartHandler::BOGO_ITEM_KEY => true,
					CartHandler::BOGO_RULE_KEY => 5,
				),
			);

			$this->make_handler()->apply_bogo_prices( $cart );

			$this->assertArrayNotHasKey( CartHandler::BOGO_RULE_KEY, $cart->cart_contents['paid'] );
			$this->assertArrayHasKey( CartHandler::BOGO_RULE_KEY, $cart->cart_contents['gift'] );
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
		 * Build an active rule covering product 1.
		 *
		 * @param string $type Rule type.
		 *
		 * @return Rule
		 */
		private function make_rule( $type ) {
			$rule                  = new Rule();
			$rule->id              = 5;
			$rule->rule_type       = $type;
			$rule->apply_to        = Rule::APPLY_SPECIFIC_PRODUCTS;
			$rule->buy_product_ids = array( 1 );
			$rule->buy_quantity    = 1;
			$rule->free_quantity   = 1;
			$rule->discount_value  = 50;

			return $rule;
		}

		/**
		 * A vetoed rule loses the gift it had already granted.
		 */
		public function test_vetoed_rule_has_its_gifts_removed() {
			Functions\when( 'get_option' )->justReturn( array( 'enabled' => true ) );

			$rule                = $this->make_rule( Rule::TYPE_BUY_X_GET_X );
			$cart                = new \WC_Cart();
			$cart->cart_contents = array(
				'paid' => array(
					'product_id'   => 1,
					'variation_id' => 0,
					'quantity'     => 1,
					'data'         => new \Bogo_Fake_Priced_Product( 20 ),
				),
				'gift' => array(
					'product_id'               => 1,
					'variation_id'             => 0,
					'quantity'                 => 1,
					CartHandler::BOGO_ITEM_KEY => true,
					CartHandler::BOGO_RULE_KEY => 5,
				),
			);

			Functions\when( 'WC' )->justReturn( (object) array( 'cart' => $cart ) );

			Filters\expectApplied( 'bogofy_rule_is_eligible' )
				->once()
				->with( true, $rule, $cart )
				->andReturn( false );
			Actions\expectDone( 'bogofy_gift_removed' )->once()->with( 'gift', 5 );

			$this->make_handler( array( $rule ) )->sync_free_items();

			$this->assertArrayNotHasKey( 'gift', $cart->cart_contents );
			$this->assertArrayHasKey( 'paid', $cart->cart_contents );
		}

		/**
		 * A vetoed "Buy X Get X Discounted" rule leaves paid lines at full price.
		 */
		public function test_vetoed_discount_rule_is_not_applied() {
			Functions\when( 'get_option' )->justReturn( array( 'enabled' => true ) );

			$rule                = $this->make_rule( Rule::TYPE_BUY_X_GET_X_DISCOUNTED );
			$product             = new \Bogo_Fake_Priced_Product( 20 );
			$cart                = new \WC_Cart();
			$cart->cart_contents = array(
				'paid' => array(
					'key'          => 'paid',
					'product_id'   => 1,
					'variation_id' => 0,
					'quantity'     => 2,
					'data'         => $product,
				),
			);

			Filters\expectApplied( 'bogofy_rule_is_eligible' )->once()->andReturn( false );

			$this->make_handler( array( $rule ) )->apply_bogo_prices( $cart );

			$this->assertSame( 20.0, (float) $product->get_price() );
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
