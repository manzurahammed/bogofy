<?php
/**
 * CartNudges tests.
 *
 * @package Bogofy\Tests\Unit\Frontend
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
		}
	}
}

namespace Bogofy\Tests\Unit\Frontend {

	use Bogofy\Cart\CartHandler;
	use Bogofy\Cart\Contracts\FreeItemManagerInterface;
	use Bogofy\Cart\EligibilityChecker;
	use Bogofy\Frontend\CartNudges;
	use Bogofy\Models\Rule;
	use Bogofy\Models\RuleRepository;
	use Brain\Monkey\Filters;
	use Brain\Monkey\Functions;
	use PHPUnit\Framework\TestCase;

	/**
	 * Class CartNudgesTest
	 */
	class CartNudgesTest extends TestCase {

		/**
		 * Gift product IDs the free item manager cannot supply.
		 *
		 * @var int[]
		 */
		private $unavailable = array();

		/**
		 * Units the store can grant, by product ID. Unlisted products are unlimited.
		 *
		 * @var array<int,int>
		 */
		private $stock = array();

		/**
		 * Set up test fixtures.
		 */
		protected function setUp(): void {
			parent::setUp();
			bogo_tests_setup();
			Functions\when( '_n' )->alias(
				function ( $single, $plural, $number ) {
					return 1 === $number ? $single : $plural;
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
		 * Build nudges backed by a repository returning the given rules.
		 *
		 * @param Rule ...$rules Active rules.
		 *
		 * @return CartNudges
		 */
		private function nudges( Rule ...$rules ) {
			$repo = $this->createMock( RuleRepository::class );
			$repo->method( 'get_active_rules' )->willReturn( $rules );

			$free_items = $this->createMock( FreeItemManagerInterface::class );
			$free_items->method( 'can_supply' )->willReturnCallback(
				function ( $cart, $product_id ) {
					return ! in_array( $product_id, $this->unavailable, true );
				}
			);
			$free_items->method( 'supplyable' )->willReturnCallback(
				function ( $cart, $product_id, $quantity ) {
					if ( in_array( $product_id, $this->unavailable, true ) ) {
						return 0;
					}

					return min( $quantity, $this->stock[ $product_id ] ?? $quantity );
				}
			);

			return new CartNudges( $repo, new EligibilityChecker(), $free_items );
		}

		/**
		 * A "buy 3 get 1" rule on all products.
		 *
		 * @param string $type Rule type.
		 *
		 * @return Rule
		 */
		private function rule( $type = Rule::TYPE_BUY_X_GET_X ) {
			$rule                 = new Rule();
			$rule->rule_type      = $type;
			$rule->apply_to       = Rule::APPLY_ALL_PRODUCTS;
			$rule->buy_quantity   = 3;
			$rule->free_quantity  = 1;
			$rule->discount_value = 50;

			return $rule;
		}

		/**
		 * A cart holding one paid line of the given quantity.
		 *
		 * @param int $quantity Line quantity.
		 *
		 * @return \WC_Cart
		 */
		private function cart( $quantity ) {
			return new \WC_Cart(
				array(
					'a' => array(
						'product_id'   => 10,
						'variation_id' => 0,
						'quantity'     => $quantity,
					),
				)
			);
		}

		/**
		 * Store the given settings overrides.
		 *
		 * @param array $settings Stored settings.
		 */
		private function stub_settings( $settings ) {
			Functions\when( 'get_option' )->justReturn( $settings );
		}

		/**
		 * The default message fills the placeholders and pluralises.
		 */
		public function test_default_message_for_partial_set() {
			$this->assertSame(
				array( 'Add 2 more items to get 1 free!' ),
				$this->nudges( $this->rule() )->get_messages( $this->cart( 1 ) )
			);
			$this->assertSame(
				array( 'Add 1 more item to get 1 free!' ),
				$this->nudges( $this->rule() )->get_messages( $this->cart( 2 ) )
			);
		}

		/**
		 * Discounted rules mention the discount instead of "free".
		 */
		public function test_default_message_for_discounted_rule() {
			$messages = $this->nudges( $this->rule( Rule::TYPE_BUY_X_GET_X_DISCOUNTED ) )->get_messages( $this->cart( 1 ) );

			$this->assertSame( array( 'Add 2 more items to get 1 at 50% off!' ), $messages );
		}

		/**
		 * A percentage reward is not called free, and keeps decimal percentages.
		 */
		public function test_percentage_reward_is_not_called_free() {
			$rule                 = $this->rule();
			$rule->discount_type  = Rule::DISCOUNT_PERCENTAGE;
			$rule->discount_value = 12.5;

			$this->assertSame(
				array( 'Add 2 more items to get 1 at 12.5% off!' ),
				$this->nudges( $rule )->get_messages( $this->cart( 1 ) )
			);
		}

		/**
		 * A gift rule is advertised only while one of its gifts can be supplied.
		 */
		public function test_no_message_when_gift_product_unavailable() {
			$rule                   = $this->rule( Rule::TYPE_BUY_X_GET_Y );
			$rule->free_product_ids = array( 99 );

			$this->assertCount( 1, $this->nudges( $rule )->get_messages( $this->cart( 1 ) ) );

			$this->unavailable = array( 99 );
			$this->assertSame( array(), $this->nudges( $rule )->get_messages( $this->cart( 1 ) ) );
		}

		/**
		 * Any of the rule's gifts can back the nudge, not only the first.
		 */
		public function test_later_gift_backs_nudge_when_first_unavailable() {
			foreach ( array( Rule::TYPE_BUY_X_GET_Y, Rule::TYPE_BUY_CAT_GET_FREE ) as $type ) {
				$rule                   = $this->rule( $type );
				$rule->free_product_ids = array( 98, 99 );

				$this->unavailable = array( 98 );
				$this->assertCount( 1, $this->nudges( $rule )->get_messages( $this->cart( 1 ) ), $type );

				$this->unavailable = array( 98, 99 );
				$this->assertSame( array(), $this->nudges( $rule )->get_messages( $this->cart( 1 ) ), $type );
			}
		}

		/**
		 * An excluded gift is never granted, so it is not advertised.
		 */
		public function test_no_message_when_only_gift_is_excluded() {
			Functions\when( 'wc_get_product' )->justReturn( false );

			$rule                                     = $this->rule( Rule::TYPE_BUY_X_GET_Y );
			$rule->free_product_ids                   = array( 99 );
			$rule->conditions['excluded_product_ids'] = array( 99 );

			$this->assertSame( array(), $this->nudges( $rule )->get_messages( $this->cart( 1 ) ) );

			$rule->free_product_ids = array( 99, 98 );
			$this->assertCount( 1, $this->nudges( $rule )->get_messages( $this->cart( 1 ) ) );
		}

		/**
		 * A gift rule promises no more gifts than the store can grant.
		 */
		public function test_gift_promise_is_capped_at_available_stock() {
			$rule                   = $this->rule( Rule::TYPE_BUY_X_GET_Y );
			$rule->buy_quantity     = 2;
			$rule->free_quantity    = 2;
			$rule->free_product_ids = array( 99 );

			$this->assertSame( array( 'Add 1 more item to get 2 free!' ), $this->nudges( $rule )->get_messages( $this->cart( 1 ) ) );

			$this->stock = array( 99 => 1 );
			$this->assertSame( array( 'Add 1 more item to get 1 free!' ), $this->nudges( $rule )->get_messages( $this->cart( 1 ) ) );
		}

		/**
		 * Gifts already held count against the stock the next set needs.
		 */
		public function test_held_gifts_count_against_the_next_set() {
			$rule                   = $this->rule( Rule::TYPE_BUY_X_GET_Y );
			$rule->id               = 4;
			$rule->buy_quantity     = 2;
			$rule->free_product_ids = array( 99 );

			$cart                      = $this->cart( 3 );
			$cart->cart_contents['g']  = array(
				'product_id'               => 99,
				'variation_id'             => 0,
				'quantity'                 => 1,
				CartHandler::BOGO_ITEM_KEY => true,
				CartHandler::BOGO_RULE_KEY => 4,
			);

			// One unit in stock: the held gift uses it, so the next set can't be met.
			$this->stock = array( 99 => 1 );
			$this->assertSame( array(), $this->nudges( $rule )->get_messages( $cart ) );

			$this->stock = array( 99 => 2 );
			$this->assertSame( array( 'Add 1 more item to get 1 free!' ), $this->nudges( $rule )->get_messages( $cart ) );
		}

		/**
		 * Buy X Get X needs stock for the paid units to add and the free copy.
		 */
		public function test_copy_reward_needs_stock_for_paid_and_free_units() {
			// Buy 3 get 1 with 1 in the cart: 2 paid + 1 free more units are needed.
			$this->stock = array( 10 => 2 );
			$this->assertSame( array(), $this->nudges( $this->rule() )->get_messages( $this->cart( 1 ) ) );

			$this->stock = array( 10 => 3 );
			$this->assertSame( array( 'Add 2 more items to get 1 free!' ), $this->nudges( $this->rule() )->get_messages( $this->cart( 1 ) ) );
		}

		/**
		 * A rule vetoed by `bogofy_rule_is_eligible` is not advertised.
		 */
		public function test_no_message_when_rule_is_vetoed() {
			Filters\expectApplied( 'bogofy_rule_is_eligible' )->once()->andReturn( false );

			$this->assertSame( array(), $this->nudges( $this->rule() )->get_messages( $this->cart( 1 ) ) );
		}

		/**
		 * Nudges never pass projected items through `bogofy_free_quantity`.
		 */
		public function test_free_quantity_filter_is_not_applied() {
			Filters\expectApplied( 'bogofy_free_quantity' )->never();

			$this->assertCount( 1, $this->nudges( $this->rule() )->get_messages( $this->cart( 2 ) ) );
		}

		/**
		 * Overlapping discounted rules: only the highest-priority one is advertised.
		 */
		public function test_overlapping_discounted_rules_advertise_highest_priority_only() {
			$low                 = $this->rule( Rule::TYPE_BUY_X_GET_X_DISCOUNTED );
			$low->priority       = 20;
			$low->discount_value = 10;

			$high           = $this->rule( Rule::TYPE_BUY_X_GET_X_DISCOUNTED );
			$high->priority = 5;

			$this->assertSame(
				array( 'Add 2 more items to get 1 at 50% off!' ),
				$this->nudges( $low, $high )->get_messages( $this->cart( 1 ) )
			);
		}

		/**
		 * A custom template supports the {remaining} and {free_qty} placeholders.
		 */
		public function test_custom_template_placeholders() {
			$this->stub_settings( array( 'cart_nudge_text' => 'Only {remaining} to go for {free_qty} gift' ) );

			$this->assertSame(
				array( 'Only 2 to go for 1 gift' ),
				$this->nudges( $this->rule() )->get_messages( $this->cart( 1 ) )
			);
		}

		/**
		 * No nudge on an exact set boundary.
		 */
		public function test_no_message_for_exact_set() {
			$this->assertSame( array(), $this->nudges( $this->rule() )->get_messages( $this->cart( 3 ) ) );
		}

		/**
		 * The settings toggle turns nudges off.
		 */
		public function test_no_message_when_toggle_off() {
			$this->stub_settings( array( 'show_cart_nudges' => false ) );

			$this->assertSame( array(), $this->nudges( $this->rule() )->get_messages( $this->cart( 1 ) ) );
		}

		/**
		 * The classic cart output escapes the message and uses BEM classes.
		 */
		public function test_render_outputs_escaped_nudge() {
			$nudges = $this->getMockBuilder( CartNudges::class )
				->setConstructorArgs( array( $this->createMock( RuleRepository::class ), new EligibilityChecker(), $this->createMock( FreeItemManagerInterface::class ) ) )
				->onlyMethods( array( 'get_messages' ) )
				->getMock();
			$nudges->method( 'get_messages' )->willReturn( array( '<b>2</b> more' ) );

			ob_start();
			$nudges->render();
			$html = ob_get_clean();

			$this->assertStringContainsString( 'class="bogo-nudge"', $html );
			$this->assertStringContainsString( 'class="bogo-nudge__text">&lt;b&gt;2&lt;/b&gt; more', $html );
		}
	}
}
