<?php
/**
 * Formatting helper utilities.
 *
 * @package BuyOneGetOne\Helpers
 */

namespace BuyOneGetOne\Helpers;

/**
 * Class Formatter
 *
 * Provides formatting utilities.
 */
class Formatter {

	/**
	 * Format price for display.
	 *
	 * @param float $price Price value.
	 *
	 * @return string
	 */
	public static function format_price( $price ) {
		if ( function_exists( 'wc_price' ) ) {
			return wc_price( $price );
		}
		return number_format( $price, 2 );
	}

	/**
	 * Format date for display.
	 *
	 * @param string $date   Date string.
	 * @param string $format Date format (optional).
	 *
	 * @return string
	 */
	public static function format_date( $date, $format = '' ) {
		if ( empty( $date ) ) {
			return '';
		}

		if ( empty( $format ) ) {
			$format = get_option( 'date_format' );
		}

		return date_i18n( $format, strtotime( $date ) );
	}

	/**
	 * Format date and time for display.
	 *
	 * @param string $datetime DateTime string.
	 *
	 * @return string
	 */
	public static function format_datetime( $datetime ) {
		if ( empty( $datetime ) ) {
			return '';
		}

		$date_format = get_option( 'date_format' );
		$time_format = get_option( 'time_format' );

		return date_i18n( $date_format . ' ' . $time_format, strtotime( $datetime ) );
	}

	/**
	 * Format percentage for display.
	 *
	 * @param float $value Percentage value.
	 *
	 * @return string
	 */
	public static function format_percentage( $value ) {
		return number_format( $value, 0 ) . '%';
	}

	/**
	 * Format number with appropriate suffix.
	 *
	 * @param int $number Number to format.
	 *
	 * @return string
	 */
	public static function format_number_suffix( $number ) {
		$suffix = array( 'th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th' );

		if ( ( $number % 100 ) >= 11 && ( $number % 100 ) <= 13 ) {
			return $number . 'th';
		}

		return $number . $suffix[ $number % 10 ];
	}
}
