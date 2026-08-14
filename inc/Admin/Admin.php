<?php
/**
 * Admin functionality.
 *
 * @package BuyOneGetOne\Admin
 */

namespace BuyOneGetOne\Admin;

/**
 * Class Admin
 *
 * Handles admin-related functionality.
 */
class Admin {

	/**
	 * Admin page slug.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'bogofy';

	/**
	 * Register admin menu.
	 *
	 * @return void
	 */
	public function register_admin_menu() {
		add_menu_page(
			__( 'Bogofy', 'bogofy' ),
			__( 'Bogofy', 'bogofy' ),
			'manage_woocommerce',
			self::PAGE_SLUG,
			array( $this, 'render_admin_page' ),
			'dashicons-tag',
			56
		);
	}

	/**
	 * Render admin page.
	 *
	 * @return void
	 */
	public function render_admin_page() {
		?>
		<div id="bogo-admin-root" class="bogo-admin"></div>
		<?php
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $hook Current admin page hook.
	 *
	 * @return void
	 */
	public function enqueue_scripts( $hook ) {
		// Only load on our admin page.
		if ( 'toplevel_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}

		$manifest_path = BOGO_PLUGIN_DIR . 'assets/build/.vite/manifest.json';

		// Check if we're in development mode.
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && file_exists( BOGO_PLUGIN_DIR . 'frontend/src/main.jsx' ) ) {
			// Development mode - load from Vite dev server.
			$this->enqueue_dev_scripts();
		} elseif ( file_exists( $manifest_path ) ) {
			// Production mode - load built assets.
			$this->enqueue_production_scripts( $manifest_path );
		} else {
			// Fallback - try to load without manifest.
			$this->enqueue_fallback_scripts();
		}

		// Localize script data.
		wp_localize_script(
			'bogo-admin',
			'bogoAdmin',
			array(
				'apiUrl'    => rest_url( 'bogofy/v1' ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'adminUrl'  => admin_url(),
				'pluginUrl' => BOGO_PLUGIN_URL,
			)
		);
	}

	/**
	 * Enqueue development scripts from Vite dev server.
	 *
	 * @return void
	 */
	private function enqueue_dev_scripts() {
		// Vite client for HMR.
		wp_enqueue_script(
			'vite-client',
			'http://localhost:3000/@vite/client',
			array(),
			null, // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
			true
		);

		// Main React app.
		wp_enqueue_script(
			'bogo-admin',
			'http://localhost:3000/src/main.jsx',
			array( 'vite-client', 'wp-i18n' ),
			null, // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
			true
		);

		$this->set_translations();

		// Add type="module" to script tags.
		add_filter( 'script_loader_tag', array( $this, 'add_module_type' ), 10, 3 );
	}

	/**
	 * Enqueue production scripts from built assets.
	 *
	 * @param string $manifest_path Path to manifest file.
	 *
	 * @return void
	 */
	private function enqueue_production_scripts( $manifest_path ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$manifest = json_decode( file_get_contents( $manifest_path ), true );

		if ( ! $manifest || ! isset( $manifest['src/main.jsx'] ) ) {
			return;
		}

		$entry = $manifest['src/main.jsx'];

		// Enqueue CSS.
		if ( isset( $entry['css'] ) ) {
			foreach ( $entry['css'] as $css_file ) {
				wp_enqueue_style(
					'bogo-admin',
					BOGO_PLUGIN_URL . 'assets/build/' . $css_file,
					array(),
					BOGO_VERSION
				);
			}
		}

		// Enqueue JS.
		wp_enqueue_script(
			'bogo-admin',
			BOGO_PLUGIN_URL . 'assets/build/' . $entry['file'],
			array( 'wp-i18n' ),
			BOGO_VERSION,
			true
		);

		$this->set_translations();

		// Add type="module" to script tag.
		add_filter( 'script_loader_tag', array( $this, 'add_module_type' ), 10, 3 );
	}

	/**
	 * Enqueue fallback scripts when no manifest exists.
	 *
	 * @return void
	 */
	private function enqueue_fallback_scripts() {
		// Try to load main.js directly.
		$js_path = BOGO_PLUGIN_DIR . 'assets/build/main.js';
		if ( file_exists( $js_path ) ) {
			wp_enqueue_script(
				'bogo-admin',
				BOGO_PLUGIN_URL . 'assets/build/main.js',
				array( 'wp-i18n' ),
				BOGO_VERSION,
				true
			);

			$this->set_translations();

			add_filter( 'script_loader_tag', array( $this, 'add_module_type' ), 10, 3 );
		}

		// Try to load main.css directly.
		$css_path = BOGO_PLUGIN_DIR . 'assets/build/main.css';
		if ( file_exists( $css_path ) ) {
			wp_enqueue_style(
				'bogo-admin',
				BOGO_PLUGIN_URL . 'assets/build/main.css',
				array(),
				BOGO_VERSION
			);
		}
	}

	/**
	 * Register JS translations for the admin app.
	 *
	 * Lets strings wrapped in @wordpress/i18n functions be translated via the
	 * JSON translation files in /languages.
	 *
	 * @return void
	 */
	private function set_translations() {
		wp_set_script_translations( 'bogo-admin', 'bogofy', BOGO_PLUGIN_DIR . 'languages' );
	}

	/**
	 * Add type="module" to script tags.
	 *
	 * @param string $tag    Script tag HTML.
	 * @param string $handle Script handle.
	 * @param string $src    Script source URL.
	 *
	 * @return string
	 */
	public function add_module_type( $tag, $handle, $src ) {
		if ( 'bogo-admin' === $handle || 'vite-client' === $handle ) {
			$tag = str_replace( '<script ', '<script type="module" ', $tag );
		}
		return $tag;
	}
}
