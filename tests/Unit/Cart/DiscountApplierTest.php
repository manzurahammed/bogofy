<?php
/**
 * DiscountApplier and free-gift reconciliation tests.
 *
 * @package Bogofy\Tests\Unit\Cart
 */

namespace Bogofy\Tests\Unit\Cart;

use Bogofy\Cart\CartHandler;
use Bogofy\Cart\Contracts\FreeItemManagerInterface;
use Bogofy\Cart\DiscountApplier;
use Bogofy\Cart\EligibilityChecker;
use Bogofy\Models\Rule;
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
	 * Build a Buy X Get Y rule with the given gift products.
	 *
	 * @param array $gift_ids Gift product IDs.
	 *
	 * @return Rule
	 */
	private function gift_rule( array $gift_ids ) {
		$rule                   = $this->rule( 1, 1, array( 10 ) );
		$rule->rule_type        = Rule::TYPE_BUY_X_GET_Y;
		$rule->free_product_ids = $gift_ids;

		return $rule;
	}

	/**
	 * Cart whose gift lines the mocked manager adds and removes.
	 *
	 * @param array $contents Initial cart contents.
	 *
	 * @return object
	 */
	private function gift_cart( array $contents = array() ) {
		return new class( $contents ) {
			public $cart_contents;

			public function __construct( $contents ) {
				$this->cart_contents = $contents;
			}

			public function get_cart() {
				return $this->cart_contents;
			}
		};
	}

	/**
	 * A gift line for rule 5.
	 *
	 * @param int   $product_id Gift product ID.
	 * @param int   $quantity   Quantity.
	 * @param array $extra      Extra cart item data.
	 *
	 * @return array
	 */
	private function gift_line( $product_id, $quantity, array $extra = array() ) {
		return array_merge(
			array(
				'product_id'               => $product_id,
				'variation_id'             => 0,
				'quantity'                 => $quantity,
				CartHandler::BOGO_ITEM_KEY => true,
				CartHandler::BOGO_RULE_KEY => 5,
			),
			$extra
		);
	}

	/**
	 * Applier whose manager mutates the fake cart like the real one.
	 *
	 * @param array $unavailable Gift product IDs that cannot be supplied.
	 *
	 * @return DiscountApplier
	 */
	private function gift_applier( array $unavailable = array() ) {
		$manager = Mockery::mock( FreeItemManagerInterface::class );
		$manager->shouldReceive( 'add_free_item' )->andReturnUsing(
			function ( $cart, $product_id, $quantity ) use ( $unavailable ) {
				$key = 'gift_' . $product_id;
				if ( in_array( $product_id, $unavailable, true ) ) {
					unset( $cart->cart_contents[ $key ] );
					return false;
				}
				$line                        = $cart->cart_contents[ $key ] ?? $this->gift_line( $product_id, $quantity );
				$line['quantity']            = $quantity;
				$cart->cart_contents[ $key ] = $line;
				return $key;
			}
		);
		$manager->shouldReceive( 'remove_rule_items_except' )->andReturnUsing(
			function ( $cart, $rule_id, $keep ) {
				foreach ( $cart->cart_contents as $key => $line ) {
					if ( ! in_array( (int) $line['product_id'], $keep, true ) ) {
						unset( $cart->cart_contents[ $key ] );
					}
				}
			}
		);
		$manager->shouldReceive( 'remove_rule_items' )->andReturnUsing(
			function ( $cart ) {
				$cart->cart_contents = array();
			}
		);

		return new DiscountApplier( new EligibilityChecker(), $manager );
	}

	/**
	 * With several gifts and no choice yet, the first gift is added by default.
	 */
	public function test_multi_gift_rule_adds_first_gift_by_default() {
		$cart = $this->gift_cart();

		$this->gift_applier()->sync( $cart, $this->gift_rule( array( 30, 40 ) ), array( 'a' => $this->line( 10, 1, 15.0 ) ) );

		$this->assertSame( array( 'gift_30' ), array_keys( $cart->cart_contents ) );
		$this->assertSame( 30, $cart->cart_contents['gift_30'][ CartHandler::BOGO_GIFT_CHOICE_KEY ] );
	}

	/**
	 * A single-gift rule keeps its old behaviour and stores no choice.
	 */
	public function test_single_gift_rule_is_unchanged() {
		$cart = $this->gift_cart();

		$this->gift_applier()->sync( $cart, $this->gift_rule( array( 30 ) ), array( 'a' => $this->line( 10, 2, 15.0 ) ) );

		$this->assertSame( 2, $cart->cart_contents['gift_30']['quantity'] );
		$this->assertArrayNotHasKey( CartHandler::BOGO_GIFT_CHOICE_KEY, $cart->cart_contents['gift_30'] );
	}

	/**
	 * Switching replaces the gift line, and the choice survives a later sync.
	 */
	public function test_switched_gift_is_kept_on_recalculation() {
		$rule    = $this->gift_rule( array( 30, 40 ) );
		$applier = $this->gift_applier();
		$cart    = $this->gift_cart( array( 'gift_30' => $this->gift_line( 30, 1 ) ) );

		$this->assertTrue( $applier->switch_gift( $cart, $rule, 40 ) );
		$this->assertSame( array( 'gift_40' ), array_keys( $cart->cart_contents ) );
		$this->assertSame( 40, $cart->cart_contents['gift_40'][ CartHandler::BOGO_GIFT_CHOICE_KEY ] );

		$applier->sync( $cart, $rule, array( 'a' => $this->line( 10, 1, 15.0 ) ) );

		$this->assertSame( array( 'gift_40' ), array_keys( $cart->cart_contents ) );
	}

	/**
	 * A product that isn't one of the rule's gifts is rejected and nothing changes.
	 */
	public function test_switching_to_a_product_outside_the_rule_is_rejected() {
		$cart = $this->gift_cart( array( 'gift_30' => $this->gift_line( 30, 1 ) ) );

		$this->assertFalse( $this->gift_applier()->switch_gift( $cart, $this->gift_rule( array( 30, 40 ) ), 99 ) );
		$this->assertSame( array( 'gift_30' ), array_keys( $cart->cart_contents ) );
	}

	/**
	 * Without an earned gift line there is nothing to switch.
	 */
	public function test_switching_without_an_earned_gift_is_rejected() {
		$cart = $this->gift_cart();

		$this->assertFalse( $this->gift_applier()->switch_gift( $cart, $this->gift_rule( array( 30, 40 ) ), 40 ) );
		$this->assertSame( array(), $cart->cart_contents );
	}

	/**
	 * A switch carries over the earned quantity, and the next sync caps it at
	 * the rule's free quantity.
	 */
	public function test_switching_never_grants_more_than_the_free_quantity() {
		$rule    = $this->gift_rule( array( 30, 40 ) );
		$applier = $this->gift_applier();
		$cart    = $this->gift_cart( array( 'gift_30' => $this->gift_line( 30, 2 ) ) );

		$applier->switch_gift( $cart, $rule, 40 );
		$this->assertSame( 2, $cart->cart_contents['gift_40']['quantity'] );

		// The customer now only qualifies for one free unit.
		$applier->sync( $cart, $rule, array( 'a' => $this->line( 10, 1, 15.0 ) ) );
		$this->assertSame( 1, $cart->cart_contents['gift_40']['quantity'] );
		$this->assertCount( 1, $cart->cart_contents );
	}

	/**
	 * The replaced gift's line is passed on so its stock is reused, and it stays
	 * in the cart when the new gift can't be added.
	 */
	public function test_switch_reuses_the_replaced_gift_and_keeps_it_on_failure() {
		$replacing = null;
		$manager   = Mockery::mock( FreeItemManagerInterface::class );
		$manager->shouldReceive( 'add_free_item' )->andReturnUsing(
			function ( $cart, $product_id, $quantity, $rule, $variation, $keys ) use ( &$replacing ) {
				$replacing = $keys;
				return false;
			}
		);
		$manager->shouldNotReceive( 'remove_rule_items_except' );

		$cart    = $this->gift_cart( array( 'gift_30' => $this->gift_line( 30, 1 ) ) );
		$applier = new DiscountApplier( new EligibilityChecker(), $manager );

		$this->assertFalse( $applier->switch_gift( $cart, $this->gift_rule( array( 30, 40 ) ), 40 ) );
		$this->assertSame( array( 'gift_30' ), $replacing );
		$this->assertSame( array( 'gift_30' ), array_keys( $cart->cart_contents ) );
	}

	/**
	 * A chosen gift that can't be supplied falls back to the next gift.
	 */
	public function test_unavailable_choice_falls_back_to_next_gift() {
		$cart = $this->gift_cart(
			array( 'gift_40' => $this->gift_line( 40, 1, array( CartHandler::BOGO_GIFT_CHOICE_KEY => 40 ) ) )
		);

		$this->gift_applier( array( 40 ) )->sync( $cart, $this->gift_rule( array( 30, 40 ) ), array( 'a' => $this->line( 10, 1, 15.0 ) ) );

		$this->assertSame( array( 'gift_30' ), array_keys( $cart->cart_contents ) );
	}
}
