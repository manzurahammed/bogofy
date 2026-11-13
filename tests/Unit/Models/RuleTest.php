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
	 * Enum-like fields reject values outside their allowed set.
	 */
	public function test_validate_rejects_unknown_enum_values() {
		foreach ( array( 'status', 'discount_type', 'apply_to' ) as $field ) {
			$rule          = new Rule();
			$rule->title   = 'Rule';
			$rule->$field  = 'nonsense';

			$this->assertInstanceOf( \WP_Error::class, $rule->validate(), $field );
		}
	}

	/**
	 * Dates must parse, and a rule cannot end before it starts.
	 */
	public function test_validate_rejects_bad_dates() {
		$rule             = new Rule();
		$rule->title      = 'Rule';
		$rule->start_date = 'not a date';
		$this->assertInstanceOf( \WP_Error::class, $rule->validate() );

		$rule->start_date = '2026-12-01T10:00';
		$rule->end_date   = '2026-11-01T10:00';
		$this->assertInstanceOf( \WP_Error::class, $rule->validate() );

		$rule->end_date = '2026-12-02T10:00';
		$this->assertTrue( $rule->validate() );
	}

	/**
	 * Text strtotime() accepts must not reach the DATETIME columns.
	 */
	public function test_validate_rejects_dates_the_database_cannot_store() {
		$rule        = new Rule();
		$rule->title = 'Rule';

		foreach ( array( 'tomorrow', '+1 week', '2026-02-30', '2026-13-01 10:00:00' ) as $date ) {
			$rule->start_date = $date;
			$this->assertInstanceOf( \WP_Error::class, $rule->validate(), $date );
		}
	}

	/**
	 * The admin form and the database formats are both accepted.
	 */
	public function test_validate_accepts_form_and_database_dates() {
		$rule        = new Rule();
		$rule->title = 'Rule';

		foreach ( array( '2026-12-01T10:00', '2026-12-01 10:00', '2026-12-01 10:00:00', '2026-12-01' ) as $date ) {
			$rule->start_date = $date;
			$this->assertTrue( $rule->validate(), $date );
		}
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

	/**
	 * Test duplicating a rule gives an inactive, unsaved copy with fresh stats.
	 */
	public function test_duplicate_returns_inactive_copy() {
		$rule                   = new Rule();
		$rule->id               = 7;
		$rule->title            = 'Summer Sale';
		$rule->status           = Rule::STATUS_ACTIVE;
		$rule->buy_quantity     = 2;
		$rule->buy_product_ids  = array( 1, 2 );
		$rule->free_product_ids = array( 3 );
		$rule->orders_count     = 12;
		$rule->revenue_total    = 340.5;
		$rule->created_at       = '2026-01-01 00:00:00';

		$copy = $rule->duplicate();

		$this->assertSame( 0, $copy->id );
		$this->assertSame( 'Summer Sale (copy)', $copy->title );
		$this->assertSame( Rule::STATUS_INACTIVE, $copy->status );
		$this->assertSame( 0, $copy->orders_count );
		$this->assertSame( 0.0, $copy->revenue_total );
		$this->assertSame( '', $copy->created_at );
		$this->assertSame( 2, $copy->buy_quantity );
		$this->assertSame( array( 1, 2 ), $copy->buy_product_ids );
		$this->assertSame( array( 3 ), $copy->free_product_ids );

		// The original is untouched.
		$this->assertSame( 7, $rule->id );
		$this->assertSame( Rule::STATUS_ACTIVE, $rule->status );
		$this->assertSame( 12, $rule->orders_count );
	}

	/**
	 * Test a long title is shortened to fit the column and keeps the copy marker.
	 */
	public function test_duplicate_truncates_long_title_and_keeps_marker() {
		$rule        = new Rule();
		$rule->title = str_repeat( 'a', 255 );

		$title = $rule->duplicate()->title;

		$this->assertSame( 255, mb_strlen( $title ) );
		$this->assertStringEndsWith( ' (copy)', $title );
	}

	/**
	 * Test a long non-ASCII title is cut on a character boundary.
	 */
	public function test_duplicate_truncates_multibyte_title_safely() {
		$rule        = new Rule();
		$rule->title = str_repeat( 'é', 255 );

		$title = $rule->duplicate()->title;

		$this->assertTrue( mb_check_encoding( $title, 'UTF-8' ) );
		$this->assertSame( 255, mb_strlen( $title ) );
		$this->assertStringEndsWith( ' (copy)', $title );
	}

	/**
	 * Request conditions are typed and sanitized; empty values become defaults.
	 */
	public function test_from_request_sanitizes_conditions() {
		$rule = Rule::from_request(
			array(
				'title'      => 'Rule',
				'conditions' => array(
					'min_subtotal'          => '49.999',
					'min_items'             => '3',
					'excluded_product_ids'  => array( '12', 'abc', 0, 14 ),
					'excluded_category_ids' => array( '7' ),
					'exclude_sale_items'    => '1',
				),
			)
		);

		$this->assertSame(
			array(
				'min_subtotal'          => 50.0,
				'min_items'             => 3,
				'excluded_product_ids'  => array( 12, 14 ),
				'excluded_category_ids' => array( 7 ),
				'exclude_sale_items'    => true,
			),
			$rule->conditions
		);

		$blank = Rule::from_request(
			array(
				'title'      => 'Rule',
				'conditions' => array(
					'min_subtotal'       => '',
					'min_items'          => '',
					'exclude_sale_items' => false,
				),
			)
		);

		$this->assertSame( 0.0, $blank->conditions['min_subtotal'] );
		$this->assertSame( 0, $blank->conditions['min_items'] );
		$this->assertFalse( $blank->conditions['exclude_sale_items'] );
	}

	/**
	 * Conditions round-trip through the database column and the API array.
	 */
	public function test_conditions_round_trip_through_db_and_to_array() {
		$rule                               = new Rule();
		$rule->conditions['min_subtotal']   = 25.5;
		$rule->conditions['min_items']      = 2;
		$rule->conditions['excluded_product_ids'] = array( 9 );

		$stored = $rule->to_db_array();
		$this->assertIsString( $stored['conditions'] );

		$loaded = Rule::from_db( array( 'conditions' => $stored['conditions'] ) );

		$this->assertSame( $rule->conditions, $loaded->conditions );
		$this->assertSame( $rule->conditions, $loaded->to_array()['conditions'] );
	}

	/**
	 * Rows saved before the conditions column existed load with no conditions.
	 */
	public function test_legacy_rows_without_conditions_load_defaults() {
		foreach ( array( null, '', 'not json' ) as $value ) {
			$rule = Rule::from_db(
				array(
					'id'         => 1,
					'conditions' => $value,
				)
			);

			$this->assertSame( 0.0, $rule->conditions['min_subtotal'] );
			$this->assertSame( 0, $rule->conditions['min_items'] );
			$this->assertSame( array(), $rule->conditions['excluded_product_ids'] );
			$this->assertSame( array(), $rule->conditions['excluded_category_ids'] );
			$this->assertFalse( $rule->conditions['exclude_sale_items'] );
			$this->assertFalse( $rule->has_exclusions() );
		}

		$this->assertArrayHasKey( 'min_items', Rule::from_db( array( 'id' => 2 ) )->conditions );
	}

	/**
	 * Negative minimums are rejected with a 400-style validation error.
	 */
	public function test_validate_rejects_negative_minimums() {
		$rule = Rule::from_request(
			array(
				'title'      => 'Rule',
				'conditions' => array( 'min_subtotal' => '-1' ),
			)
		);
		$this->assertSame( 'invalid_min_subtotal', $rule->validate()->get_error_code() );

		$rule = Rule::from_request(
			array(
				'title'      => 'Rule',
				'conditions' => array( 'min_items' => -2 ),
			)
		);
		$this->assertSame( 'invalid_min_items', $rule->validate()->get_error_code() );

		$rule = Rule::from_request(
			array(
				'title'      => 'Rule',
				'conditions' => array(
					'min_subtotal' => '0',
					'min_items'    => 0,
				),
			)
		);
		$this->assertTrue( $rule->validate() );
	}

	/**
	 * Exclusions match by product, parent, category, or sale status.
	 */
	public function test_excludes_product_matches_each_exclusion() {
		$rule = Rule::from_request(
			array(
				'title'      => 'Rule',
				'conditions' => array(
					'excluded_product_ids'  => array( 5 ),
					'excluded_category_ids' => array( 8 ),
				),
			)
		);

		$this->assertTrue( $rule->excludes_product( array( 5 ), array(), false ) );
		$this->assertTrue( $rule->excludes_product( array( 6, 5 ), array(), false ) );
		$this->assertTrue( $rule->excludes_product( array( 6 ), array( 8 ), false ) );
		$this->assertFalse( $rule->excludes_product( array( 6 ), array( 9 ), true ) );

		$rule->conditions['exclude_sale_items'] = true;
		$this->assertTrue( $rule->excludes_product( array( 6 ), array( 9 ), true ) );
	}
}
