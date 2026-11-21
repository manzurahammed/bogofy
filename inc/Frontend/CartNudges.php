<?php
/**
 * "Add N more" cart nudges.
 *
 * @package Bogofy\Frontend
 */

namespace Bogofy\Frontend;

use Bogofy\Admin\Settings;
use Bogofy\Cart\CartHandler;
use Bogofy\Cart\Contracts\EligibilityCheckerInterface;
use Bogofy\Cart\Contracts\FreeItemManagerInterface;
use Bogofy\Models\Rule;
use Bogofy\Models\RuleRepository;

/**
 * Class CartNudges
 *
 * Tells shoppers how many more qualifying items unlock the next free or
 * discounted item. Messages are plain text: the classic cart escapes them on
 * output and the block cart receives them through the Store API.
 *
 * @since 2.2.0
 */
class CartNudges {

	/**
	 * Rule repository.
	 *
	 * @var RuleRepository
	 */
	private $repository;

	/**
	 * Eligibility checker.
	 *
	 * @var EligibilityCheckerInterface
	 */
	private $eligibility_checker;

	/**
	 * Free item manager.
	 *
	 * @var FreeItemManagerInterface
	 */
	private $free_item_manager;

	/**
	 * Constructor.
	 *
	 * @since 2.2.0
	 *
	 * @param RuleRepository              $repository          Rule repository.
	 * @param EligibilityCheckerInterface $eligibility_checker Eligibility checker.
	 * @param FreeItemManagerInterface    $free_item_manager   Free item manager.
	 */
	public function __construct( RuleRepository $repository, EligibilityCheckerInterface $eligibility_checker, FreeItemManagerInterface $free_item_manager ) {
		$this->repository          = $repository;
		$this->eligibility_checker = $eligibility_checker;
		$this->free_item_manager   = $free_item_manager;
	}

	/**
	 * Nudge messages for every active rule the cart has partly met.
	 *
	 * @since 2.2.0
	 *
	 * @param \WC_Cart|null $cart Cart object. Defaults to the current cart.
	 *
	 * @return string[] Plain-text messages.
	 */
	public function get_messages( $cart = null ) {
		if ( ! Settings::is_enabled() || ! Settings::get( 'show_cart_nudges' ) ) {
			return array();
		}

		if ( null === $cart && function_exists( 'WC' ) ) {
			$cart = WC()->cart;
		}

		if ( ! $cart instanceof \WC_Cart ) {
			return array();
		}

		$rules = $this->repository->get_active_rules();

		// Same order the pricing code uses, so overlap is judged the same way.
		usort(
			$rules,
			static function ( $a, $b ) {
				return (int) $a->priority <=> (int) $b->priority;
			}
		);

		$messages = array();
		$claimed  = array();

		foreach ( $rules as $rule ) {
			$eligible_items = $this->eligibility_checker->get_eligible_items( $cart, $rule );

			// A rule pricing vetoes must not be advertised. Documented in
			// CartHandler::is_rule_eligible().
			if ( ! apply_filters( 'bogofy_rule_is_eligible', ! empty( $eligible_items ), $rule, $cart ) ) {
				continue;
			}

			// A discounted line goes to the highest-priority rule only, so a lower
			// rule sharing any of its lines would never deliver what it advertises.
			if ( Rule::TYPE_BUY_X_GET_X_DISCOUNTED === $rule->rule_type && ! empty( $eligible_items ) ) {
				$keys = array_keys( $eligible_items );

				if ( array_intersect( $keys, $claimed ) ) {
					continue;
				}

				$claimed = array_merge( $claimed, $keys );
			}

			$unlock   = $this->eligibility_checker->get_next_unlock( $eligible_items, $rule );
			$free_qty = $unlock ? $this->deliverable_reward( $cart, $rule, $eligible_items, $unlock ) : 0;

			if ( $free_qty > 0 ) {
				$messages[] = $this->format_message( $rule, $unlock['remaining'], $free_qty );
			}
		}

		return $messages;
	}

	/**
	 * How many of the next set's reward units the store could actually grant.
	 *
	 * Mirrors the gift code, so a nudge never promises more than the cart will
	 * receive. Gift rules use the gift sync would pick: the shopper's current
	 * gift first, then the rule's other gifts in order, skipping excluded ones.
	 * Buy X Get X rewards a copy of the cheapest bought item, whose stock must
	 * also cover the paid units the shopper is asked to add. Discounted rules
	 * reward the bought items themselves and need no extra stock.
	 *
	 * @param \WC_Cart $cart           Cart object.
	 * @param Rule     $rule           Rule object.
	 * @param array    $eligible_items Eligible cart items.
	 * @param array    $unlock         Result of get_next_unlock().
	 *
	 * @return int Reward units to advertise; 0 for no nudge.
	 */
	private function deliverable_reward( $cart, Rule $rule, $eligible_items, $unlock ) {
		$free_qty = (int) $unlock['free_qty'];

		if ( Rule::TYPE_BUY_X_GET_X_DISCOUNTED === $rule->rule_type ) {
			return $free_qty;
		}

		$held     = $this->held_gift_lines( $cart, $rule );
		$held_qty = (int) array_sum( $held );
		$keys     = array_keys( $held );

		if ( Rule::TYPE_BUY_X_GET_X === $rule->rule_type ) {
			$line = $this->cheapest_line( $eligible_items );

			if ( ! $line ) {
				return 0;
			}

			$product_id = (int) ( $line['variation_id'] ? $line['variation_id'] : $line['product_id'] );
			$paid       = (int) $unlock['remaining'];
			$units      = $this->free_item_manager->supplyable( $cart, $product_id, $paid + $held_qty + $free_qty, $line['variation'] ?? array(), $keys );

			return max( 0, min( $free_qty, $units - $paid - $held_qty ) );
		}

		foreach ( $this->gift_order( $cart, $rule, $held ) as $gift_id ) {
			if ( $this->is_gift_excluded( $gift_id, $rule ) ) {
				continue;
			}

			$units = $this->free_item_manager->supplyable( $cart, $gift_id, $held_qty + $free_qty, array(), $keys );

			// Sync settles on the first gift it can add at all, so stop here.
			if ( $units > 0 ) {
				return max( 0, min( $free_qty, $units - $held_qty ) );
			}
		}

		return 0;
	}

	/**
	 * The rule's gift lines already in the cart.
	 *
	 * @param \WC_Cart $cart Cart object.
	 * @param Rule     $rule Rule object.
	 *
	 * @return array<string,int> Quantity keyed by cart item key.
	 */
	private function held_gift_lines( $cart, Rule $rule ) {
		$held = array();

		foreach ( $cart->get_cart() as $key => $cart_item ) {
			if ( CartHandler::is_bogo_item( $cart_item ) && (int) ( $cart_item[ CartHandler::BOGO_RULE_KEY ] ?? 0 ) === (int) $rule->id ) {
				$held[ $key ] = (int) $cart_item['quantity'];
			}
		}

		return $held;
	}

	/**
	 * Gift products in the order sync tries them: the current gift, then the rest.
	 *
	 * @param \WC_Cart $cart Cart object.
	 * @param Rule     $rule Rule object.
	 * @param array    $held The rule's gift lines in the cart.
	 *
	 * @return int[]
	 */
	private function gift_order( $cart, Rule $rule, $held ) {
		$gift_ids = $rule->gift_product_ids();
		$contents = $cart->get_cart();

		foreach ( array_keys( $held ) as $key ) {
			$current = CartHandler::gift_product_id( $contents[ $key ] );

			if ( in_array( $current, $gift_ids, true ) ) {
				return array_values( array_unique( array_merge( array( $current ), $gift_ids ) ) );
			}
		}

		return $gift_ids;
	}

	/**
	 * The cheapest eligible line, whose product Buy X Get X gives free first.
	 *
	 * @param array $eligible_items Eligible cart items.
	 *
	 * @return array|null
	 */
	private function cheapest_line( $eligible_items ) {
		$cheapest = null;

		foreach ( $eligible_items as $cart_item ) {
			$price = isset( $cart_item['data'] ) ? (float) $cart_item['data']->get_price() : 0.0;

			if ( null === $cheapest || $price < $cheapest[0] ) {
				$cheapest = array( $price, $cart_item );
			}
		}

		return $cheapest ? $cheapest[1] : null;
	}

	/**
	 * Whether the rule's exclusions rule out a gift product.
	 *
	 * @param int  $product_id Gift product or variation ID.
	 * @param Rule $rule       Rule object.
	 *
	 * @return bool
	 */
	private function is_gift_excluded( $product_id, Rule $rule ) {
		if ( ! $rule->has_exclusions() ) {
			return false;
		}

		$product   = wc_get_product( $product_id );
		$product   = $product ? $product : null;
		$parent_id = $product && $product->get_parent_id() ? (int) $product->get_parent_id() : $product_id;

		return $this->eligibility_checker->is_product_excluded( $product_id, $parent_id, $rule, $product );
	}

	/**
	 * Print the nudges above the classic cart table.
	 *
	 * Hooked to `woocommerce_before_cart_table`.
	 *
	 * @since 2.2.0
	 *
	 * @return void
	 */
	public function render() {
		foreach ( $this->get_messages() as $message ) {
			printf(
				'<div class="bogo-nudge" role="status"><span class="bogo-nudge__icon">%s</span><span class="bogo-nudge__text">%s</span></div>',
				ProductPage::gift_icon_svg( 18 ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static inline SVG.
				esc_html( $message )
			);
		}
	}

	/**
	 * Fill the nudge template for a rule.
	 *
	 * Uses the merchant's custom text when set, otherwise a translatable default.
	 *
	 * @param Rule $rule      Rule object.
	 * @param int  $remaining Qualifying items still needed.
	 * @param int  $free_qty  Items the next set unlocks.
	 *
	 * @return string
	 */
	private function format_message( Rule $rule, $remaining, $free_qty ) {
		$template = (string) Settings::get( 'cart_nudge_text' );

		if ( '' === $template ) {
			$is_discount = Rule::TYPE_BUY_X_GET_X_DISCOUNTED === $rule->rule_type || Rule::DISCOUNT_FREE !== $rule->discount_type;

			$template = $is_discount
				/* translators: {remaining}: items still needed, {free_qty}: discounted items unlocked, {discount}: discount percentage. Keep the placeholders as-is. */
				? _n( 'Add {remaining} more item to get {free_qty} at {discount}% off!', 'Add {remaining} more items to get {free_qty} at {discount}% off!', $remaining, 'bogofy' )
				/* translators: {remaining}: items still needed, {free_qty}: free items unlocked. Keep the placeholders as-is. */
				: _n( 'Add {remaining} more item to get {free_qty} free!', 'Add {remaining} more items to get {free_qty} free!', $remaining, 'bogofy' );
		}

		$replacements = array(
			'{remaining}' => (int) $remaining,
			'{free_qty}'  => (int) $free_qty,
			'{discount}'  => (float) $rule->discount_value,
		);

		return str_replace( array_keys( $replacements ), array_values( $replacements ), $template );
	}
}
