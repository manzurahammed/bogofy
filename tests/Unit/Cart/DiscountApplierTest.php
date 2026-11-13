<?php
/**
 * DiscountApplier and free-gift reconciliation tests.
 *
 * @package Bogofy\Tests\Unit\Cart
 */

namespace Bogofy\Tests\Unit\Cart;

use Bogofy\Cart\Contracts\FreeItemManagerInterface;
use Bogofy\Cart\DiscountApplier;
use Bogofy\Cart\EligibilityChecker;
use Bogofy\Models\Rule;
use Brain\Monkey\Filters;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Class DiscountApplierTest
 */
class DiscountApplierTest extends TestCase {

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
		Mockery::close();
		bogo_tests_teardown();
		parent::tearDown();
	}

	/**
	 * Build a Buy X Get X rule.
	 *
	 * @param int   $buy  Buy quantity.
	 * @param int   $free Free quantity.
	 * @param array $ids  Buy product IDs.
	 *
	 * @return Rule
	 */
	private function rule( $buy, $free, $ids ) {
		$rule                  = new Rule();
		$rule->id              = 5;
		$rule->rule_type       = Rule::TYPE_BUY_X_GET_X;
		$rule->apply_to        = Rule::APPLY_SPECIFIC_PRODUCTS;
		$rule->buy_quantity    = $buy;
		$rule->free_quantity   = $free;
		$rule->buy_product_ids = $ids;

		return $rule;
	}

	/**
	 * Build a cart line.
	 *
	 * @param int   $id       Product ID.
	 * @param int   $quantity Quantity.
	 * @param float $price    Price.
	 *
	 * @return array
	 */
	private function line( $id, $quantity, $price ) {
		return array(
			'product_id'   => $id,
			'variation_id' => 0,
			'quantity'     => $quantity,
			'data'         => new \Bogo_Fake_Priced_Product( $price ),
		);
	}

	/**
	 * Run the sync and capture what was granted.
	 *
	 * @param Rule  $rule  Rule.
	 * @param array $items Eligible items.
	 *
	 * @return array Map of product ID to granted quantity.
	 */
	private function granted( Rule $rule, array $items ) {
		$granted = array();
		$manager = Mockery::mock( FreeItemManagerInterface::class );
		$manager->shouldReceive( 'remove_rule_items_except' );
		$manager->shouldReceive( 'add_free_item' )->andReturnUsing(
			function ( $cart, $product_id, $quantity ) use ( &$granted ) {
				$granted[ $product_id ] = $quantity;
				return 'key';
			}
		);

		( new DiscountApplier( new EligibilityChecker(), $manager ) )->sync( new \stdClass(), $rule, $items );

		return $granted;
	}

	/**
	 * The free copy must be the product the customer bought, not the rule's first.
	 */
	public function test_buy_x_get_x_gives_the_purchased_product() {
		$granted = $this->granted(
			$this->rule( 1, 1, array( 10, 20 ) ),
			array( 'b' => $this->line( 20, 1, 15.0 ) )
		);

		$this->assertSame( array( 20 => 1 ), $granted );
	}

	/**
	 * "Any" variations pass on the attributes the customer chose.
	 */
	public function test_buy_x_get_x_passes_the_chosen_attributes() {
		$received = null;
		$line     = $this->line( 20, 1, 15.0 );

		$line['variation'] = array( 'attribute_size' => 'L' );

		$manager = Mockery::mock( FreeItemManagerInterface::class );
		$manager->shouldReceive( 'remove_rule_items_except' );
		$manager->shouldReceive( 'add_free_item' )->andReturnUsing(
			function ( $cart, $product_id, $quantity, $rule, $variation ) use ( &$received ) {
				$received = $variation;
				return 'key';
			}
		);

		( new DiscountApplier( new EligibilityChecker(), $manager ) )->sync( new \stdClass(), $this->rule( 1, 1, array( 20 ) ), array( 'a' => $line ) );

		$this->assertSame( array( 'attribute_size' => 'L' ), $received );
	}

	/**
	 * "Buy 1 Get 2" grants the full free quantity, even above the bought quantity.
	 */
	public function test_free_quantity_may_exceed_purchased_quantity() {
		$granted = $this->granted(
			$this->rule( 1, 2, array( 10 ) ),
			array( 'a' => $this->line( 10, 1, 15.0 ) )
		);

		$this->assertSame( array( 10 => 2 ), $granted );
	}

	/**
	 * Two lines of one product accumulate instead of overwriting each other.
	 */
	public function test_split_lines_of_one_product_accumulate() {
		$granted = $this->granted(
			$this->rule( 1, 1, array( 10 ) ),
			array(
				'a' => $this->line( 10, 1, 15.0 ),
				'b' => $this->line( 10, 2, 15.0 ),
			)
		);

		$this->assertSame( array( 10 => 3 ), $granted );
	}

	/**
	 * Free quantity is always a whole number, and a zero buy quantity is safe.
	 */
	public function test_free_quantity_is_int_and_survives_zero_buy_quantity() {
		$checker = new EligibilityChecker();

		$this->assertSame( 2, $checker->calculate_free_quantity( array( array( 'quantity' => 2 ) ), $this->rule( 1, 1, array() ) ) );
		$this->assertSame( 2, $checker->calculate_free_quantity( array( array( 'quantity' => 2 ) ), $this->rule( 0, 1, array() ) ) );
	}

	/**
	 * Developers can change the free quantity, and the gift follows it.
	 */
	public function test_free_quantity_filter_changes_the_granted_quantity() {
		$rule  = $this->rule( 2, 1, array( 10 ) );
		$items = array( 'a' => $this->line( 10, 2, 5 ) );

		Filters\expectApplied( 'bogofy_free_quantity' )
			->once()
			->with( 1, $rule, $items )
			->andReturn( '3' );

		$this->assertSame( array( 10 => 3 ), $this->granted( $rule, $items ) );
	}

	/**
	 * A negative filtered quantity grants nothing rather than a negative line.
	 */
	public function test_negative_filtered_free_quantity_is_zero() {
		Filters\expectApplied( 'bogofy_free_quantity' )->once()->andReturn( -4 );

		$checker = new EligibilityChecker();

		$this->assertSame( 0, $checker->calculate_free_quantity( array( array( 'quantity' => 2 ) ), $this->rule( 1, 1, array() ) ) );
	}
}
