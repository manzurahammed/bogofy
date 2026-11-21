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
	 * Cart handler.
	 *
	 * @var CartHandler|null
	 */
	private $cart_handler;

	/**
	 * Nonce action for switching the gift from the classic cart.
	 *
	 * @var string
	 */
	const SWITCH_GIFT_NONCE = 'bogo-switch-gift';

	/**
	 * Constructor.
	 *
	 * @param RuleRepository   $repository   Rule repository.
	 * @param CartHandler|null $cart_handler Cart handler, for the gift picker.
	 */
	public function __construct( RuleRepository $repository, ?CartHandler $cart_handler = null ) {
		$this->repository   = $repository;
		$this->cart_handler = $cart_handler;
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

		$item_data[] = array(
			'key'     => '',
			'value'   => implode( '<br>', $notes ),
			'display' => '',
		);

		return $item_data;
	}

	/**
	 * Render the gift picker under a classic-cart gift line.
	 *
	 * Each alternative is a nonce-protected link, like WooCommerce's own remove
	 * links, because the line already sits inside the cart form.
	 *
	 * @since 2.2.0
	 *
	 * @param array  $cart_item     Cart item.
	 * @param string $cart_item_key Cart item key.
	 *
	 * @return void
	 */
	public function render_gift_picker( $cart_item, $cart_item_key ) {
		if ( ! $this->cart_handler || ! CartHandler::is_bogo_item( $cart_item ) || ! Settings::get( 'show_cart_gift' ) ) {
			return;
		}

		$choice = null;
		foreach ( $this->cart_handler->get_gift_choices( WC()->cart ) as $candidate ) {
			if ( $candidate['cart_item_key'] === $cart_item_key ) {
				$choice = $candidate;
				break;
			}
		}

		if ( ! $choice ) {
			return;
		}
		?>
		<div class="bogo-gift-picker">
			<span class="bogo-gift-picker__label"><?php esc_html_e( 'Choose your gift:', 'bogofy' ); ?></span>
			<ul class="bogo-gift-picker__options">
				<?php foreach ( $choice['options'] as $option ) : ?>
					<?php if ( (int) $option['id'] === (int) $choice['selected'] ) : ?>
						<li class="bogo-gift-picker__option bogo-gift-picker__option--selected" aria-current="true"><?php echo esc_html( $option['name'] ); ?></li>
					<?php else : ?>
						<li class="bogo-gift-picker__option">
							<a class="bogo-gift-picker__link" href="<?php echo esc_url( $this->switch_gift_url( $choice['rule_id'], $option['id'] ) ); ?>"><?php echo esc_html( $option['name'] ); ?></a>
						</li>
					<?php endif; ?>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}

	/**
	 * Handle a gift switch requested from the classic cart.
	 *
	 * Hooked to `wp_loaded`, after the cart is loaded from the session.
	 *
	 * @since 2.2.0
	 *
	 * @return void
	 */
	public function handle_gift_switch() {
		if ( ! $this->cart_handler || ! isset( $_GET['bogo_gift_rule'], $_GET['bogo_gift_product'] ) ) {
			return;
		}

		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, self::SWITCH_GIFT_NONCE ) ) {
			wc_add_notice( __( 'Your session has expired. Please choose your gift again.', 'bogofy' ), 'error' );
		} elseif ( $this->cart_handler->switch_gift( absint( $_GET['bogo_gift_rule'] ), absint( $_GET['bogo_gift_product'] ) ) ) {
			WC()->cart->calculate_totals();
			wc_add_notice( __( 'Your free gift has been updated.', 'bogofy' ) );
		} else {
			wc_add_notice( __( 'That gift could not be selected.', 'bogofy' ), 'error' );
		}

		wp_safe_redirect( wc_get_cart_url() );
		exit;
	}

	/**
	 * Nonce-protected URL that switches a rule's gift.
	 *
	 * @param int $rule_id    Rule ID.
	 * @param int $product_id Gift product ID.
	 *
	 * @return string
	 */
	private function switch_gift_url( $rule_id, $product_id ) {
		return wp_nonce_url(
			add_query_arg(
				array(
					'bogo_gift_rule'    => (int) $rule_id,
					'bogo_gift_product' => (int) $product_id,
				),
				wc_get_cart_url()
			),
			self::SWITCH_GIFT_NONCE
		);
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

		$bought = $this->get_bought_product_ids();

		foreach ( $rule->buy_product_ids as $product_id ) {
			$product = in_array( (int) $product_id, $bought, true ) ? wc_get_product( (int) $product_id ) : false;
			if ( $product ) {
				return $product->get_name();
			}
		}

		return '';
	}

	/**
	 * IDs of the products and variations on the paid cart lines.
	 *
	 * @return int[]
	 */
	private function get_bought_product_ids() {
		$ids  = array();
		$cart = WC()->cart;

		foreach ( $cart ? $cart->get_cart() : array() as $cart_item ) {
			if ( ! CartHandler::is_bogo_item( $cart_item ) ) {
				$ids[] = (int) $cart_item['product_id'];
				$ids[] = (int) $cart_item['variation_id'];
			}
		}

		return $ids;
	}
}
