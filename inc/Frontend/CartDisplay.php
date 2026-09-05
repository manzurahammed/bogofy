<?php
/**
 * Cart display modifications.
 *
 * @package Bogofy\Frontend
 */

namespace Bogofy\Frontend;

use Bogofy\Cart\CartHandler;
use Bogofy\Models\RuleRepository;

/**
 * Class CartDisplay
 *
 * Adds BOGO messaging and colour accents to the cart — the free-item gift note,
 * price display and a "Bogo savings" totals row. The default cart layout is left
 * untouched.
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
	 * Append the FREE badge and gift note under a free item's name.
	 *
	 * @param string $name      Product name HTML.
	 * @param array  $cart_item Cart item.
	 *
	 * @return string
	 */
	public function add_gift_details( $name, $cart_item ) {
		if ( ! CartHandler::is_bogo_item( $cart_item ) ) {
			return $name;
		}

		$badge = '<span class="bogo-free-badge">' . ProductPage::gift_icon_svg( 12 ) . esc_html__( 'FREE', 'bogofy' ) . '</span>';

		$rule_title = $this->get_rule_title( $cart_item );
		$gift_line  = '';
		if ( $rule_title ) {
			$gift_line = '<span class="bogo-gift-note">' . ProductPage::gift_icon_svg( 13 ) .
				'<span>' . sprintf(
					/* translators: %s: rule name */
					esc_html__( 'Your gift from %s', 'bogofy' ),
					'<strong>' . esc_html( $rule_title ) . '</strong>'
				) . '</span></span>';
		}

		$lock_line = '<span class="bogo-gift-note bogo-gift-note--lock">' . self::lock_icon_svg() .
			'<span>' . esc_html__( 'Auto-added gift · removed if the qualifying item is removed', 'bogofy' ) . '</span></span>';

		return $name . ' ' . $badge . $gift_line . $lock_line;
	}

	/**
	 * Add a marker class to the free item's cart row.
	 *
	 * @param string $classes   Existing row classes.
	 * @param array  $cart_item Cart item.
	 *
	 * @return string
	 */
	public function add_item_class( $classes, $cart_item ) {
		if ( CartHandler::is_bogo_item( $cart_item ) ) {
			$classes .= ' bogo-cart-item';
		}
		return $classes;
	}

	/**
	 * Modify price display for free / discounted items.
	 *
	 * @param string $price_html Price HTML.
	 * @param array  $cart_item  Cart item.
	 *
	 * @return string
	 */
	public function modify_free_item_price_display( $price_html, $cart_item ) {
		if ( ! CartHandler::is_bogo_item( $cart_item ) ) {
			return $price_html;
		}

		$product        = $cart_item['data'];
		$original_price = (float) $product->get_regular_price();
		$current_price  = (float) $product->get_price();

		// Free item: show the struck-through base price next to a green 0.00.
		if ( 0.0 === $current_price ) {
			return sprintf(
				'<del>%s</del> <ins class="bogo-free-price">%s</ins>',
				wp_kses_post( wc_price( $original_price ) ),
				wp_kses_post( wc_price( 0 ) )
			);
		}

		// Discounted item.
		if ( $current_price < $original_price ) {
			return sprintf(
				'<del>%s</del> <ins class="bogo-free-price">%s</ins>',
				wp_kses_post( wc_price( $original_price ) ),
				wp_kses_post( wc_price( $current_price ) )
			);
		}

		return $price_html;
	}

	/**
	 * Render a "Bogo savings" row in the cart totals.
	 *
	 * @return void
	 */
	public function render_savings_row() {
		$savings = $this->get_cart_savings();

		if ( $savings <= 0 ) {
			return;
		}

		printf(
			'<tr class="bogo-savings-row"><th>%s</th><td data-title="%s">- %s</td></tr>',
			esc_html__( 'Bogo savings', 'bogofy' ),
			esc_attr__( 'Bogo savings', 'bogofy' ),
			wp_kses_post( wc_price( $savings ) )
		);
	}

	/**
	 * Total base-price savings across all BOGO lines in the cart.
	 *
	 * @return float
	 */
	private function get_cart_savings() {
		$cart = WC()->cart;

		if ( ! $cart instanceof \WC_Cart ) {
			return 0.0;
		}

		$savings = 0.0;
		foreach ( $cart->get_cart() as $cart_item ) {
			if ( empty( $cart_item[ CartHandler::BOGO_RULE_KEY ] ) || empty( $cart_item['data'] ) ) {
				continue;
			}

			$product = $cart_item['data'];
			$regular = (float) $product->get_regular_price();
			$current = (float) $product->get_price();

			if ( $regular > $current ) {
				$savings += ( $regular - $current ) * (int) $cart_item['quantity'];
			}
		}

		return $savings;
	}

	/**
	 * Get the title of the rule that granted a cart item.
	 *
	 * @param array $cart_item Cart item.
	 *
	 * @return string
	 */
	private function get_rule_title( $cart_item ) {
		if ( empty( $cart_item[ CartHandler::BOGO_RULE_KEY ] ) ) {
			return '';
		}

		$rule = $this->repository->get( (int) $cart_item[ CartHandler::BOGO_RULE_KEY ] );

		return $rule ? $rule->title : '';
	}

	/**
	 * Inline lock icon SVG.
	 *
	 * @return string
	 */
	private static function lock_icon_svg() {
		return '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>';
	}
}
