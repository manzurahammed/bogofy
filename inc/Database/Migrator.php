<?php
/**
 * Database migration manager.
 *
 * @package Bogofy\Database
 */

namespace Bogofy\Database;

/**
 * Class Migrator
 *
 * Handles database version migrations.
 */
class Migrator {

	/**
	 * Current database version.
	 *
	 * @var string
	 */
	const DB_VERSION = '1.1.0';

	/**
	 * Option name for storing DB version.
	 *
	 * @var string
	 */
	const DB_VERSION_OPTION = 'bogo_db_version';

	/**
	 * Run pending migrations.
	 *
	 * @return void
	 */
	public static function run() {
		$current_version = get_option( self::DB_VERSION_OPTION, '0.0.0' );

		if ( version_compare( $current_version, self::DB_VERSION, '<' ) ) {
			self::migrate( $current_version );
			update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
		}
	}

	/**
	 * Run migrations from current version.
	 *
	 * @param string $from_version Current version.
	 *
	 * @return void
	 */
	private static function migrate( $from_version ) {
		// Migration 1.0.0 - Initial setup.
		if ( version_compare( $from_version, '1.0.0', '<' ) ) {
			self::migrate_to_1_0_0();
		}

		// Migration 1.1.0 - Add orders_count / revenue_total columns to bogo_rules.
		if ( version_compare( $from_version, '1.1.0', '<' ) ) {
			self::migrate_to_1_1_0();
		}

		// Future migrations can be added here.
	}

	/**
	 * Migration to version 1.1.0.
	 *
	 * Adds the `orders_count` and `revenue_total` columns to the rules table.
	 * Schema::create_tables() is idempotent (dbDelta), so re-running it simply
	 * adds the new columns to the existing table.
	 *
	 * @return void
	 */
	private static function migrate_to_1_1_0() {
		Schema::create_tables();
	}

	/**
	 * Migration to version 1.0.0.
	 *
	 * @return void
	 */
	private static function migrate_to_1_0_0() {
		// Initial table creation is handled by Schema::create_tables().
		// This method is for any additional setup needed after table creation.

		// Ensure tables exist.
		Schema::create_tables();
	}

	/**
	 * Check if migrations are needed.
	 *
	 * @return bool
	 */
	public static function needs_migration() {
		$current_version = get_option( self::DB_VERSION_OPTION, '0.0.0' );
		return version_compare( $current_version, self::DB_VERSION, '<' );
	}

	/**
	 * Get current database version.
	 *
	 * @return string
	 */
	public static function get_current_version() {
		return get_option( self::DB_VERSION_OPTION, '0.0.0' );
	}
}
