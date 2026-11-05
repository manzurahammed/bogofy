<?php
/**
 * CartDisplay tests.
 *
 * @package Bogofy\Tests\Unit\Frontend
 */

namespace Bogofy\Tests\Unit\Frontend;

use Bogofy\Cart\CartHandler;
use Bogofy\Frontend\CartDisplay;
use Bogofy\Models\Rule;
use Bogofy\Models\RuleRepository;
use Brain\Monkey;
use PHPUnit\Framework\TestCase;

/**
 * Class CartDisplayTest
 */
class CartDisplayTest extends TestCase {

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
	 * Build a CartDisplay whose repository returns the given rule.
	 *
	 * @param Rule|null $rule Rule returned by the repository.
	 *
	 * @return CartDisplay
	 */
	private function display_with_rule( $rule ) {
		$repo = $this->createMock( RuleRepository::class );
		$repo->method( 'get' )->willReturn( $rule );
		return new CartDisplay( $repo );
	}

	/**
	 * A trigger-product rule adds one gift note naming the rule and the trigger.
	 */
	public function test_adds_gift_note_for_free_item() {
		Monkey\Functions\when( 'wc_get_product' )->justReturn(
			new class() {
				public function get_name() {
					return 'Trigger Product';
				}
			}
		);

		$rule                  = new Rule();
		$rule->title           = 'My Rule';
		$rule->apply_to        = Rule::APPLY_SPECIFIC_PRODUCTS;
		$rule->buy_product_ids = array( 10 );

		$this->stub_cart_with_paid_product( 10 );

		$cart_item = array(
			CartHandler::BOGO_ITEM_KEY => true,
			CartHandler::BOGO_RULE_KEY => 3,
		);

		$out = $this->display_with_rule( $rule )->add_item_data( array(), $cart_item );

		$this->assertCount( 1, $out );
		$this->assertStringContainsString( 'My Rule', $out[0]['value'] );
		$this->assertStringContainsString( 'Trigger Product', $out[0]['value'] );
		$this->assertStringContainsString( '<br>', $out[0]['value'] );
	}

	/**
	 * The note names the product in the cart, not the rule's first product.
	 */
	public function test_gift_note_names_the_product_that_was_bought() {
		Monkey\Functions\when( 'wc_get_product' )->alias(
			function ( $product_id ) {
				return new class( $product_id ) {
					private $id;

					public function __construct( $id ) {
						$this->id = $id;
					}

					public function get_name() {
						return 'Product ' . $this->id;
					}
				};
			}
		);

		$rule                  = new Rule();
		$rule->title           = 'My Rule';
		$rule->apply_to        = Rule::APPLY_SPECIFIC_PRODUCTS;
		$rule->buy_product_ids = array( 10, 20 );

		$this->stub_cart_with_paid_product( 20 );

		$out = $this->display_with_rule( $rule )->add_item_data(
			array(),
			array(
				CartHandler::BOGO_ITEM_KEY => true,
				CartHandler::BOGO_RULE_KEY => 3,
			)
		);

		$this->assertStringContainsString( 'Product 20', $out[0]['value'] );
		$this->assertStringNotContainsString( 'Product 10', $out[0]['value'] );
	}

	/**
	 * Stub a cart holding one paid line.
	 *
	 * @param int $product_id Product ID.
	 *
	 * @return void
	 */
	private function stub_cart_with_paid_product( $product_id ) {
		$cart = new class( $product_id ) {
			private $product_id;

			public function __construct( $product_id ) {
				$this->product_id = $product_id;
			}

			public function get_cart() {
				return array(
					'paid' => array(
						'product_id'   => $this->product_id,
						'variation_id' => 0,
					),
				);
			}
		};

		Monkey\Functions\when( 'WC' )->justReturn( (object) array( 'cart' => $cart ) );
	}

	/**
	 * When the cart-gift setting is off, nothing is added (default cart design).
	 */
	public function test_returns_input_unchanged_when_setting_off() {
		Monkey\Functions\when( 'get_option' )->alias(
			function ( $key, $default_value = array() ) {
				return 'bogo_settings' === $key
					? array(
						'enabled'        => true,
						'show_cart_gift' => false,
					)
					: $default_value;
			}
		);

		$cart_item = array( CartHandler::BOGO_ITEM_KEY => true );

		$out = $this->display_with_rule( new Rule() )->add_item_data( array(), $cart_item );

		$this->assertSame( array(), $out );
	}

	/**
	 * Non-BOGO items are left untouched.
	 */
	public function test_ignores_non_bogo_item() {
		$existing = array( 'existing' => 'data' );

		$out = $this->display_with_rule( new Rule() )->add_item_data( $existing, array() );

		$this->assertSame( $existing, $out );
	}
}
