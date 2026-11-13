import { useState, useCallback } from "react";
import { __ } from "@wordpress/i18n";
import { useCreateRule } from "../../../hooks/useRules";
import { useNotification } from "../../../hooks/useNotification";
import { goToAdmin } from "../../../utils/navigation";
import { defaultData } from "./constants";

/**
 * Encapsulates all state and behaviour for the create-rule wizard.
 *
 * @returns {Object} Wizard state and handlers consumed by the wizard UI.
 */
export function useCreateRuleWizard() {
  const [step, setStep] = useState(1);
  const [formData, setFormData] = useState(defaultData);
  const [selectedBuyProducts, setSelectedBuyProducts] = useState([]);
  const [selectedFreeProducts, setSelectedFreeProducts] = useState([]);
  const [selectedCategories, setSelectedCategories] = useState([]);
  const [selectedExcludedProducts, setSelectedExcludedProducts] = useState([]);
  const [selectedExcludedCategories, setSelectedExcludedCategories] = useState(
    [],
  );
  const [errors, setErrors] = useState({});

  const createRule = useCreateRule();
  const { success, error } = useNotification();

  const needsGiftProduct = (ruleType) =>
    ruleType === "buy_x_get_y" || ruleType === "buy_cat_get_free";

  const validate = useCallback(() => {
    const errs = {};
    if (!formData.title.trim())
      errs.title = __("Rule name is required", "bogofy");
    if (
      step >= 2 &&
      formData.apply_to === "specific_products" &&
      selectedBuyProducts.length === 0
    ) {
      errs.buy_products = __("Select at least one trigger product", "bogofy");
    }
    if (
      step >= 2 &&
      formData.apply_to === "specific_categories" &&
      selectedCategories.length === 0
    ) {
      errs.categories = __("Select at least one category", "bogofy");
    }
    if (
      step >= 2 &&
      needsGiftProduct(formData.rule_type) &&
      selectedFreeProducts.length === 0
    ) {
      errs.free_products = __("Select at least one gift product", "bogofy");
    }
    if (
      step >= 2 &&
      formData.rule_type === "buy_x_get_x_discounted" &&
      (formData.discount_value <= 0 || formData.discount_value > 100)
    ) {
      errs.discount_value = __("Enter a discount between 1 and 100", "bogofy");
    }
    const { min_subtotal: minSubtotal, min_items: minItems } =
      formData.conditions;
    if (step >= 3 && minSubtotal !== "" && !(Number(minSubtotal) >= 0)) {
      errs.min_subtotal = __("Minimum subtotal cannot be negative", "bogofy");
    }
    if (
      step >= 3 &&
      minItems !== "" &&
      !(Number.isInteger(Number(minItems)) && Number(minItems) >= 0)
    ) {
      errs.min_items = __(
        "Minimum items must be a whole number, 0 or more",
        "bogofy",
      );
    }
    setErrors(errs);
    return Object.keys(errs).length === 0;
  }, [
    formData,
    step,
    selectedBuyProducts,
    selectedFreeProducts,
    selectedCategories,
  ]);

  const goNext = useCallback(() => {
    if (!validate()) return;
    setStep((s) => Math.min(4, s + 1));
  }, [validate]);

  const goPrev = useCallback(() => setStep((s) => Math.max(1, s - 1)), []);

  // Allow jumping straight back to any earlier step (e.g. from the stepper).
  // Going backward never needs validation; forward jumps are ignored.
  const goToStep = useCallback(
    (target) => setStep((s) => (target < s ? Math.max(1, target) : s)),
    [],
  );

  const submitRule = useCallback(
    async (status) => {
      if (!validate()) return;
      const data = {
        ...formData,
        status,
        buy_product_ids: selectedBuyProducts.map((p) => p.id),
        free_product_ids: selectedFreeProducts.map((p) => p.id),
        category_ids: selectedCategories.map((c) => c.id),
        max_free_qty: formData.max_free_qty
          ? Number(formData.max_free_qty)
          : null,
        start_date: formData.start_date || null,
        end_date: formData.end_date || null,
        conditions: {
          min_subtotal: Number(formData.conditions.min_subtotal) || 0,
          min_items: Number(formData.conditions.min_items) || 0,
          excluded_product_ids: selectedExcludedProducts.map((p) => p.id),
          excluded_category_ids: selectedExcludedCategories.map((c) => c.id),
          exclude_sale_items: !!formData.conditions.exclude_sale_items,
        },
      };
      try {
        await createRule.mutateAsync(data);
        success(__("Rule created successfully", "bogofy"));
        goToAdmin("tab=rules");
      } catch (err) {
        error(err.message || __("Failed to create rule", "bogofy"));
      }
    },
    [
      validate,
      formData,
      selectedBuyProducts,
      selectedFreeProducts,
      selectedCategories,
      selectedExcludedProducts,
      selectedExcludedCategories,
      createRule,
      success,
      error,
    ],
  );

  return {
    step,
    formData,
    setFormData,
    selectedBuyProducts,
    setSelectedBuyProducts,
    selectedFreeProducts,
    setSelectedFreeProducts,
    selectedCategories,
    setSelectedCategories,
    selectedExcludedProducts,
    setSelectedExcludedProducts,
    selectedExcludedCategories,
    setSelectedExcludedCategories,
    errors,
    goNext,
    goPrev,
    goToStep,
    submitRule,
    isPending: createRule.isPending,
  };
}
