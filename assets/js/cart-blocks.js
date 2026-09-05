/**
 * Bogofy block cart/checkout integration.
 *
 * Uses the WooCommerce Blocks Checkout filters + a totals slot-fill to render
 * the free-item price, gift-row accent and a "Bogo savings" total, driven by the
 * BOGO data exposed on the Store API (extensions.bogofy).
 */
(function (wc, wp) {
  if (!wc || !wc.blocksCheckout || !wp || !wp.element) {
    return;
  }

  var registerCheckoutFilters = wc.blocksCheckout.registerCheckoutFilters;
  var ExperimentalOrderMeta = wc.blocksCheckout.ExperimentalOrderMeta;
  var el = wp.element.createElement;
  var __ = (wp.i18n && wp.i18n.__) || function (s) { return s; };

  /**
   * Read the bogofy extension data for a cart item from the filter arguments.
   */
  function itemBogo(extensions, args) {
    if (extensions && extensions.bogofy) {
      return extensions.bogofy;
    }
    if (args && args.cartItem && args.cartItem.extensions && args.cartItem.extensions.bogofy) {
      return args.cartItem.extensions.bogofy;
    }
    return null;
  }

  // Accent the free gift row. (WooCommerce renders the struck base price and the
  // native "Save X" badge itself, so we don't override the price format here —
  // those filters only accept a plain <price/> token, not custom markup.)
  registerCheckoutFilters("bogofy", {
    cartItemClass: function (defaultValue, extensions, args) {
      var bogo = itemBogo(extensions, args);
      if (bogo && bogo.is_free) {
        return (defaultValue ? defaultValue + " " : "") + "bogo-cart-item";
      }
      return defaultValue;
    },
  });

  // "Bogo savings" row in the cart/checkout totals.
  function SavingsRow(props) {
    var ext = props && props.extensions ? props.extensions.bogofy : null;
    if (!ext || !(Number(ext.savings) > 0) || !ext.savings_html) {
      return null;
    }
    return el(
      "div",
      { className: "wc-block-components-totals-item bogo-savings-row" },
      el(
        "span",
        { className: "wc-block-components-totals-item__label" },
        __("Bogo savings", "bogofy")
      ),
      el("span", {
        className: "wc-block-components-totals-item__value bogo-savings-row__value",
        dangerouslySetInnerHTML: { __html: "- " + ext.savings_html },
      })
    );
  }

  function Render() {
    return el(ExperimentalOrderMeta, null, el(SavingsRow, null));
  }

  if (wp.plugins && wp.plugins.registerPlugin) {
    wp.plugins.registerPlugin("bogofy-cart-savings", {
      render: Render,
      scope: "woocommerce-checkout",
    });
  }
})(window.wc, window.wp);
