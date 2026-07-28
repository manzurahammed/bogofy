<?php
/**
 * Plugin Name:       Buy One Get One
 * Description:       Create powerful BOGO (Buy One Get One) discount rules for WooCommerce — unlimited rules, scheduling, category support, and more. Completely free.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Manzur Ahammed
 * Author URI:        https://manzur.me
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       buy-one-get-one
 * Domain Path:       /languages
 * WC requires at least: 7.0
 * WC tested up to:   9.0
 *
 * @package BuyOneGetOne
 */

namespace BuyOneGetOne;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'BOGO_VERSION', '1.0.0' );
define( 'BOGO_PLUGIN_FILE', __FILE__ );
define( 'BOGO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BOGO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BOGO_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Composer autoloader.
if ( file_exists( BOGO_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once BOGO_PLUGIN_DIR . 'vendor/autoload.php';
}

/**
 * Check if WooCommerce is active.
 *
 * @return bool
 */
function bogo_is_woocommerce_active() {
	return class_exists( 'WooCommerce' );
}

/**
 * Display admin notice if WooCommerce is not active.
 *
 * @return void
 */
function bogo_woocommerce_missing_notice() {
	?>
	<div class="notice notice-error">
		<p>
			<?php
			echo esc_html__(
				'Buy One Get One requires WooCommerce to be installed and active.',
				'buy-one-get-one'
			);
			?>
		</p>
	</div>
	<?php
}

/**
 * Initialize the plugin.
 *
 * @return void
 */
function bogo_init() {
	// Check WooCommerce dependency.
	if ( ! bogo_is_woocommerce_active() ) {
		add_action( 'admin_notices', __NAMESPACE__ . '\\bogo_woocommerce_missing_notice' );
		return;
	}

	// Load text domain.
	load_plugin_textdomain( 'buy-one-get-one', false, dirname( BOGO_PLUGIN_BASENAME ) . '/languages' );

	// Initialize plugin.
	Core\Plugin::get_instance();
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\bogo_init' );

// Register activation hook.
register_activation_hook( __FILE__, array( Core\Activator::class, 'activate' ) );

// Register deactivation hook.
register_deactivation_hook( __FILE__, array( Core\Deactivator::class, 'deactivate' ) );

// Declare HPOS compatibility.
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	}
);
