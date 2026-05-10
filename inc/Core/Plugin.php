<?php
/**
 * Main Plugin class.
 *
 * @package BuyOneGetOne\Core
 */

namespace BuyOneGetOne\Core;

use BuyOneGetOne\Admin\Admin;
use BuyOneGetOne\Admin\RestApi;
use BuyOneGetOne\Cart\CartHandler;
use BuyOneGetOne\Frontend\ProductPage;
use BuyOneGetOne\Frontend\CartDisplay;
use BuyOneGetOne\Database\Schema;

/**
 * Class Plugin
 *
 * Main plugin singleton class that initializes all components.
 */
class Plugin {

	/**
	 * Plugin instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Loader instance.
	 *
	 * @var Loader
	 */
	private $loader;

	/**
	 * Get plugin instance.
	 *
	 * @return Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Plugin constructor.
	 */
	private function __construct() {
		$this->loader = new Loader();
		$this->maybe_create_tables();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->loader->run();
	}

	/**
	 * Check if database tables exist and create if needed.
	 *
	 * @return void
	 */
	private function maybe_create_tables() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'bogo_rules';

		// Check if table exists.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$table_name
			)
		);

		if ( $table_exists !== $table_name ) {
			Schema::create_tables();
		}
	}

	/**
	 * Register all admin-related hooks.
	 *
	 * @return void
	 */
	private function define_admin_hooks() {
		$admin = new Admin();

		$this->loader->add_action( 'admin_menu', $admin, 'register_admin_menu' );
		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_scripts' );

		// REST API.
		$rest_api = new RestApi();
		$this->loader->add_action( 'rest_api_init', $rest_api, 'register_routes' );
	}

	/**
	 * Register all public-facing hooks.
	 *
	 * @return void
	 */
	private function define_public_hooks() {
		// Cart handling.
		$cart_handler = new CartHandler();
		$this->loader->add_action( 'woocommerce_before_calculate_totals', $cart_handler, 'apply_bogo_rules', 10, 1 );
		$this->loader->add_action( 'woocommerce_add_to_cart', $cart_handler, 'on_add_to_cart', 10, 6 );
		$this->loader->add_action( 'woocommerce_cart_item_removed', $cart_handler, 'on_cart_item_removed', 10, 2 );
		$this->loader->add_action( 'woocommerce_update_cart_action_cart_updated', $cart_handler, 'on_cart_updated', 10, 1 );
		$this->loader->add_filter( 'woocommerce_cart_item_quantity', $cart_handler, 'filter_cart_item_quantity', 10, 3 );

		// Product page display.
		$product_page = new ProductPage();
		$this->loader->add_action( 'woocommerce_single_product_summary', $product_page, 'display_bogo_message', 25 );
		$this->loader->add_action( 'woocommerce_after_shop_loop_item_title', $product_page, 'display_bogo_badge', 15 );

		// Cart display.
		$cart_display = new CartDisplay();
		$this->loader->add_filter( 'woocommerce_get_item_data', $cart_display, 'add_bogo_label', 10, 2 );
		$this->loader->add_filter( 'woocommerce_cart_item_price', $cart_display, 'modify_free_item_price_display', 10, 3 );
	}
}
