<?php
/**
 * EligibilityChecker tests.
 *
 * @package Bogofy\Tests\Unit\Cart
 */

namespace Bogofy\Tests\Unit\Cart;

use Bogofy\Cart\EligibilityChecker;
use Bogofy\Models\Rule;
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
	}

	/**
	 * Tear down test fixtures.
	 */
	protected function tearDown(): void {
		bogo_tests_teardown();
		parent::tearDown();
	}

	/**
	 * Build a Buy X Get X rule.
	 *
	 * @param int      $buy  Buy quantity.
	 * @param int      $free Free quantity.
	 * @param int|null $max  Max free quantity.
	 *
	 * @return Rule
	 */
	private function rule( $buy, $free, $max = null ) {
		$rule                = new Rule();
		$rule->rule_type     = Rule::TYPE_BUY_X_GET_X;
		$rule->buy_quantity  = $buy;
		$rule->free_quantity = $free;
		$rule->max_free_qty  = $max;

		return $rule;
	}

	/**
	 * Eligible cart lines with the given quantities.
	 *
	 * @param int ...$quantities Line quantities.
	 *
	 * @return array
	 */
	private function items( ...$quantities ) {
		return array_map(
			static function ( $quantity ) {
				return array( 'quantity' => $quantity );
			},
			$quantities
		);
	}

	/**
	 * 1 of 2 bought: one more unlocks one free item.
	 */
	public function test_next_unlock_for_partial_set() {
		$unlock = ( new EligibilityChecker() )->get_next_unlock( $this->items( 1 ), $this->rule( 2, 1 ) );

		$this->assertSame(
			array(
				'remaining' => 1,
				'free_qty'  => 1,
			),
			$unlock
		);
	}

	/**
	 * Quantities across lines add up toward the next set.
	 */
	public function test_next_unlock_sums_lines_past_a_full_set() {
		$unlock = ( new EligibilityChecker() )->get_next_unlock( $this->items( 2, 2 ), $this->rule( 3, 2 ) );

		$this->assertSame( 2, $unlock['remaining'] );
		$this->assertSame( 2, $unlock['free_qty'] );
	}

	/**
	 * Exactly on a set boundary there is no partly met set to nudge.
	 */
	public function test_no_next_unlock_for_exact_set() {
		$this->assertNull( ( new EligibilityChecker() )->get_next_unlock( $this->items( 2 ), $this->rule( 2, 1 ) ) );
		$this->assertNull( ( new EligibilityChecker() )->get_next_unlock( $this->items( 4 ), $this->rule( 2, 1 ) ) );
	}

	/**
	 * Once the max free quantity is reached, nothing more can be unlocked.
	 */
	public function test_no_next_unlock_when_max_reached() {
		// 5 items, buy 2 get 1, max 2: 2 free already, a third is capped away.
		$this->assertNull( ( new EligibilityChecker() )->get_next_unlock( $this->items( 5 ), $this->rule( 2, 1, 2 ) ) );
	}

	/**
	 * The unlocked quantity is capped by the remaining max headroom.
	 */
	public function test_next_unlock_free_qty_capped_by_max() {
		// 3 items, buy 2 get 2, max 3: 2 free already, only 1 more allowed.
		$unlock = ( new EligibilityChecker() )->get_next_unlock( $this->items( 3 ), $this->rule( 2, 2, 3 ) );

		$this->assertSame( 1, $unlock['remaining'] );
		$this->assertSame( 1, $unlock['free_qty'] );
	}

	/**
	 * A cart with no eligible items gets no nudge.
	 */
	public function test_no_next_unlock_when_not_eligible() {
		$this->assertNull( ( new EligibilityChecker() )->get_next_unlock( array(), $this->rule( 2, 1 ) ) );
	}
}
