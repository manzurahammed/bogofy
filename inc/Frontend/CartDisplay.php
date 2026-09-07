<?php
/**
 * Cart display modifications.
 *
 * @package Bogofy\Frontend
 */

namespace Bogofy\Frontend;

use Bogofy\Admin\Settings;
use Bogofy\Cart\CartHandler;
use Bogofy\Models\Rule;
use Bogofy\Models\RuleRepository;

/**
 * Class CartDisplay
 *
 * Adds the BOGO gift note (which rule granted the item and what it is linked to)
 * under free cart items. Because it hooks `woocommerce_get_item_data`, the notes
 * surface in both the classic cart and the block cart (via the Store API).
 */
class CartDisplay {

	/**
	 * Rule repository.
	 *
	 * @var RuleRepository
	 */
	private $repository;

	/**
	 * Constructor.
	 *
	 * @param RuleRepository $repository Rule repository.
	 */
	public function __construct( RuleRepository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Append the gift note lines to a free item's cart data.
	 *
	 * @param array $item_data Existing cart item data.
	 * @param array $cart_item Cart item.
	 *
	 * @return array
	 */
	public function add_item_data( $item_data, $cart_item ) {
		if ( ! Settings::is_enabled() || ! Settings::get( 'show_cart_gift' ) ) {
			return $item_data;
		}

		if ( ! CartHandler::is_bogo_item( $cart_item ) ) {
			return $item_data;
		}

		$rule  = $this->get_rule( $cart_item );
		$notes = array();

		if ( $rule ) {
			$notes[] = sprintf(
				/* translators: %s: rule name */
				esc_html__( '🎁 Your gift from %s', 'bogofy' ),
				'<strong>' . esc_html( $rule->title ) . '</strong>'
			);
		}

		$trigger = $rule ? $this->get_trigger_label( $rule ) : '';

		$notes[] = $trigger
			? sprintf(
				/* translators: %s: trigger product/category name */
				esc_html__( '🔒 Quantity linked to your %s · removed if that item goes', 'bogofy' ),
				'<strong>' . esc_html( $trigger ) . '</strong>'
			)
			: esc_html__( '🔒 Auto-added gift · removed if the qualifying item is removed', 'bogofy' );

		// Combine into a single entry so the block cart stacks them on their own
		// lines instead of joining separate entries with " / ".
		$item_data[] = array(
			'key'     => '',
			'value'   => implode( '<br>', $notes ),
			'display' => '',
		);

		return $item_data;
	}

	/**
	 * Get the rule that granted a cart item.
	 *
	 * @param array $cart_item Cart item.
	 *
	 * @return Rule|null
	 */
	private function get_rule( $cart_item ) {
		if ( empty( $cart_item[ CartHandler::BOGO_RULE_KEY ] ) ) {
			return null;
		}

		return $this->repository->get( (int) $cart_item[ CartHandler::BOGO_RULE_KEY ] );
	}

	/**
	 * Human label for what a rule is triggered by (product or category name).
	 *
	 * @param Rule $rule Rule object.
	 *
	 * @return string
	 */
	private function get_trigger_label( Rule $rule ) {
		if ( Rule::APPLY_SPECIFIC_CATEGORIES === $rule->apply_to && ! empty( $rule->category_ids ) ) {
			$term = get_term( (int) $rule->category_ids[0], 'product_cat' );
			if ( $term && ! is_wp_error( $term ) ) {
				return $term->name;
			}
		}

		if ( ! empty( $rule->buy_product_ids ) ) {
			$product = wc_get_product( (int) $rule->buy_product_ids[0] );
			if ( $product ) {
				return $product->get_name();
			}
		}

		return '';
	}
}
