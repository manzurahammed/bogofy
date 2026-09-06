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
 * Enqueues the storefront stylesheet used for BOGO messaging.
 */
class Assets {

	/**
	 * Enqueue the storefront stylesheet.
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
