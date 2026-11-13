import React from "react";
import PropTypes from "prop-types";
import { __ } from "@wordpress/i18n";
import { ProductSearch } from "../Shared/ProductSearch";
import { CategorySearch } from "../Shared/CategorySearch";

// Form state for a rule with no conditions; blank minimums mean "none".
export const emptyConditions = {
  min_subtotal: "",
  min_items: "",
  exclude_sale_items: false,
};

/**
 * Turn saved conditions from the API into form state.
 *
 * @param {Object} saved Conditions as returned by the REST API.
 * @returns {Object} Form state for CartConditions.
 */
export function conditionsToForm(saved = {}) {
  return {
    min_subtotal: Number(saved?.min_subtotal) > 0 ? saved.min_subtotal : "",
    min_items: Number(saved?.min_items) > 0 ? saved.min_items : "",
    exclude_sale_items: !!saved?.exclude_sale_items,
  };
}

/**
 * Validate the cart condition minimums.
 *
 * @param {Object} conditions Form state.
 * @returns {Object} Errors keyed by field; empty when valid.
 */
export function validateConditions(conditions = {}) {
  const errs = {};
  const { min_subtotal: minSubtotal = "", min_items: minItems = "" } =
    conditions;

  if (minSubtotal !== "" && !(Number(minSubtotal) >= 0)) {
    errs.min_subtotal = __("Minimum subtotal cannot be negative", "bogofy");
  }
  if (
    minItems !== "" &&
    !(Number.isInteger(Number(minItems)) && Number(minItems) >= 0)
  ) {
    errs.min_items = __(
      "Minimum items must be a whole number, 0 or more",
      "bogofy",
    );
  }

  return errs;
}

/**
 * Build the conditions payload the REST API expects.
 *
 * @param {Object} conditions         Form state.
 * @param {Array}  excludedProducts   Selected { id, name } products.
 * @param {Array}  excludedCategories Selected { id, name } categories.
 * @returns {Object} Conditions payload.
 */
export function conditionsToPayload(
  conditions = {},
  excludedProducts = [],
  excludedCategories = [],
) {
  return {
    min_subtotal: Number(conditions.min_subtotal) || 0,
    min_items: Number(conditions.min_items) || 0,
    excluded_product_ids: excludedProducts.map((p) => p.id),
    excluded_category_ids: excludedCategories.map((c) => c.id),
    exclude_sale_items: !!conditions.exclude_sale_items,
  };
}

/**
 * Cart condition fields shared by the create wizard and the edit form.
 */
export function CartConditions({
  conditions = emptyConditions,
  onChange,
  selectedExcludedProducts,
  setSelectedExcludedProducts,
  selectedExcludedCategories,
  setSelectedExcludedCategories,
  errors = {},
}) {
  const setCondition = (key, value) =>
    onChange({ ...conditions, [key]: value });

  return (
    <div className="bogo-cart-conditions">
      <div className="bogo-row" style={{ gap: 10, marginTop: 16 }}>
        <label className="bogo-form-label" style={{ margin: 0 }}>
          {__("Minimum subtotal", "bogofy")}
        </label>
        <input
          type="number"
          className="bogo-form-input"
          style={{ width: 90, textAlign: "center" }}
          min="0"
          step="0.01"
          placeholder="0"
          value={conditions.min_subtotal}
          onChange={(e) => setCondition("min_subtotal", e.target.value)}
        />
        <span style={{ color: "var(--muted)", fontSize: 12 }}>
          {__("before BOGO discounts · blank = none", "bogofy")}
        </span>
      </div>
      {errors.min_subtotal && (
        <div className="bogo-form-error">{errors.min_subtotal}</div>
      )}

      <div className="bogo-row" style={{ gap: 10, marginTop: 14 }}>
        <label className="bogo-form-label" style={{ margin: 0 }}>
          {__("Minimum items", "bogofy")}
        </label>
        <input
          type="number"
          className="bogo-form-input"
          style={{ width: 90, textAlign: "center" }}
          min="0"
          step="1"
          placeholder="0"
          value={conditions.min_items}
          onChange={(e) => setCondition("min_items", e.target.value)}
        />
        <span style={{ color: "var(--muted)", fontSize: 12 }}>
          {__("items in cart · blank = none", "bogofy")}
        </span>
      </div>
      {errors.min_items && (
        <div className="bogo-form-error">{errors.min_items}</div>
      )}

      <div style={{ marginTop: 16 }}>
        <label className="bogo-form-label">
          {__("Excluded products", "bogofy")}
        </label>
        <ProductSearch
          selectedProducts={selectedExcludedProducts}
          onChange={setSelectedExcludedProducts}
          placeholder={__("Search for products to exclude…", "bogofy")}
        />
      </div>

      <div style={{ marginTop: 14 }}>
        <label className="bogo-form-label">
          {__("Excluded categories", "bogofy")}
        </label>
        <CategorySearch
          selectedCategories={selectedExcludedCategories}
          onChange={setSelectedExcludedCategories}
          placeholder={__("Search for categories to exclude…", "bogofy")}
        />
      </div>

      <div className="bogo-row" style={{ gap: 10, marginTop: 16 }}>
        <button
          type="button"
          role="switch"
          aria-checked={!!conditions.exclude_sale_items}
          aria-label={__("Exclude sale items", "bogofy")}
          className={`bogo-toggle${conditions.exclude_sale_items ? " bogo-toggle--on" : ""}`}
          onClick={() =>
            setCondition("exclude_sale_items", !conditions.exclude_sale_items)
          }
        />
        <span className="bogo-form-label" style={{ margin: 0 }}>
          {__("Exclude sale items", "bogofy")}
        </span>
        <span style={{ color: "var(--muted)", fontSize: 12 }}>
          {__("products on sale don't count or get the offer", "bogofy")}
        </span>
      </div>
    </div>
  );
}

CartConditions.propTypes = {
  conditions: PropTypes.shape({
    min_subtotal: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
    min_items: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
    exclude_sale_items: PropTypes.bool,
  }),
  onChange: PropTypes.func.isRequired,
  selectedExcludedProducts: PropTypes.array.isRequired,
  setSelectedExcludedProducts: PropTypes.func.isRequired,
  selectedExcludedCategories: PropTypes.array.isRequired,
  setSelectedExcludedCategories: PropTypes.func.isRequired,
  errors: PropTypes.object,
};

export default CartConditions;
