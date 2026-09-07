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
use Bogofy\Frontend\Assets;
use Bogofy\Frontend\CartDisplay;
use Bogofy\Frontend\StoreApi;
use Bogofy\Models\RuleRepository;
use Bogofy\Orders\OrderTracker;
use Bogofy\Database\Schema;
use Bogofy\Database\Migrator;

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
	 * @return void
	 */
	private function register_services() {
		$this->container->set(
			RuleRepository::class,
			function () {
				return new RuleRepository();
			}
		);

		$this->container->set(
			OrderTracker::class,
			function ( Container $c ) {
				return new OrderTracker( $c->get( RuleRepository::class ) );
			}
		);

		$this->container->set(
			EligibilityChecker::class,
			function () {
				return new EligibilityChecker();
			}
		);

		$this->container->set(
			FreeItemManager::class,
			function () {
				return new FreeItemManager();
			}
		);

		$this->container->set(
			DiscountApplier::class,
			function ( Container $c ) {
				return new DiscountApplier(
					$c->get( EligibilityChecker::class ),
					$c->get( FreeItemManager::class )
				);
			}
		);

		$this->container->set(
			CartHandler::class,
			function ( Container $c ) {
				return new CartHandler(
					$c->get( RuleRepository::class ),
					$c->get( EligibilityChecker::class ),
					$c->get( DiscountApplier::class ),
					$c->get( FreeItemManager::class )
				);
			}
		);

		$this->container->set(
			Admin::class,
			function () {
				return new Admin();
			}
		);

		$this->container->set(
			RestApi::class,
			function ( Container $c ) {
				return new RestApi( $c->get( RuleRepository::class ) );
			}
		);

		$this->container->set(
			ProductPage::class,
			function ( Container $c ) {
				return new ProductPage( $c->get( RuleRepository::class ) );
			}
		);

		$this->container->set(
			Assets::class,
			function () {
				return new Assets();
			}
		);

		$this->container->set(
			CartDisplay::class,
			function ( Container $c ) {
				return new CartDisplay( $c->get( RuleRepository::class ) );
			}
		);

		$this->container->set(
			StoreApi::class,
			function () {
				return new StoreApi();
			}
		);
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

		// Apply pending schema migrations (e.g. new tables/columns) on upgrade,
		// so existing installs don't require a manual deactivate/reactivate.
		Migrator::run();
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

		// Order tracking: stamp rule + base price onto BOGO line items at checkout,
		// then record/roll back each rule's totals as the order status changes.
		$order_tracker = $this->container->get( OrderTracker::class );
		$this->loader->register_action( 'woocommerce_checkout_create_order_line_item', $order_tracker, 'persist_line_meta', 20, 3 );
		$this->loader->register_action( 'woocommerce_order_status_changed', $order_tracker, 'on_status_changed', 20, 4 );

		// Storefront styles + block cart/checkout script.
		$assets = $this->container->get( Assets::class );
		$this->loader->register_action( 'wp_enqueue_scripts', $assets, 'enqueue' );

		// Expose BOGO data to the block cart/checkout via the Store API.
		$store_api = $this->container->get( StoreApi::class );
		$this->loader->register_action( 'woocommerce_blocks_loaded', $store_api, 'register' );

		// Gift note under free cart items (classic + block cart).
		$cart_display = $this->container->get( CartDisplay::class );
		$this->loader->register_filter( 'woocommerce_get_item_data', $cart_display, 'add_item_data', 10, 2 );

		// Product page display.
		$product_page = $this->container->get( ProductPage::class );
		$this->loader->register_action( 'woocommerce_single_product_summary', $product_page, 'display_bogo_message', 25 );
		// Anchor the loop strip to the image base (after the thumbnail, before the title).
		$this->loader->register_action( 'woocommerce_before_shop_loop_item_title', $product_page, 'display_bogo_badge', 15 );
	}
}
