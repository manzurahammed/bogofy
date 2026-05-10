<?php
/**
 * Plugin activation handler.
 *
 * @package BuyOneGetOne\Core
 */

namespace BuyOneGetOne\Core;

use BuyOneGetOne\Database\Schema;
use BuyOneGetOne\Database\Migrator;

/**
 * Class Activator
 *
 * Handles plugin activation tasks.
 */
class Activator {

	/**
	 * Activate the plugin.
	 *
	 * @return void
	 */
	public static function activate() {
		// Check PHP version.
		if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
			deactivate_plugins( BOGO_PLUGIN_BASENAME );
			wp_die(
				esc_html__( 'Buy One Get One requires PHP 7.4 or higher.', 'buy-one-get-one' ),
				'Plugin Activation Error',
				array( 'back_link' => true )
			);
		}

		// Check WordPress version.
		global $wp_version;
		if ( version_compare( $wp_version, '6.0', '<' ) ) {
			deactivate_plugins( BOGO_PLUGIN_BASENAME );
			wp_die(
				esc_html__( 'Buy One Get One requires WordPress 6.0 or higher.', 'buy-one-get-one' ),
				'Plugin Activation Error',
				array( 'back_link' => true )
			);
		}

		// Create database tables.
		Schema::create_tables();

		// Run migrations.
		Migrator::run();

		// Set default options.
		self::set_default_options();

		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Set default plugin options.
	 *
	 * @return void
	 */
	private static function set_default_options() {
		$defaults = array(
			'enabled'                    => true,
			'free_item_label'            => __( 'FREE (BOGO Deal)', 'buy-one-get-one' ),
			'cart_notice_text'           => __( 'Congratulations! You got a free item with your purchase.', 'buy-one-get-one' ),
			'show_product_page_messages' => true,
			'show_shop_badges'           => true,
			'stack_with_coupons'         => true,
		);

		if ( false === get_option( 'bogo_settings' ) ) {
			add_option( 'bogo_settings', $defaults );
		}

		// Store plugin version.
		update_option( 'bogo_version', BOGO_VERSION );
	}
}
