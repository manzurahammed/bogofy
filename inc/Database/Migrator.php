<?php
/**
 * Database migration manager.
 *
 * @package BuyOneGetOne\Database
 */

namespace BuyOneGetOne\Database;

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
	const DB_VERSION = '1.0.0';

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

		// Future migrations can be added here.
		// if ( version_compare( $from_version, '1.1.0', '<' ) ) {
		//     self::migrate_to_1_1_0();
		// }
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
