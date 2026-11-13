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

	use Bogofy\Cart\EligibilityChecker;
	use Bogofy\Frontend\CartNudges;
	use Bogofy\Models\Rule;
	use Bogofy\Models\RuleRepository;
	use Brain\Monkey\Functions;
	use PHPUnit\Framework\TestCase;

	/**
	 * Class CartNudgesTest
	 */
	class CartNudgesTest extends TestCase {

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

			return new CartNudges( $repo, new EligibilityChecker() );
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
		 * A Buy X Get Y gift that cannot be added is not advertised.
		 */
		public function test_no_message_when_gift_product_unavailable() {
			$rule                   = $this->rule( Rule::TYPE_BUY_X_GET_Y );
			$rule->free_product_ids = array( 99 );

			$in_stock = true;
			Functions\when( 'wc_get_product' )->alias(
				function () use ( &$in_stock ) {
					return new class( $in_stock ) {
						private $in_stock;

						public function __construct( $in_stock ) {
							$this->in_stock = $in_stock;
						}

						public function is_purchasable() {
							return true;
						}

						public function is_in_stock() {
							return $this->in_stock;
						}
					};
				}
			);

			$this->assertCount( 1, $this->nudges( $rule )->get_messages( $this->cart( 1 ) ) );

			$in_stock = false;
			$this->assertSame( array(), $this->nudges( $rule )->get_messages( $this->cart( 1 ) ) );
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
				->setConstructorArgs( array( $this->createMock( RuleRepository::class ), new EligibilityChecker() ) )
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
