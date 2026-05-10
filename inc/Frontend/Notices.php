<?php
/**
 * WooCommerce notice integration.
 *
 * @package BuyOneGetOne\Frontend
 */

namespace BuyOneGetOne\Frontend;

use BuyOneGetOne\Admin\Settings;

/**
 * Class Notices
 *
 * Handles WooCommerce notice integration for BOGO.
 */
class Notices {

	/**
	 * Display BOGO applied notice.
	 *
	 * @param string $message Notice message.
	 *
	 * @return void
	 */
	public static function add_bogo_notice( $message = '' ) {
		if ( empty( $message ) ) {
			$message = Settings::get( 'cart_notice_text' );
		}

		if ( ! empty( $message ) && function_exists( 'wc_add_notice' ) ) {
			wc_add_notice( $message, 'success' );
		}
	}

	/**
	 * Display error notice.
	 *
	 * @param string $message Error message.
	 *
	 * @return void
	 */
	public static function add_error_notice( $message ) {
		if ( ! empty( $message ) && function_exists( 'wc_add_notice' ) ) {
			wc_add_notice( $message, 'error' );
		}
	}

	/**
	 * Display info notice.
	 *
	 * @param string $message Info message.
	 *
	 * @return void
	 */
	public static function add_info_notice( $message ) {
		if ( ! empty( $message ) && function_exists( 'wc_add_notice' ) ) {
			wc_add_notice( $message, 'notice' );
		}
	}
}
