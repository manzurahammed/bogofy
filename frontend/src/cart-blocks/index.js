/**
 * Bogofy block cart/checkout integration.
 *
 * Three informational, non-authoritative touches driven by the server-side BOGO
 * data exposed on the Store API (`extensions.bogofy`):
 *   1. Adds `.bogo-cart-item` to free gift rows (so the stylesheet can style
 *      them). The gift note lines and the price come from WooCommerce itself.
 *   2. Renders a "You saved" row in the Order Meta slot. The amount is computed
 *      from raw minor units + currency shape sent by PHP — no injected HTML.
 *   3. Renders "Add N more…" nudges (plain text from PHP) in the cart's Order
 *      Meta slot.
 *
 * Pricing/savings remain authoritative on the server: the gift item is priced
 * to zero (or discounted) by the PHP engine, so cart totals, taxes and order
 * records already reflect it. This row is purely a "You saved" summary.
 */
import {
  registerCheckoutFilters,
  ExperimentalOrderMeta,
} from "@woocommerce/blocks-checkout";
import { registerPlugin } from "@wordpress/plugins";
import { createElement } from "@wordpress/element";
import { __ } from "@wordpress/i18n";

/**
 * Read the bogofy extension data for a cart item from the filter arguments.
 *
 * @param {Object} extensions Item-level extension data.
 * @param {Object} args       Filter arguments (may hold the cart item).
 * @return {Object|undefined} The bogofy extension payload.
 */
const itemBogo = (extensions, args) =>
  extensions?.bogofy ?? args?.cartItem?.extensions?.bogofy;

/**
 * Format a raw savings payload as plain text (e.g. "$18.00").
 *
 * @param {Object} bogo Cart-level bogofy extension data.
 * @return {string} The formatted amount.
 */
const formatSavings = (bogo) => {
  const {
    savings_minor: minor = 0,
    currency_minor_unit: unit = 2,
    currency_prefix: prefix = "",
    currency_suffix: suffix = "",
    currency_decimal_separator: decimal = ".",
    currency_thousand_separator: thousand = ",",
  } = bogo;

  const factor = 10 ** unit;
  const abs = Math.abs(minor);
  const whole = String(Math.floor(abs / factor)).replace(
    /\B(?=(\d{3})+(?!\d))/g,
    thousand,
  );
  const fraction = unit > 0 ? decimal + String(abs % factor).padStart(unit, "0") : "";

  return `${minor < 0 ? "-" : ""}${prefix}${whole}${fraction}${suffix}`;
};

registerCheckoutFilters("bogofy", {
  cartItemClass: (defaultValue, extensions, args) => {
    const bogo = itemBogo(extensions, args);
    return bogo?.is_free && bogo?.show_gift !== false
      ? [defaultValue, "bogo-cart-item"].filter(Boolean).join(" ")
      : defaultValue;
  },
});

/**
 * Renders the "You saved" row in the cart/checkout Order Meta slot.
 *
 * @param {Object} props            Slot props.
 * @param {Object} props.extensions Cart-level extension data.
 * @return {WPElement|null} The savings row, or null when there are no savings.
 */
const SavingsRow = ({ extensions }) => {
  const bogo = extensions?.bogofy;
  if (
    !bogo ||
    bogo.show_gift === false ||
    !(Number(bogo.savings_minor) > 0)
  ) {
    return null;
  }

  return createElement(
    "div",
    { className: "wc-block-components-totals-item bogo-savings-row" },
    createElement(
      "span",
      { className: "wc-block-components-totals-item__label" },
      __("You saved", "bogofy"),
    ),
    createElement(
      "span",
      {
        className:
          "wc-block-components-totals-item__value bogo-savings-row__value",
      },
      formatSavings(bogo),
    ),
  );
};

/**
 * Renders the "Add N more…" nudges in the cart Order Meta slot.
 *
 * @param {Object} props            Slot props.
 * @param {Object} props.extensions Cart-level extension data.
 * @param {string} props.context    Block context (cart or checkout).
 * @return {WPElement|null} The nudge list, or null when there is nothing to nudge.
 */
const NudgeList = ({ extensions, context }) => {
  const nudges = extensions?.bogofy?.nudges;
  if (
    context === "woocommerce/checkout" ||
    !Array.isArray(nudges) ||
    !nudges.length
  ) {
    return null;
  }

  return createElement(
    "div",
    { className: "bogo-nudge-list" },
    nudges.map((message) =>
      createElement(
        "div",
        { key: message, className: "bogo-nudge", role: "status" },
        createElement("span", { className: "bogo-nudge__text" }, message),
      ),
    ),
  );
};

const BogofyCartMeta = () =>
  createElement(
    ExperimentalOrderMeta,
    null,
    createElement(NudgeList),
    createElement(SavingsRow),
  );

registerPlugin("bogofy-cart-savings", {
  render: BogofyCartMeta,
  scope: "woocommerce-checkout",
});
