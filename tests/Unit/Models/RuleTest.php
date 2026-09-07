<?php
/**
 * Rule model tests.
 *
 * @package Bogofy\Tests\Unit\Models
 */

namespace Bogofy\Tests\Unit\Models;

use Bogofy\Models\Rule;
use PHPUnit\Framework\TestCase;
use Brain\Monkey;

/**
 * Class RuleTest
 */
class RuleTest extends TestCase {

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
	 * Test creating rule from database row.
	 */
	public function test_from_db_creates_rule() {
		$data = array(
			'id'               => 1,
			'title'            => 'Test Rule',
			'rule_type'        => 'buy_x_get_x',
			'status'           => 'active',
			'buy_quantity'     => 2,
			'free_quantity'    => 1,
			'discount_type'    => 'free',
			'discount_value'   => 100.00,
			'apply_to'         => 'specific_products',
			'buy_product_ids'  => '[1, 2, 3]',
			'free_product_ids' => '[]',
			'category_ids'     => '[]',
			'max_free_qty'     => 5,
			'message_template' => 'Buy {buy_qty} Get {free_qty} Free!',
			'priority'         => 10,
			'start_date'       => '2024-01-01 00:00:00',
			'end_date'         => '2024-12-31 23:59:59',
			'created_at'       => '2024-01-01 00:00:00',
			'updated_at'       => '2024-01-01 00:00:00',
		);

		$rule = Rule::from_db( $data );

		$this->assertEquals( 1, $rule->id );
		$this->assertEquals( 'Test Rule', $rule->title );
		$this->assertEquals( 'buy_x_get_x', $rule->rule_type );
		$this->assertEquals( 'active', $rule->status );
		$this->assertEquals( 2, $rule->buy_quantity );
		$this->assertEquals( 1, $rule->free_quantity );
		$this->assertEquals( array( 1, 2, 3 ), $rule->buy_product_ids );
		$this->assertEquals( 5, $rule->max_free_qty );
	}

	/**
	 * Test creating rule from request data.
	 */
	public function test_from_request_sanitizes_data() {
		$data = array(
			'title'         => '  Test Rule  ',
			'rule_type'     => 'buy_x_get_y',
			'buy_quantity'  => '3',
			'free_quantity' => '1',
			'apply_to'      => 'specific_categories',
		);

		$rule = Rule::from_request( $data );

		$this->assertEquals( 'Test Rule', $rule->title );
		$this->assertEquals( 'buy_x_get_y', $rule->rule_type );
		$this->assertEquals( 3, $rule->buy_quantity );
		$this->assertEquals( 1, $rule->free_quantity );
	}

	/**
	 * Test validation fails without title.
	 */
	public function test_validate_fails_without_title() {
		$rule        = new Rule();
		$rule->title = '';

		$result = $rule->validate();

		$this->assertInstanceOf( \WP_Error::class, $result );
	}

	/**
	 * Test validation fails with invalid rule type.
	 */
	public function test_validate_fails_with_invalid_rule_type() {
		$rule            = new Rule();
		$rule->title     = 'Test Rule';
		$rule->rule_type = 'invalid_type';

		$result = $rule->validate();

		$this->assertInstanceOf( \WP_Error::class, $result );
	}

	/**
	 * Test validation passes with valid data.
	 */
	public function test_validate_passes_with_valid_data() {
		$rule               = new Rule();
		$rule->title        = 'Test Rule';
		$rule->rule_type    = 'buy_x_get_x';
		$rule->buy_quantity = 2;
		$rule->free_quantity = 1;

		$result = $rule->validate();

		$this->assertTrue( $result );
	}

	/**
	 * Test the orders_count / revenue stat fields round-trip through the model.
	 */
	public function test_stat_fields_flow_through_from_db_and_to_array() {
		$rule = Rule::from_db(
			array(
				'id'            => 7,
				'orders_count'  => '5',
				'revenue_total' => '123.45',
			)
		);

		$this->assertSame( 5, $rule->orders_count );
		$this->assertSame( 123.45, $rule->revenue_total );

		$array = $rule->to_array();
		$this->assertSame( 5, $array['orders_count'] );
		$this->assertSame( 123.45, $array['revenue'] );
	}

	/**
	 * Test to_array returns expected structure.
	 */
	public function test_to_array_returns_expected_structure() {
		$rule               = new Rule();
		$rule->id           = 1;
		$rule->title        = 'Test Rule';
		$rule->rule_type    = 'buy_x_get_x';

		$array = $rule->to_array();

		$this->assertIsArray( $array );
		$this->assertArrayHasKey( 'id', $array );
		$this->assertArrayHasKey( 'title', $array );
		$this->assertArrayHasKey( 'rule_type', $array );
		$this->assertEquals( 1, $array['id'] );
		$this->assertEquals( 'Test Rule', $array['title'] );
	}

	/**
	 * Test is_active returns correct status.
	 */
	public function test_is_active_checks_status_and_dates() {
		$rule         = new Rule();
		$rule->status = 'active';

		$this->assertTrue( $rule->is_active() );

		$rule->status = 'inactive';
		$this->assertFalse( $rule->is_active() );
	}

	/**
	 * Test get_type_label returns human readable label.
	 */
	public function test_get_type_label_returns_readable_label() {
		$rule            = new Rule();
		$rule->rule_type = 'buy_x_get_x';

		$this->assertEquals( 'Buy X Get X Free', $rule->get_type_label() );

		$rule->rule_type = 'buy_x_get_y';
		$this->assertEquals( 'Buy X Get Y Free', $rule->get_type_label() );
	}
}
