<?php
/**
 * Storefront asset loading.
 *
 * @package Bogofy\Frontend
 */

namespace Bogofy\Frontend;

use Bogofy\Admin\Settings;

/**
 * Class Assets
 *
 * Enqueues the storefront stylesheet and the block cart/checkout script.
 */
class Assets {

	/**
	 * Handle for the block cart/checkout script.
	 *
	 * @var string
	 */
	const CART_HANDLE = 'bogofy-cart-blocks';

	/**
	 * Enqueue the storefront stylesheet and, on the cart/checkout, the block script.
	 *
	 * @return void
	 */
	public function enqueue() {
		if ( ! Settings::is_enabled() ) {
			return;
		}

		wp_enqueue_style(
			'bogofy-storefront',
			BOGO_PLUGIN_URL . 'assets/css/storefront.css',
			array(),
			$this->version( 'assets/css/storefront.css' )
		);

		// Block cart/checkout integration: only where those blocks render and when
		// the cart gift styling is enabled.
		if (
			Settings::get( 'show_cart_gift' )
			&& function_exists( 'is_cart' )
			&& ( is_cart() || is_checkout() )
		) {
			wp_enqueue_script(
				self::CART_HANDLE,
				BOGO_PLUGIN_URL . 'assets/build/cart-blocks.js',
				array( 'wc-blocks-checkout', 'wp-plugins', 'wp-element', 'wp-i18n' ),
				$this->version( 'assets/build/cart-blocks.js' ),
				true
			);
		}
	}

	/**
	 * Cache-busting version for a bundled asset.
	 *
	 * @param string $relative_path Path relative to the plugin directory.
	 *
	 * @return string
	 */
	private function version( $relative_path ) {
		$path = BOGO_PLUGIN_DIR . $relative_path;
		return file_exists( $path ) ? (string) filemtime( $path ) : BOGO_VERSION;
	}
}
