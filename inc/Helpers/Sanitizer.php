<?php
/**
 * Input sanitization utilities.
 *
 * @package BuyOneGetOne\Helpers
 */

namespace BuyOneGetOne\Helpers;

/**
 * Class Sanitizer
 *
 * Provides input sanitization utilities.
 */
class Sanitizer {

	/**
	 * Sanitize rule type.
	 *
	 * @param string $type Rule type.
	 *
	 * @return string
	 */
	public static function sanitize_rule_type( $type ) {
		$valid_types = array(
			'buy_x_get_x',
			'buy_x_get_y',
			'buy_cat_get_free',
			'buy_x_get_x_discounted',
		);

		$type = sanitize_text_field( $type );
		return in_array( $type, $valid_types, true ) ? $type : 'buy_x_get_x';
	}

	/**
	 * Sanitize status.
	 *
	 * @param string $status Status value.
	 *
	 * @return string
	 */
	public static function sanitize_status( $status ) {
		$valid_statuses = array( 'active', 'inactive' );
		$status         = sanitize_text_field( $status );
		return in_array( $status, $valid_statuses, true ) ? $status : 'active';
	}

	/**
	 * Sanitize discount type.
	 *
	 * @param string $type Discount type.
	 *
	 * @return string
	 */
	public static function sanitize_discount_type( $type ) {
		$valid_types = array( 'free', 'percentage' );
		$type        = sanitize_text_field( $type );
		return in_array( $type, $valid_types, true ) ? $type : 'free';
	}

	/**
	 * Sanitize apply to type.
	 *
	 * @param string $type Apply to type.
	 *
	 * @return string
	 */
	public static function sanitize_apply_to( $type ) {
		$valid_types = array( 'specific_products', 'specific_categories', 'all_products' );
		$type        = sanitize_text_field( $type );
		return in_array( $type, $valid_types, true ) ? $type : 'specific_products';
	}

	/**
	 * Sanitize array of IDs.
	 *
	 * @param mixed $ids IDs to sanitize.
	 *
	 * @return array
	 */
	public static function sanitize_id_array( $ids ) {
		if ( ! is_array( $ids ) ) {
			$ids = array( $ids );
		}

		return array_filter( array_map( 'absint', $ids ) );
	}

	/**
	 * Sanitize datetime string.
	 *
	 * @param string $datetime DateTime string.
	 *
	 * @return string|null
	 */
	public static function sanitize_datetime( $datetime ) {
		if ( empty( $datetime ) ) {
			return null;
		}

		$timestamp = strtotime( $datetime );
		if ( false === $timestamp ) {
			return null;
		}

		return gmdate( 'Y-m-d H:i:s', $timestamp );
	}

	/**
	 * Sanitize positive integer.
	 *
	 * @param mixed $value   Value to sanitize.
	 * @param int   $default Default value.
	 * @param int   $min     Minimum value.
	 *
	 * @return int
	 */
	public static function sanitize_positive_int( $value, $default = 1, $min = 1 ) {
		$value = absint( $value );
		return $value >= $min ? $value : $default;
	}

	/**
	 * Sanitize percentage value.
	 *
	 * @param mixed $value Value to sanitize.
	 *
	 * @return float
	 */
	public static function sanitize_percentage( $value ) {
		$value = floatval( $value );
		return max( 0, min( 100, $value ) );
	}
}
