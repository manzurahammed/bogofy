<?php
/**
 * Plugin deactivation handler.
 *
 * @package BuyOneGetOne\Core
 */

namespace BuyOneGetOne\Core;

/**
 * Class Deactivator
 *
 * Handles plugin deactivation tasks.
 */
class Deactivator {

	/**
	 * Deactivate the plugin.
	 *
	 * @return void
	 */
	public static function deactivate() {
		// Clear any scheduled events.
		wp_clear_scheduled_hook( 'bogo_daily_cleanup' );

		// Flush rewrite rules.
		flush_rewrite_rules();
	}
}
