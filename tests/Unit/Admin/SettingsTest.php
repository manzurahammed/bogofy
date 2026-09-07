<?php
/**
 * Settings tests.
 *
 * @package Bogofy\Tests\Unit\Admin
 */

namespace Bogofy\Tests\Unit\Admin;

use Bogofy\Admin\Settings;
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
	 * sanitize() drops keys that are not known settings.
	 */
	public function test_sanitize_ignores_unknown_keys() {
		$out = Settings::sanitize( array( 'not_a_setting' => 'x' ) );

		$this->assertArrayNotHasKey( 'not_a_setting', $out );
	}
}
