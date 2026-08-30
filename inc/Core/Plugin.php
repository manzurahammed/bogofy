<?php
/**
 * Main Plugin class.
 *
 * @package Bogofy\Core
 */

namespace Bogofy\Core;

use Bogofy\Admin\Admin;
use Bogofy\Admin\RestApi;
use Bogofy\Cart\CartHandler;
use Bogofy\Cart\DiscountApplier;
use Bogofy\Cart\EligibilityChecker;
use Bogofy\Cart\FreeItemManager;
use Bogofy\Frontend\ProductPage;
use Bogofy\Frontend\CartDisplay;
use Bogofy\Models\RuleRepository;
use Bogofy\Database\Schema;

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
	 * Service container.
	 *
	 * @var Container
	 */
	private $container;

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
		$this->loader    = new Loader();
		$this->container = new Container();

		$this->register_services();
		$this->maybe_create_tables();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->loader->run();
	}

	/**
	 * Register all plugin services in the container.
	 *
	 * This is the single place where the object graph is composed. Each service
	 * is a shared instance resolved lazily on first use.
	 *
	 * @return void
	 */
	private function register_services() {
		$this->container->set( RuleRepository::class, function () {
			return new RuleRepository();
		} );

		$this->container->set( EligibilityChecker::class, function () {
			return new EligibilityChecker();
		} );

		$this->container->set( FreeItemManager::class, function () {
			return new FreeItemManager();
		} );

		$this->container->set( DiscountApplier::class, function ( Container $c ) {
			return new DiscountApplier(
				$c->get( EligibilityChecker::class ),
				$c->get( FreeItemManager::class )
			);
		} );

		$this->container->set( CartHandler::class, function ( Container $c ) {
			return new CartHandler(
				$c->get( RuleRepository::class ),
				$c->get( EligibilityChecker::class ),
				$c->get( DiscountApplier::class ),
				$c->get( FreeItemManager::class )
			);
		} );

		$this->container->set( Admin::class, function () {
			return new Admin();
		} );

		$this->container->set( RestApi::class, function ( Container $c ) {
			return new RestApi( $c->get( RuleRepository::class ) );
		} );

		$this->container->set( ProductPage::class, function ( Container $c ) {
			return new ProductPage( $c->get( RuleRepository::class ) );
		} );

		$this->container->set( CartDisplay::class, function () {
			return new CartDisplay();
		} );
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
		$admin = $this->container->get( Admin::class );

		$this->loader->register_action( 'admin_menu', $admin, 'register_admin_menu' );
		$this->loader->register_action( 'admin_enqueue_scripts', $admin, 'enqueue_scripts' );

		// REST API.
		$rest_api = $this->container->get( RestApi::class );
		$this->loader->register_action( 'rest_api_init', $rest_api, 'register_routes' );
	}

	/**
	 * Register all public-facing hooks.
	 *
	 * @return void
	 */
	private function define_public_hooks() {
		// Cart handling.
		$cart_handler = $this->container->get( CartHandler::class );

		// Reconcile free item lines on cart mutations and on each cart load (never during totals calc).
		$this->loader->register_action( 'woocommerce_add_to_cart', $cart_handler, 'on_add_to_cart', 20, 6 );
		$this->loader->register_action( 'woocommerce_cart_item_removed', $cart_handler, 'on_cart_item_removed', 20, 2 );
		$this->loader->register_action( 'woocommerce_cart_item_restored', $cart_handler, 'sync_free_items', 20 );
		$this->loader->register_action( 'woocommerce_after_cart_item_quantity_update', $cart_handler, 'sync_free_items', 20 );
		$this->loader->register_action( 'woocommerce_update_cart_action_cart_updated', $cart_handler, 'on_cart_updated', 20, 1 );
		$this->loader->register_action( 'woocommerce_cart_loaded_from_session', $cart_handler, 'sync_free_items', 20 );
		$this->loader->register_action( 'woocommerce_check_cart_items', $cart_handler, 'sync_free_items', 20 );

		// Price free/discounted items during totals calculation only.
		$this->loader->register_action( 'woocommerce_before_calculate_totals', $cart_handler, 'apply_bogo_prices', 10, 1 );

		$this->loader->register_filter( 'woocommerce_cart_item_quantity', $cart_handler, 'filter_cart_item_quantity', 10, 3 );

		// Product page display.
		$product_page = $this->container->get( ProductPage::class );
		$this->loader->register_action( 'woocommerce_single_product_summary', $product_page, 'display_bogo_message', 25 );
		$this->loader->register_action( 'woocommerce_after_shop_loop_item_title', $product_page, 'display_bogo_badge', 15 );

		// Cart display.
		$cart_display = $this->container->get( CartDisplay::class );
		$this->loader->register_filter( 'woocommerce_get_item_data', $cart_display, 'add_bogo_label', 10, 2 );
		$this->loader->register_filter( 'woocommerce_cart_item_price', $cart_display, 'modify_free_item_price_display', 10, 3 );
	}
}
