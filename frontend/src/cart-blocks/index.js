/**
 * Bogofy block cart/checkout integration.
 *
 * Two informational, non-authoritative touches driven by the server-side BOGO
 * data exposed on the Store API (`extensions.bogofy`):
 *   1. Adds `.bogo-cart-item` to free gift rows (so the stylesheet can style
 *      them). The gift note lines and the price come from WooCommerce itself.
 *   2. Renders a "You saved" row in the Order Meta slot. The amount is computed
 *      from raw minor units + currency shape sent by PHP — no injected HTML.
 *
 *   3. Renders a gift picker in the same slot for rules that offer several
 *      gift products; a choice is sent through `extensionCartUpdate` and the
 *      server swaps the gift line.
 *
 * Pricing/savings remain authoritative on the server: the gift item is priced
 * to zero (or discounted) by the PHP engine, so cart totals, taxes and order
 * records already reflect it. This row is purely a "You saved" summary.
 */
import {
  registerCheckoutFilters,
  ExperimentalOrderMeta,
  extensionCartUpdate,
} from "@woocommerce/blocks-checkout";
import { registerPlugin } from "@wordpress/plugins";
import { createElement, Fragment, useState } from "@wordpress/element";
import { __, sprintf } from "@wordpress/i18n";

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
    return bogo?.is_free
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
  if (!bogo || !(Number(bogo.savings_minor) > 0)) {
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
 * Gift picker for one rule: the chosen gift plus a button per alternative.
 *
 * @param {Object} props        Component props.
 * @param {Object} props.choice One `gift_choices` entry from the server.
 * @return {WPElement} The picker.
 */
const GiftPicker = ({ choice }) => {
  const [busy, setBusy] = useState(false);
  const [failed, setFailed] = useState(false);

  const select = (productId) => {
    setBusy(true);
    setFailed(false);
    extensionCartUpdate({
      namespace: "bogofy",
      data: { rule_id: choice.rule_id, product_id: productId },
    })
      .catch(() => setFailed(true))
      .finally(() => setBusy(false));
  };

  return createElement(
    "div",
    { className: "bogo-gift-picker" },
    createElement(
      "span",
      { className: "bogo-gift-picker__label" },
      /* translators: %s: rule name */
      sprintf(__("Choose your gift from %s:", "bogofy"), choice.rule_title),
    ),
    createElement(
      "ul",
      { className: "bogo-gift-picker__options" },
      choice.options.map((option) =>
        option.id === choice.selected
          ? createElement(
              "li",
              {
                key: option.id,
                className:
                  "bogo-gift-picker__option bogo-gift-picker__option--selected",
                "aria-current": "true",
              },
              option.name,
            )
          : createElement(
              "li",
              { key: option.id, className: "bogo-gift-picker__option" },
              createElement(
                "button",
                {
                  type: "button",
                  className: "bogo-gift-picker__button",
                  disabled: busy,
                  onClick: () => select(option.id),
                },
                option.name,
              ),
            ),
      ),
    ),
    failed &&
      createElement(
        "p",
        { className: "bogo-gift-picker__error", role: "alert" },
        __("That gift could not be selected.", "bogofy"),
      ),
  );
};

/**
 * Renders a gift picker per rule that offers a choice of gifts (cart only).
 *
 * @param {Object} props            Slot props.
 * @param {Object} props.extensions Cart-level extension data.
 * @param {string} props.context    Block context.
 * @return {WPElement|null} The pickers, or null when there is nothing to choose.
 */
const GiftPickers = ({ extensions, context }) => {
  const choices = extensions?.bogofy?.gift_choices;
  if (context === "woocommerce/checkout" || !choices?.length) {
    return null;
  }

  return createElement(
    Fragment,
    null,
    choices.map((choice) =>
      createElement(GiftPicker, { key: choice.rule_id, choice }),
    ),
  );
};

const BogofyCartMeta = () =>
  createElement(
    ExperimentalOrderMeta,
    null,
    createElement(GiftPickers),
    createElement(SavingsRow),
  );

registerPlugin("bogofy-cart-savings", {
  render: BogofyCartMeta,
  scope: "woocommerce-checkout",
});
