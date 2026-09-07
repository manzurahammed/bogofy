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
