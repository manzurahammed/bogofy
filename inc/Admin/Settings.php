<?php
/**
 * Plugin settings handler.
 *
 * @package BuyOneGetOne\Admin
 */

namespace BuyOneGetOne\Admin;

/**
 * Class Settings
 *
 * Handles plugin settings.
 */
class Settings {

	/**
	 * Option name.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'bogo_settings';

	/**
	 * Default settings.
	 *
	 * @var array
	 */
	private static $defaults = array(
		'enabled'                    => true,
		'free_item_label'            => 'FREE (BOGO Deal)',
		'cart_notice_text'           => 'Congratulations! You got a free item with your purchase.',
		'show_product_page_messages' => true,
		'show_shop_badges'           => true,
		'stack_with_coupons'         => true,
	);

	/**
	 * Get all settings.
	 *
	 * @return array
	 */
	public static function get_all() {
		$settings = get_option( self::OPTION_NAME, array() );
		return wp_parse_args( $settings, self::$defaults );
	}

	/**
	 * Get a single setting.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Default value.
	 *
	 * @return mixed
	 */
	public static function get( $key, $default = null ) {
		$settings = self::get_all();

		if ( isset( $settings[ $key ] ) ) {
			return $settings[ $key ];
		}

		if ( null !== $default ) {
			return $default;
		}

		return self::$defaults[ $key ] ?? null;
	}

	/**
	 * Update settings.
	 *
	 * @param array $new_settings New settings to save.
	 *
	 * @return bool
	 */
	public static function update( $new_settings ) {
		$current = self::get_all();
		$sanitized = self::sanitize( $new_settings );
		$merged = wp_parse_args( $sanitized, $current );

		return update_option( self::OPTION_NAME, $merged );
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array $settings Settings to sanitize.
	 *
	 * @return array
	 */
	public static function sanitize( $settings ) {
		$sanitized = array();

		if ( isset( $settings['enabled'] ) ) {
			$sanitized['enabled'] = (bool) $settings['enabled'];
		}

		if ( isset( $settings['free_item_label'] ) ) {
			$sanitized['free_item_label'] = sanitize_text_field( $settings['free_item_label'] );
		}

		if ( isset( $settings['cart_notice_text'] ) ) {
			$sanitized['cart_notice_text'] = sanitize_text_field( $settings['cart_notice_text'] );
		}

		if ( isset( $settings['show_product_page_messages'] ) ) {
			$sanitized['show_product_page_messages'] = (bool) $settings['show_product_page_messages'];
		}

		if ( isset( $settings['show_shop_badges'] ) ) {
			$sanitized['show_shop_badges'] = (bool) $settings['show_shop_badges'];
		}

		if ( isset( $settings['stack_with_coupons'] ) ) {
			$sanitized['stack_with_coupons'] = (bool) $settings['stack_with_coupons'];
		}

		return $sanitized;
	}

	/**
	 * Check if plugin is enabled.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		return (bool) self::get( 'enabled', true );
	}

	/**
	 * Reset settings to defaults.
	 *
	 * @return bool
	 */
	public static function reset() {
		return update_option( self::OPTION_NAME, self::$defaults );
	}

	/**
	 * Delete all settings.
	 *
	 * @return bool
	 */
	public static function delete() {
		return delete_option( self::OPTION_NAME );
	}
}
