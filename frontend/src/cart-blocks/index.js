/**
 * Bogofy block cart/checkout integration.
 *
 * Accents the free gift row (so the stylesheet can style it) and renders a
 * "Bogo savings" row in the cart/checkout totals, driven by the BOGO data
 * exposed on the Store API (`extensions.bogofy`). The gift note lines and the
 * price come from WooCommerce itself (item_data + native price).
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
 * @return {Object|null} The bogofy extension payload, or null.
 */
const itemBogo = (extensions, args) => {
  if (extensions?.bogofy) {
    return extensions.bogofy;
  }
  return args?.cartItem?.extensions?.bogofy ?? null;
};

if (typeof registerCheckoutFilters === "function") {
  registerCheckoutFilters("bogofy", {
    cartItemClass: (defaultValue, extensions, args) => {
      const bogo = itemBogo(extensions, args);
      if (bogo?.is_free) {
        return `${defaultValue ? `${defaultValue} ` : ""}bogo-cart-item`;
      }
      return defaultValue;
    },
  });
}

/**
 * Renders the "Bogo savings" line in the totals slot.
 *
 * @param {Object} props            Slot props.
 * @param {Object} props.extensions Cart-level extension data.
 * @return {WPElement|null} The savings row, or null when there are no savings.
 */
const SavingsRow = ({ extensions }) => {
  const bogo = extensions?.bogofy;
  if (!bogo || !(Number(bogo.savings) > 0) || !bogo.savings_html) {
    return null;
  }

  return createElement(
    "div",
    { className: "wc-block-components-totals-item bogo-savings-row" },
    createElement(
      "span",
      { className: "wc-block-components-totals-item__label" },
      __("Bogo savings", "bogofy"),
    ),
    createElement("span", {
      className:
        "wc-block-components-totals-item__value bogo-savings-row__value",
      dangerouslySetInnerHTML: { __html: `- ${bogo.savings_html}` },
    }),
  );
};

const BogofyCartMeta = () =>
  createElement(ExperimentalOrderMeta, null, createElement(SavingsRow));

if (typeof registerPlugin === "function") {
  registerPlugin("bogofy-cart-savings", {
    render: BogofyCartMeta,
    scope: "woocommerce-checkout",
  });
}
