<?php
/**
 * "Add N more" cart nudges.
 *
 * @package Bogofy\Frontend
 */

namespace Bogofy\Frontend;

use Bogofy\Admin\Settings;
use Bogofy\Cart\Contracts\EligibilityCheckerInterface;
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
	 * Constructor.
	 *
	 * @since 2.2.0
	 *
	 * @param RuleRepository              $repository          Rule repository.
	 * @param EligibilityCheckerInterface $eligibility_checker Eligibility checker.
	 */
	public function __construct( RuleRepository $repository, EligibilityCheckerInterface $eligibility_checker ) {
		$this->repository          = $repository;
		$this->eligibility_checker = $eligibility_checker;
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

		$messages = array();

		foreach ( $this->repository->get_active_rules() as $rule ) {
			$eligible_items = $this->eligibility_checker->get_eligible_items( $cart, $rule );
			$unlock         = $this->eligibility_checker->get_next_unlock( $eligible_items, $rule );

			if ( $unlock ) {
				$messages[] = $this->format_message( $rule, $unlock['remaining'], $unlock['free_qty'] );
			}
		}

		return $messages;
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
			$template = Rule::TYPE_BUY_X_GET_X_DISCOUNTED === $rule->rule_type
				/* translators: {remaining}: items still needed, {free_qty}: discounted items unlocked, {discount}: discount percentage. Keep the placeholders as-is. */
				? _n( 'Add {remaining} more item to get {free_qty} at {discount}% off!', 'Add {remaining} more items to get {free_qty} at {discount}% off!', $remaining, 'bogofy' )
				/* translators: {remaining}: items still needed, {free_qty}: free items unlocked. Keep the placeholders as-is. */
				: _n( 'Add {remaining} more item to get {free_qty} free!', 'Add {remaining} more items to get {free_qty} free!', $remaining, 'bogofy' );
		}

		$replacements = array(
			'{remaining}' => (int) $remaining,
			'{free_qty}'  => (int) $free_qty,
			'{discount}'  => (int) $rule->discount_value,
		);

		return str_replace( array_keys( $replacements ), array_values( $replacements ), $template );
	}
}
