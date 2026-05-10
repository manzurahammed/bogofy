<?php
/**
 * Database schema handler.
 *
 * @package BuyOneGetOne\Database
 */

namespace BuyOneGetOne\Database;

/**
 * Class Schema
 *
 * Handles database table creation.
 */
class Schema {

	/**
	 * Table name for BOGO rules.
	 *
	 * @var string
	 */
	const RULES_TABLE = 'bogo_rules';

	/**
	 * Get the full table name with prefix.
	 *
	 * @param string $table Table name without prefix.
	 *
	 * @return string
	 */
	public static function get_table_name( $table ) {
		global $wpdb;
		return $wpdb->prefix . $table;
	}

	/**
	 * Create all database tables.
	 *
	 * @return void
	 */
	public static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = self::get_table_name( self::RULES_TABLE );

		$sql = "CREATE TABLE {$table_name} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			title VARCHAR(255) NOT NULL,
			rule_type VARCHAR(50) NOT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'active',
			buy_quantity INT(11) NOT NULL DEFAULT 1,
			free_quantity INT(11) NOT NULL DEFAULT 1,
			discount_type VARCHAR(20) NOT NULL DEFAULT 'free',
			discount_value DECIMAL(10,2) NOT NULL DEFAULT 100.00,
			apply_to VARCHAR(20) NOT NULL DEFAULT 'specific_products',
			buy_product_ids TEXT,
			free_product_ids TEXT,
			category_ids TEXT,
			max_free_qty INT(11) DEFAULT NULL,
			message_template VARCHAR(500) DEFAULT '',
			priority INT(11) NOT NULL DEFAULT 10,
			start_date DATETIME DEFAULT NULL,
			end_date DATETIME DEFAULT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_status (status),
			KEY idx_priority (priority),
			KEY idx_dates (start_date, end_date),
			KEY idx_rule_type (rule_type)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Drop all database tables.
	 *
	 * @return void
	 */
	public static function drop_tables() {
		global $wpdb;

		$table_name = self::get_table_name( self::RULES_TABLE );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
	}
}
