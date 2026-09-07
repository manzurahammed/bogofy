<?php
/**
 * ProductPage tests.
 *
 * @package Bogofy\Tests\Unit\Frontend
 */

namespace Bogofy\Tests\Unit\Frontend;

use Bogofy\Frontend\ProductPage;
use Bogofy\Models\Rule;
use Bogofy\Models\RuleRepository;
use PHPUnit\Framework\TestCase;

/**
 * Class ProductPageTest
 */
class ProductPageTest extends TestCase {

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
	 * The gift icon helper returns a sized inline SVG.
	 */
	public function test_gift_icon_svg_returns_sized_svg() {
		$svg = ProductPage::gift_icon_svg( 14 );

		$this->assertStringContainsString( '<svg', $svg );
		$this->assertStringContainsString( 'width="14"', $svg );
		$this->assertStringContainsString( 'height="14"', $svg );
	}

	/**
	 * The strip label reflects the rule type (free vs. discounted).
	 *
	 * @param string $type     Rule type.
	 * @param int    $discount Discount value.
	 * @param string $needle   Expected substring.
	 *
	 * @dataProvider badge_label_provider
	 */
	public function test_badge_label_varies_by_rule_type( $type, $discount, $needle ) {
		$rule                 = new Rule();
		$rule->rule_type      = $type;
		$rule->buy_quantity   = 1;
		$rule->free_quantity  = 1;
		$rule->discount_value = $discount;

		$page   = new ProductPage( $this->createMock( RuleRepository::class ) );
		$method = new \ReflectionMethod( $page, 'get_badge_label' );
		$method->setAccessible( true );

		$this->assertStringContainsString( $needle, $method->invoke( $page, $rule ) );
	}

	/**
	 * Data provider for badge labels.
	 *
	 * @return array
	 */
	public function badge_label_provider() {
		return array(
			'free'       => array( Rule::TYPE_BUY_X_GET_Y, 100, 'Free' ),
			'discounted' => array( Rule::TYPE_BUY_X_GET_X_DISCOUNTED, 20, '20% off' ),
		);
	}
}
