<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package BuyOneGetOne\Tests
 */

// Composer autoloader.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Load Brain Monkey.
require_once dirname( __DIR__ ) . '/vendor/antecedent/patchwork/Patchwork.php';

use Brain\Monkey;

/**
 * Set up Brain Monkey before tests.
 */
function bogo_tests_setup() {
	Monkey\setUp();

	// Define WordPress constants.
	if ( ! defined( 'ABSPATH' ) ) {
		define( 'ABSPATH', '/tmp/wordpress/' );
	}

	if ( ! defined( 'BOGO_VERSION' ) ) {
		define( 'BOGO_VERSION', '1.0.0' );
	}

	if ( ! defined( 'BOGO_PLUGIN_FILE' ) ) {
		define( 'BOGO_PLUGIN_FILE', dirname( __DIR__ ) . '/buy-one-get-one.php' );
	}

	if ( ! defined( 'BOGO_PLUGIN_DIR' ) ) {
		define( 'BOGO_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
	}

	if ( ! defined( 'BOGO_PLUGIN_URL' ) ) {
		define( 'BOGO_PLUGIN_URL', 'http://example.com/wp-content/plugins/buy-one-get-one/' );
	}

	if ( ! defined( 'BOGO_PLUGIN_BASENAME' ) ) {
		define( 'BOGO_PLUGIN_BASENAME', 'buy-one-get-one/buy-one-get-one.php' );
	}

	bogo_tests_stub_wp_functions();
}

/**
 * Tear down Brain Monkey after tests.
 */
function bogo_tests_teardown() {
	Monkey\tearDown();
}

/**
 * Stub WordPress functions that are commonly used.
 *
 * Must run after Monkey\setUp() for every test, because
 * Monkey\tearDown() clears all registered stubs.
 */
function bogo_tests_stub_wp_functions() {
	Monkey\Functions\stubs(
		array(
		'__'                     => function ( $text, $domain = 'default' ) {
			return $text;
		},
		'esc_html__'             => function ( $text, $domain = 'default' ) {
			return $text;
		},
		'esc_attr__'             => function ( $text, $domain = 'default' ) {
			return $text;
		},
		'esc_html'               => function ( $text ) {
			return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
		},
		'esc_attr'               => function ( $text ) {
			return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
		},
		'wp_kses_post'           => function ( $text ) {
			return $text;
		},
		'sanitize_text_field'    => function ( $text ) {
			return trim( strip_tags( $text ) );
		},
		'absint'                 => function ( $value ) {
			return abs( (int) $value );
		},
		'wp_json_encode'         => function ( $data ) {
			return json_encode( $data );
		},
		'wp_parse_args'          => function ( $args, $defaults ) {
			return array_merge( $defaults, $args );
		},
		'current_time'           => function ( $type ) {
			return 'mysql' === $type ? gmdate( 'Y-m-d H:i:s' ) : time();
		},
		'get_option'             => function ( $option, $default = false ) {
			return $default;
		},
		'update_option'          => function ( $option, $value ) {
			return true;
		},
		'add_option'             => function ( $option, $value ) {
			return true;
		},
		'delete_option'          => function ( $option ) {
			return true;
		},
		)
	);
}

// Minimal WP_Error stand-in for unit tests that run without WordPress.
if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		public $errors = array();

		public function __construct( $code = '', $message = '', $data = '' ) {
			if ( ! empty( $code ) ) {
				$this->errors[ $code ][] = $message;
			}
		}

		public function get_error_code() {
			$codes = array_keys( $this->errors );
			return $codes ? $codes[0] : '';
		}

		public function get_error_message( $code = '' ) {
			if ( '' === $code ) {
				$code = $this->get_error_code();
			}
			return isset( $this->errors[ $code ] ) ? $this->errors[ $code ][0] : '';
		}
	}
}
