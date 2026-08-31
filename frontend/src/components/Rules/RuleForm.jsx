import React, { useState, useEffect } from "react";
import PropTypes from "prop-types";
import clsx from "clsx";
import { __ } from "@wordpress/i18n";
import { ProductSearch } from "../Shared/ProductSearch";
import { CategorySearch } from "../Shared/CategorySearch";
import { get } from "../../services/api";

/**
 * Fetch real names for saved product/category IDs so the edit
 * form shows titles instead of "Product #12" placeholders.
 */
async function hydrateItems(endpoint, ids, fallbackLabel) {
  if (!ids?.length) return [];
  try {
    const items = await get(endpoint, { include: ids.join(",") });
    return ids.map(
      (id) =>
        items.find((item) => item.id === id) || {
          id,
          name: `${fallbackLabel} #${id}`,
        },
    );
  } catch {
    return ids.map((id) => ({ id, name: `${fallbackLabel} #${id}` }));
  }
}

const ruleTypes = [
  {
    value: "buy_x_get_x",
    label: __("Buy X Get X Free", "bogofy"),
    description: __(
      "Buy N of same product, get M of same product free",
      "bogofy",
    ),
  },
  {
    value: "buy_x_get_y",
    label: __("Buy X Get Y Free", "bogofy"),
    description: __("Buy product A, get product B free", "bogofy"),
  },
  {
    value: "buy_cat_get_free",
    label: __("Buy from Category, Get Free", "bogofy"),
    description: __("Buy from category A, get a specific product free", "bogofy"),
  },
  {
    value: "buy_x_get_x_discounted",
    label: __("Buy X Get X Discounted", "bogofy"),
    description: __("Buy N, get M at a percentage discount", "bogofy"),
  },
];

const applyToOptions = [
  { value: "specific_products", label: __("Specific Products", "bogofy") },
  { value: "specific_categories", label: __("Specific Categories", "bogofy") },
  { value: "all_products", label: __("All Products", "bogofy") },
];

const defaultFormData = {
  title: "",
  rule_type: "buy_x_get_x",
  status: "active",
  buy_quantity: 1,
  free_quantity: 1,
  discount_type: "free",
  discount_value: 100,
  apply_to: "specific_products",
  buy_product_ids: [],
  free_product_ids: [],
  category_ids: [],
  max_free_qty: "",
  message_template: "",
  priority: 10,
  start_date: "",
  end_date: "",
};

export function RuleForm({
  initialData = null,
  onSubmit,
  isLoading = false,
  formId,
}) {
  const [formData, setFormData] = useState(defaultFormData);
  const [selectedBuyProducts, setSelectedBuyProducts] = useState([]);
  const [selectedFreeProducts, setSelectedFreeProducts] = useState([]);
  const [selectedCategories, setSelectedCategories] = useState([]);
  const [errors, setErrors] = useState({});

  useEffect(() => {
    if (initialData) {
      setFormData({
        ...defaultFormData,
        ...initialData,
        max_free_qty: initialData.max_free_qty || "",
        start_date: initialData.start_date
          ? initialData.start_date.slice(0, 16)
          : "",
        end_date: initialData.end_date ? initialData.end_date.slice(0, 16) : "",
      });

      // Resolve saved IDs to real product/category names.
      hydrateItems(
        "/products/search",
        initialData.buy_product_ids,
        "Product",
      ).then(setSelectedBuyProducts);
      hydrateItems(
        "/products/search",
        initialData.free_product_ids,
        "Product",
      ).then(setSelectedFreeProducts);
      hydrateItems(
        "/categories/search",
        initialData.category_ids,
        "Category",
      ).then(setSelectedCategories);
    }
  }, [initialData]);

  const handleChange = (e) => {
    const { name, value, type } = e.target;
    setFormData((prev) => ({
      ...prev,
      [name]: type === "number" ? Number(value) : value,
    }));
    if (errors[name]) {
      setErrors((prev) => ({ ...prev, [name]: null }));
    }
  };

  const validate = () => {
    const newErrors = {};
    if (!formData.title.trim()) {
      newErrors.title = __("Title is required", "bogofy");
    }
    if (formData.buy_quantity < 1) {
      newErrors.buy_quantity = __("Must be at least 1", "bogofy");
    }
    if (formData.free_quantity < 1) {
      newErrors.free_quantity = __("Must be at least 1", "bogofy");
    }
    if (
      formData.rule_type === "buy_x_get_x_discounted" &&
      (formData.discount_value <= 0 || formData.discount_value > 100)
    ) {
      newErrors.discount_value = __("Must be between 1 and 100", "bogofy");
    }
    if (
      formData.apply_to === "specific_products" &&
      selectedBuyProducts.length === 0
    ) {
      newErrors.buy_products = __("Select at least one product", "bogofy");
    }
    if (
      formData.apply_to === "specific_categories" &&
      selectedCategories.length === 0
    ) {
      newErrors.categories = __("Select at least one category", "bogofy");
    }
    if (
      (formData.rule_type === "buy_x_get_y" ||
        formData.rule_type === "buy_cat_get_free") &&
      selectedFreeProducts.length === 0
    ) {
      newErrors.free_products = __("Select the free product", "bogofy");
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    if (!validate()) return;

    const data = {
      ...formData,
      buy_product_ids: selectedBuyProducts.map((p) => p.id),
      free_product_ids: selectedFreeProducts.map((p) => p.id),
      category_ids: selectedCategories.map((c) => c.id),
      max_free_qty: formData.max_free_qty
        ? Number(formData.max_free_qty)
        : null,
      start_date: formData.start_date || null,
      end_date: formData.end_date || null,
    };

    onSubmit(data);
  };

  const showFreeProductSelector =
    formData.rule_type === "buy_x_get_y" ||
    formData.rule_type === "buy_cat_get_free";
  const showDiscountValue = formData.rule_type === "buy_x_get_x_discounted";

  return (
    <form id={formId} onSubmit={handleSubmit} className="bogo-space-y-6">
      {/* Basic Info */}
      <div className="bogo-card bogo-p-6">
        <h3 className="bogo-text-lg bogo-font-semibold bogo-text-gray-900 bogo-mb-4">
          {__("Basic Information", "bogofy")}
        </h3>
        <div className="bogo-grid bogo-grid-cols-1 md:bogo-grid-cols-2 bogo-gap-4">
          <div className="md:bogo-col-span-2">
            <label className="bogo-label">{__("Rule Title *", "bogofy")}</label>
            <input
              type="text"
              name="title"
              value={formData.title}
              onChange={handleChange}
              className={clsx(
                "bogo-input",
                errors.title && "bogo-border-danger-500",
              )}
              placeholder={__("e.g., Buy 2 Get 1 Free on T-Shirts", "bogofy")}
            />
            {errors.title && (
              <p className="bogo-text-sm bogo-text-danger-500 bogo-mt-1">
                {errors.title}
              </p>
            )}
          </div>

          <div className="md:bogo-col-span-2">
            <label className="bogo-label">{__("Rule Type *", "bogofy")}</label>
            <div className="bogo-grid bogo-grid-cols-1 md:bogo-grid-cols-2 bogo-gap-3">
              {ruleTypes.map((type) => (
                <label
                  key={type.value}
                  className={clsx(
                    "bogo-flex bogo-flex-col bogo-p-4 bogo-border bogo-rounded-lg bogo-cursor-pointer bogo-transition-colors",
                    formData.rule_type === type.value
                      ? "bogo-border-primary-500 bogo-bg-primary-50"
                      : "bogo-border-gray-200 hover:bogo-border-gray-300",
                  )}
                >
                  <div className="bogo-flex bogo-items-center">
                    <input
                      type="radio"
                      name="rule_type"
                      value={type.value}
                      checked={formData.rule_type === type.value}
                      onChange={handleChange}
                      className="bogo-mr-3"
                    />
                    <span className="bogo-font-medium bogo-text-gray-900">
                      {type.label}
                    </span>
                  </div>
                  <p className="bogo-text-sm bogo-text-gray-500 bogo-mt-1 bogo-ml-6">
                    {type.description}
                  </p>
                </label>
              ))}
            </div>
          </div>
        </div>
      </div>

      {/* Quantities */}
      <div className="bogo-card bogo-p-6">
        <h3 className="bogo-text-lg bogo-font-semibold bogo-text-gray-900 bogo-mb-4">
          {__("Quantities & Discount", "bogofy")}
        </h3>
        <div className="bogo-grid bogo-grid-cols-1 md:bogo-grid-cols-3 bogo-gap-4">
          <div>
            <label className="bogo-label">
              {__("Buy Quantity *", "bogofy")}
            </label>
            <input
              type="number"
              name="buy_quantity"
              value={formData.buy_quantity}
              onChange={handleChange}
              min="1"
              className={clsx(
                "bogo-input",
                errors.buy_quantity && "bogo-border-danger-500",
              )}
            />
            {errors.buy_quantity && (
              <p className="bogo-text-sm bogo-text-danger-500 bogo-mt-1">
                {errors.buy_quantity}
              </p>
            )}
          </div>

          <div>
            <label className="bogo-label">
              {__("Free/Discounted Quantity *", "bogofy")}
            </label>
            <input
              type="number"
              name="free_quantity"
              value={formData.free_quantity}
              onChange={handleChange}
              min="1"
              className={clsx(
                "bogo-input",
                errors.free_quantity && "bogo-border-danger-500",
              )}
            />
            {errors.free_quantity && (
              <p className="bogo-text-sm bogo-text-danger-500 bogo-mt-1">
                {errors.free_quantity}
              </p>
            )}
          </div>

          {showDiscountValue && (
            <div>
              <label className="bogo-label">
                {__("Discount Percentage *", "bogofy")}
              </label>
              <input
                type="number"
                name="discount_value"
                value={formData.discount_value}
                onChange={handleChange}
                min="1"
                max="100"
                className={clsx(
                  "bogo-input",
                  errors.discount_value && "bogo-border-danger-500",
                )}
              />
              {errors.discount_value && (
                <p className="bogo-text-sm bogo-text-danger-500 bogo-mt-1">
                  {errors.discount_value}
                </p>
              )}
            </div>
          )}

          <div>
            <label className="bogo-label">
              {__("Max Free Items Per Order", "bogofy")}
            </label>
            <input
              type="number"
              name="max_free_qty"
              value={formData.max_free_qty}
              onChange={handleChange}
              min="1"
              placeholder={__("Unlimited", "bogofy")}
              className="bogo-input"
            />
          </div>

          <div>
            <label className="bogo-label">{__("Priority", "bogofy")}</label>
            <input
              type="number"
              name="priority"
              value={formData.priority}
              onChange={handleChange}
              min="1"
              className="bogo-input"
            />
            <p className="bogo-text-xs bogo-text-gray-500 bogo-mt-1">
              {__("Lower = higher priority", "bogofy")}
            </p>
          </div>
        </div>
      </div>

      {/* Products & Categories */}
      <div className="bogo-card bogo-p-6">
        <h3 className="bogo-text-lg bogo-font-semibold bogo-text-gray-900 bogo-mb-4">
          {__("Product Selection", "bogofy")}
        </h3>

        <div className="bogo-mb-4">
          <label className="bogo-label">{__("Apply To *", "bogofy")}</label>
          <select
            name="apply_to"
            value={formData.apply_to}
            onChange={handleChange}
            className="bogo-select bogo-w-full md:bogo-w-64"
          >
            {applyToOptions.map((option) => (
              <option key={option.value} value={option.value}>
                {option.label}
              </option>
            ))}
          </select>
        </div>

        {formData.apply_to === "specific_products" && (
          <div className="bogo-mb-4">
            <label className="bogo-label">
              {__("Select Products *", "bogofy")}
            </label>
            <ProductSearch
              selectedProducts={selectedBuyProducts}
              onChange={setSelectedBuyProducts}
              placeholder={__("Search for products...", "bogofy")}
            />
            {errors.buy_products && (
              <p className="bogo-text-sm bogo-text-danger-500 bogo-mt-1">
                {errors.buy_products}
              </p>
            )}
          </div>
        )}

        {formData.apply_to === "specific_categories" && (
          <div className="bogo-mb-4">
            <label className="bogo-label">
              {__("Select Categories *", "bogofy")}
            </label>
            <CategorySearch
              selectedCategories={selectedCategories}
              onChange={setSelectedCategories}
              placeholder={__("Search for categories...", "bogofy")}
            />
            {errors.categories && (
              <p className="bogo-text-sm bogo-text-danger-500 bogo-mt-1">
                {errors.categories}
              </p>
            )}
          </div>
        )}

        {showFreeProductSelector && (
          <div>
            <label className="bogo-label">
              {__("Free Product *", "bogofy")}
            </label>
            <ProductSearch
              selectedProducts={selectedFreeProducts}
              onChange={setSelectedFreeProducts}
              placeholder={__("Search for free product...", "bogofy")}
            />
            {errors.free_products && (
              <p className="bogo-text-sm bogo-text-danger-500 bogo-mt-1">
                {errors.free_products}
              </p>
            )}
          </div>
        )}
      </div>

      {/* Scheduling */}
      <div className="bogo-card bogo-p-6">
        <h3 className="bogo-text-lg bogo-font-semibold bogo-text-gray-900 bogo-mb-4">
          {__("Schedule (Optional)", "bogofy")}
        </h3>
        <div className="bogo-grid bogo-grid-cols-1 md:bogo-grid-cols-2 bogo-gap-4">
          <div>
            <label className="bogo-label">{__("Start Date", "bogofy")}</label>
            <input
              type="datetime-local"
              name="start_date"
              value={formData.start_date}
              onChange={handleChange}
              className="bogo-input"
            />
          </div>
          <div>
            <label className="bogo-label">{__("End Date", "bogofy")}</label>
            <input
              type="datetime-local"
              name="end_date"
              value={formData.end_date}
              onChange={handleChange}
              className="bogo-input"
            />
          </div>
        </div>
      </div>

      {/* Message Template */}
      <div className="bogo-card bogo-p-6">
        <h3 className="bogo-text-lg bogo-font-semibold bogo-text-gray-900 bogo-mb-4">
          {__("Display Message (Optional)", "bogofy")}
        </h3>
        <div>
          <label className="bogo-label">
            {__("Product Page Message", "bogofy")}
          </label>
          <textarea
            name="message_template"
            value={formData.message_template}
            onChange={handleChange}
            rows="3"
            className="bogo-input"
            placeholder={__("e.g., Buy {buy_qty}, Get {free_qty} FREE!", "bogofy")}
          />
          <p className="bogo-text-xs bogo-text-gray-500 bogo-mt-1">
            {__(
              "Available placeholders: {buy_qty}, {free_qty}, {free_product}, {discount}%, {start_date}, {end_date}",
              "bogofy",
            )}
          </p>
        </div>
      </div>

      {/* Submit */}
      <div className="bogo-flex bogo-justify-end bogo-gap-4">
        <button
          type="button"
          onClick={() => window.history.back()}
          className="bogo-btn bogo-btn-secondary"
        >
          {__("Cancel", "bogofy")}
        </button>
        <button
          type="submit"
          disabled={isLoading}
          className="bogo-btn bogo-btn-primary"
        >
          {isLoading
            ? __("Saving...", "bogofy")
            : initialData
              ? __("Update Rule", "bogofy")
              : __("Create Rule", "bogofy")}
        </button>
      </div>
    </form>
  );
}

RuleForm.propTypes = {
  initialData: PropTypes.object,
  onSubmit: PropTypes.func.isRequired,
  isLoading: PropTypes.bool,
  formId: PropTypes.string,
};

export default RuleForm;
