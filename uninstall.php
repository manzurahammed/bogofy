<?php
/**
 * Plugin uninstall handler.
 *
 * Fired when the plugin is uninstalled.
 *
 * @package BuyOneGetOne
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Define plugin constants if not already defined.
if ( ! defined( 'BOGO_PLUGIN_DIR' ) ) {
	define( 'BOGO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

// Load autoloader for cleanup classes.
if ( file_exists( BOGO_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once BOGO_PLUGIN_DIR . 'vendor/autoload.php';
}

/**
 * Perform cleanup on uninstall.
 *
 * @return void
 */
function bogo_uninstall_cleanup() {
	global $wpdb;

	// Delete plugin options.
	delete_option( 'bogo_settings' );
	delete_option( 'bogo_version' );
	delete_option( 'bogo_db_version' );

	// Drop custom tables.
	$table_name = $wpdb->prefix . 'bogo_rules';

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );

	// Clean up any transients.
	$wpdb->query(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_bogo_%'"
	);
	$wpdb->query(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_timeout_bogo_%'"
	);

	// Clear scheduled hooks.
	wp_clear_scheduled_hook( 'bogo_daily_cleanup' );
}

// Run cleanup.
bogo_uninstall_cleanup();
