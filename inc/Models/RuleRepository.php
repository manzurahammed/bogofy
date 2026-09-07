<?php
/**
 * BOGO Rule repository for database operations.
 *
 * @package Bogofy\Models
 */

namespace Bogofy\Models;

use Bogofy\Database\Schema;

/**
 * Class RuleRepository
 *
 * Handles database operations for BOGO rules.
 */
class RuleRepository {

	/**
	 * Object cache group.
	 *
	 * @var string
	 */
	const CACHE_GROUP = 'bogo_rules';

	/**
	 * Cache key for the active rules list.
	 *
	 * @var string
	 */
	const CACHE_KEY_ACTIVE = 'active_rules';

	/**
	 * Invalidate cached rule data after a write.
	 *
	 * @return void
	 */
	private function flush_cache() {
		wp_cache_delete( self::CACHE_KEY_ACTIVE, self::CACHE_GROUP );
	}

	/**
	 * Get table name.
	 *
	 * @return string
	 */
	private function get_table() {
		return Schema::get_table_name( Schema::RULES_TABLE );
	}

	/**
	 * Get all rules.
	 *
	 * @param array $args Query arguments.
	 *
	 * @return array
	 */
	public function get_all( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'status'   => '',
			'per_page' => 20,
			'page'     => 1,
			'orderby'  => 'priority',
			'order'    => 'ASC',
			'search'   => '',
		);

		$args = wp_parse_args( $args, $defaults );

		$table = $this->get_table();
		$where = array( '1=1' );

		// Status filter.
		if ( ! empty( $args['status'] ) ) {
			$where[] = $wpdb->prepare( 'status = %s', $args['status'] );
		}

		// Search filter.
		if ( ! empty( $args['search'] ) ) {
			$search  = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where[] = $wpdb->prepare( 'title LIKE %s', $search );
		}

		$where_clause = implode( ' AND ', $where );

		// Sanitize orderby.
		$allowed_orderby = array( 'id', 'title', 'priority', 'created_at', 'updated_at', 'status' );
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'priority';
		$order           = 'DESC' === strtoupper( $args['order'] ) ? 'DESC' : 'ASC';

		// Pagination.
		$per_page = absint( $args['per_page'] );
		$page     = absint( $args['page'] );
		$offset   = ( $page - 1 ) * $per_page;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT * FROM {$table} WHERE {$where_clause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d",
				$per_page,
				$offset
			)
		);

		$rules = array();
		foreach ( $results as $row ) {
			$rules[] = Rule::from_db( $row );
		}

		return $rules;
	}

	/**
	 * Get total count of rules.
	 *
	 * @param array $args Query arguments.
	 *
	 * @return int
	 */
	public function get_count( $args = array() ) {
		global $wpdb;

		$table = $this->get_table();
		$where = array( '1=1' );

		if ( ! empty( $args['status'] ) ) {
			$where[] = $wpdb->prepare( 'status = %s', $args['status'] );
		}

		if ( ! empty( $args['search'] ) ) {
			$search  = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where[] = $wpdb->prepare( 'title LIKE %s', $search );
		}

		$where_clause = implode( ' AND ', $where );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE {$where_clause}" );
	}

	/**
	 * Get a single rule by ID.
	 *
	 * @param int $id Rule ID.
	 *
	 * @return Rule|null
	 */
	public function get( $id ) {
		global $wpdb;

		$table = $this->get_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table is internally controlled.
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );

		if ( ! $row ) {
			return null;
		}

		return Rule::from_db( $row );
	}

	/**
	 * Create a new rule.
	 *
	 * @param Rule $rule Rule object.
	 *
	 * @return int|false Rule ID on success, false on failure.
	 */
	public function create( Rule $rule ) {
		global $wpdb;

		$result = $wpdb->insert(
			$this->get_table(),
			$rule->to_db_array(),
			array( '%s', '%s', '%s', '%d', '%d', '%s', '%f', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s' )
		);

		if ( false === $result ) {
			return false;
		}

		$this->flush_cache();

		return $wpdb->insert_id;
	}

	/**
	 * Update an existing rule.
	 *
	 * @param Rule $rule Rule object.
	 *
	 * @return bool
	 */
	public function update( Rule $rule ) {
		global $wpdb;

		$result = $wpdb->update(
			$this->get_table(),
			$rule->to_db_array(),
			array( 'id' => $rule->id ),
			array( '%s', '%s', '%s', '%d', '%d', '%s', '%f', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s' ),
			array( '%d' )
		);

		$this->flush_cache();

		return false !== $result;
	}

	/**
	 * Delete a rule.
	 *
	 * @param int $id Rule ID.
	 *
	 * @return bool
	 */
	public function delete( $id ) {
		global $wpdb;

		$result = $wpdb->delete(
			$this->get_table(),
			array( 'id' => $id ),
			array( '%d' )
		);

		$this->flush_cache();

		return false !== $result;
	}

	/**
	 * Update rule status.
	 *
	 * @param int    $id     Rule ID.
	 * @param string $status New status.
	 *
	 * @return bool
	 */
	public function update_status( $id, $status ) {
		global $wpdb;

		$result = $wpdb->update(
			$this->get_table(),
			array( 'status' => $status ),
			array( 'id' => $id ),
			array( '%s' ),
			array( '%d' )
		);

		$this->flush_cache();

		return false !== $result;
	}

	/**
	 * Bulk update status.
	 *
	 * @param array  $ids    Rule IDs.
	 * @param string $status New status.
	 *
	 * @return int Number of rows affected.
	 */
	public function bulk_update_status( $ids, $status ) {
		global $wpdb;

		$ids = array_map( 'absint', $ids );
		if ( empty( $ids ) ) {
			return 0;
		}

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$table        = $this->get_table();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->query(
			$wpdb->prepare(
				// $table is internal; $placeholders is a list of %d placeholders for absint'd IDs.
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"UPDATE {$table} SET status = %s WHERE id IN ({$placeholders})",
				array_merge( array( $status ), $ids )
			)
		);

		$this->flush_cache();

		return $result;
	}

	/**
	 * Bulk delete rules.
	 *
	 * @param array $ids Rule IDs.
	 *
	 * @return int Number of rows deleted.
	 */
	public function bulk_delete( $ids ) {
		global $wpdb;

		$ids = array_map( 'absint', $ids );
		if ( empty( $ids ) ) {
			return 0;
		}

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$table        = $this->get_table();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->query(
			$wpdb->prepare(
				// $table is internal; $placeholders is a list of %d placeholders for absint'd IDs.
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
				"DELETE FROM {$table} WHERE id IN ({$placeholders})",
				$ids
			)
		);

		$this->flush_cache();

		return $result;
	}

	/**
	 * Add one completed order (and its revenue) to a rule's running totals.
	 *
	 * @param int   $rule_id Rule ID.
	 * @param float $revenue Base-price revenue to add.
	 *
	 * @return void
	 */
	public function add_order_stats( $rule_id, $revenue ) {
		global $wpdb;

		$table = $this->get_table();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				// $table is internally controlled.
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"UPDATE {$table} SET orders_count = orders_count + 1, revenue_total = revenue_total + %f WHERE id = %d",
				(float) $revenue,
				(int) $rule_id
			)
		);

		$this->flush_cache();
	}

	/**
	 * Roll back one completed order (and its revenue) from a rule's totals.
	 *
	 * Clamped at zero so totals never go negative if hooks fire unexpectedly.
	 *
	 * @param int   $rule_id Rule ID.
	 * @param float $revenue Base-price revenue to subtract.
	 *
	 * @return void
	 */
	public function remove_order_stats( $rule_id, $revenue ) {
		global $wpdb;

		$table = $this->get_table();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				// $table is internally controlled.
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"UPDATE {$table} SET orders_count = GREATEST(0, orders_count - 1), revenue_total = GREATEST(0, revenue_total - %f) WHERE id = %d",
				(float) $revenue,
				(int) $rule_id
			)
		);

		$this->flush_cache();
	}

	/**
	 * Get aggregated usage totals across all rules (for the dashboard).
	 *
	 * @return array{orders:int,revenue:float} Summed order count and base-price revenue.
	 */
	public function get_totals() {
		global $wpdb;

		$table = $this->get_table();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row( "SELECT COALESCE(SUM(orders_count),0) AS orders, COALESCE(SUM(revenue_total),0) AS revenue FROM {$table}" );

		return array(
			'orders'  => $row ? (int) $row->orders : 0,
			'revenue' => $row ? (float) $row->revenue : 0.0,
		);
	}

	/**
	 * Get active rules for cart processing.
	 *
	 * @return array Array of Rule objects.
	 */
	public function get_active_rules() {
		$cached = wp_cache_get( self::CACHE_KEY_ACTIVE, self::CACHE_GROUP );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		global $wpdb;

		$table = $this->get_table();
		$now   = current_time( 'mysql' );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT * FROM {$table}
				WHERE status = %s
				AND (start_date IS NULL OR start_date <= %s)
				AND (end_date IS NULL OR end_date >= %s)
				ORDER BY priority ASC",
				Rule::STATUS_ACTIVE,
				$now,
				$now
			)
		);

		$rules = array();
		foreach ( $results as $row ) {
			$rules[] = Rule::from_db( $row );
		}

		// Cache briefly: the cart reads this on every recalculation within a request.
		wp_cache_set( self::CACHE_KEY_ACTIVE, $rules, self::CACHE_GROUP, MINUTE_IN_SECONDS );

		return $rules;
	}

	/**
	 * Get rules applicable to a specific product.
	 *
	 * @param int $product_id Product ID.
	 *
	 * @return array Array of Rule objects.
	 */
	public function get_rules_for_product( $product_id ) {
		$active_rules     = $this->get_active_rules();
		$applicable_rules = array();

		foreach ( $active_rules as $rule ) {
			if ( $this->rule_applies_to_product( $rule, $product_id ) ) {
				$applicable_rules[] = $rule;
			}
		}

		return $applicable_rules;
	}

	/**
	 * Check if a rule applies to a product.
	 *
	 * @param Rule $rule       Rule object.
	 * @param int  $product_id Product ID.
	 *
	 * @return bool
	 */
	private function rule_applies_to_product( Rule $rule, $product_id ) {
		switch ( $rule->apply_to ) {
			case Rule::APPLY_ALL_PRODUCTS:
				return true;

			case Rule::APPLY_SPECIFIC_PRODUCTS:
				return in_array( $product_id, $rule->buy_product_ids, true );

			case Rule::APPLY_SPECIFIC_CATEGORIES:
				$product_categories = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );
				return ! empty( array_intersect( $product_categories, $rule->category_ids ) );

			default:
				return false;
		}
	}
}
