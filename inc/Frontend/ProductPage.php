<?php
/**
 * Product page display handler.
 *
 * @package BuyOneGetOne\Frontend
 */

namespace BuyOneGetOne\Frontend;

use BuyOneGetOne\Admin\Settings;
use BuyOneGetOne\Models\Rule;
use BuyOneGetOne\Models\RuleRepository;

/**
 * Class ProductPage
 *
 * Handles BOGO message display on product pages.
 */
class ProductPage {

	/**
	 * Rule repository.
	 *
	 * @var RuleRepository
	 */
	private $repository;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->repository = new RuleRepository();
	}

	/**
	 * Display BOGO message on single product page.
	 *
	 * @return void
	 */
	public function display_bogo_message() {
		if ( ! Settings::is_enabled() || ! Settings::get( 'show_product_page_messages' ) ) {
			return;
		}

		global $product;

		if ( ! $product ) {
			return;
		}

		$rules = $this->repository->get_rules_for_product( $product->get_id() );

		if ( empty( $rules ) ) {
			return;
		}

		foreach ( $rules as $rule ) {
			$this->render_product_message( $rule, $product );
		}
	}

	/**
	 * Display BOGO badge on shop loop items.
	 *
	 * @return void
	 */
	public function display_bogo_badge() {
		if ( ! Settings::is_enabled() || ! Settings::get( 'show_shop_badges' ) ) {
			return;
		}

		global $product;

		if ( ! $product ) {
			return;
		}

		$rules = $this->repository->get_rules_for_product( $product->get_id() );

		if ( empty( $rules ) ) {
			return;
		}

		// Display a simple badge.
		echo '<span class="bogo-badge">' . esc_html__( 'BOGO Deal!', 'bogofy' ) . '</span>';
	}

	/**
	 * Render product message for a rule.
	 *
	 * @param Rule        $rule    Rule object.
	 * @param \WC_Product $product Product object.
	 *
	 * @return void
	 */
	private function render_product_message( Rule $rule, $product ) {
		$message = $this->get_formatted_message( $rule, $product );

		if ( empty( $message ) ) {
			return;
		}

		printf(
			'<div class="bogo-offer-message">
				<span class="bogo-offer-icon">%s</span>
				<span class="bogo-offer-text">%s</span>
			</div>',
			'🎁',
			wp_kses_post( $message )
		);
	}

	/**
	 * Get formatted message for rule.
	 *
	 * @param Rule        $rule    Rule object.
	 * @param \WC_Product $product Product object.
	 *
	 * @return string
	 */
	private function get_formatted_message( Rule $rule, $product ) {
		// Use custom template if set.
		if ( ! empty( $rule->message_template ) ) {
			return $this->parse_message_template( $rule->message_template, $rule, $product );
		}

		// Generate default message based on rule type.
		return $this->get_default_message( $rule, $product );
	}

	/**
	 * Parse message template with placeholders.
	 *
	 * @param string      $template Template string.
	 * @param Rule        $rule     Rule object.
	 * @param \WC_Product $product  Product object.
	 *
	 * @return string
	 */
	private function parse_message_template( $template, Rule $rule, $product ) {
		$free_product_name = '';
		if ( ! empty( $rule->free_product_ids ) ) {
			$free_product = wc_get_product( $rule->free_product_ids[0] );
			if ( $free_product ) {
				$free_product_name = $free_product->get_name();
			}
		}

		$replacements = array(
			'{buy_qty}'      => $rule->buy_quantity,
			'{free_qty}'     => $rule->free_quantity,
			'{free_product}' => $free_product_name,
			'{discount}'     => $rule->discount_value,
			'{start_date}'   => $rule->start_date ? date_i18n( get_option( 'date_format' ), strtotime( $rule->start_date ) ) : '',
			'{end_date}'     => $rule->end_date ? date_i18n( get_option( 'date_format' ), strtotime( $rule->end_date ) ) : '',
		);

		return str_replace( array_keys( $replacements ), array_values( $replacements ), $template );
	}

	/**
	 * Get default message for rule type.
	 *
	 * @param Rule        $rule    Rule object.
	 * @param \WC_Product $product Product object.
	 *
	 * @return string
	 */
	private function get_default_message( Rule $rule, $product ) {
		switch ( $rule->rule_type ) {
			case Rule::TYPE_BUY_X_GET_X:
				return sprintf(
					/* translators: 1: buy quantity, 2: free quantity */
					__( 'Buy %1$d, Get %2$d FREE!', 'bogofy' ),
					$rule->buy_quantity,
					$rule->free_quantity
				);

			case Rule::TYPE_BUY_X_GET_Y:
				$free_product_name = '';
				if ( ! empty( $rule->free_product_ids ) ) {
					$free_product = wc_get_product( $rule->free_product_ids[0] );
					if ( $free_product ) {
						$free_product_name = $free_product->get_name();
					}
				}
				return sprintf(
					/* translators: 1: buy quantity, 2: free product name */
					__( 'Buy %1$d, Get %2$s FREE!', 'bogofy' ),
					$rule->buy_quantity,
					$free_product_name
				);

			case Rule::TYPE_BUY_CAT_GET_FREE:
				return sprintf(
					/* translators: 1: buy quantity, 2: free quantity */
					__( 'Buy %1$d from this category, Get %2$d FREE!', 'bogofy' ),
					$rule->buy_quantity,
					$rule->free_quantity
				);

			case Rule::TYPE_BUY_X_GET_X_DISCOUNTED:
				return sprintf(
					/* translators: 1: buy quantity, 2: free quantity, 3: discount percentage */
					__( 'Buy %1$d, Get %2$d at %3$d%% OFF!', 'bogofy' ),
					$rule->buy_quantity,
					$rule->free_quantity,
					$rule->discount_value
				);

			default:
				return '';
		}
	}
}
