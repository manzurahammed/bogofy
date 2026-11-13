<?php
/**
 * Settings tests.
 *
 * @package Bogofy\Tests\Unit\Admin
 */

namespace Bogofy\Tests\Unit\Admin;

use Bogofy\Admin\Settings;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * Class SettingsTest
 */
class SettingsTest extends TestCase {

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
	 * The cart-gift toggle is a default and defaults to on.
	 */
	public function test_defaults_include_show_cart_gift() {
		$all = Settings::get_all();

		$this->assertArrayHasKey( 'show_cart_gift', $all );
		$this->assertTrue( $all['show_cart_gift'] );
	}

	/**
	 * sanitize() casts the cart-gift toggle to a boolean.
	 */
	public function test_sanitize_casts_show_cart_gift_to_bool() {
		$this->assertTrue( Settings::sanitize( array( 'show_cart_gift' => '1' ) )['show_cart_gift'] );
		$this->assertFalse( Settings::sanitize( array( 'show_cart_gift' => 0 ) )['show_cart_gift'] );
	}

	/**
	 * The cart-nudge toggle defaults to on, with an empty (use default) message.
	 */
	public function test_defaults_include_cart_nudges() {
		$all = Settings::get_all();

		$this->assertTrue( $all['show_cart_nudges'] );
		$this->assertSame( '', $all['cart_nudge_text'] );
	}

	/**
	 * sanitize() casts the nudge toggle and strips tags from the nudge text.
	 */
	public function test_sanitize_cart_nudge_settings() {
		$out = Settings::sanitize(
			array(
				'show_cart_nudges' => '0',
				'cart_nudge_text'  => '<b>Add {remaining} more</b>',
			)
		);

		$this->assertFalse( $out['show_cart_nudges'] );
		$this->assertSame( 'Add {remaining} more', $out['cart_nudge_text'] );
	}

	/**
	 * sanitize() drops keys that are not known settings.
	 */
	public function test_sanitize_ignores_unknown_keys() {
		$out = Settings::sanitize( array( 'not_a_setting' => 'x' ) );

		$this->assertArrayNotHasKey( 'not_a_setting', $out );
	}

	/**
	 * Saving identical settings is a success, though update_option returns false.
	 */
	public function test_saving_unchanged_settings_succeeds() {
		$stored = Settings::get_all();

		Functions\when( 'get_option' )->justReturn( $stored );
		Functions\when( 'update_option' )->justReturn( false );
		Functions\when( 'maybe_serialize' )->alias( 'serialize' );

		$this->assertTrue( Settings::update( array( 'enabled' => $stored['enabled'] ) ) );
	}
}
