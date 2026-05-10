/**
 * Form validation helpers.
 */

/**
 * Check if value is empty.
 *
 * @param {any} value - Value to check.
 * @returns {boolean}
 */
export function isEmpty(value) {
  if (value === null || value === undefined) return true;
  if (typeof value === 'string') return value.trim() === '';
  if (Array.isArray(value)) return value.length === 0;
  if (typeof value === 'object') return Object.keys(value).length === 0;
  return false;
}

/**
 * Check if value is a valid positive integer.
 *
 * @param {any} value - Value to check.
 * @param {number} min - Minimum value.
 * @returns {boolean}
 */
export function isPositiveInt(value, min = 1) {
  const num = parseInt(value, 10);
  return !isNaN(num) && num >= min;
}

/**
 * Check if value is a valid percentage.
 *
 * @param {any} value - Value to check.
 * @returns {boolean}
 */
export function isValidPercentage(value) {
  const num = parseFloat(value);
  return !isNaN(num) && num > 0 && num <= 100;
}

/**
 * Validate rule form data.
 *
 * @param {Object} data - Form data.
 * @returns {Object} Errors object.
 */
export function validateRuleForm(data) {
  const errors = {};

  if (isEmpty(data.title)) {
    errors.title = 'Title is required';
  }

  if (!isPositiveInt(data.buy_quantity, 1)) {
    errors.buy_quantity = 'Must be at least 1';
  }

  if (!isPositiveInt(data.free_quantity, 1)) {
    errors.free_quantity = 'Must be at least 1';
  }

  if (data.discount_type === 'percentage' && !isValidPercentage(data.discount_value)) {
    errors.discount_value = 'Must be between 1 and 100';
  }

  if (data.apply_to === 'specific_products' && isEmpty(data.buy_product_ids)) {
    errors.buy_products = 'Select at least one product';
  }

  if (data.apply_to === 'specific_categories' && isEmpty(data.category_ids)) {
    errors.categories = 'Select at least one category';
  }

  if (
    (data.rule_type === 'buy_x_get_y' || data.rule_type === 'buy_cat_get_free') &&
    isEmpty(data.free_product_ids)
  ) {
    errors.free_products = 'Select the free product';
  }

  if (data.start_date && data.end_date && new Date(data.start_date) > new Date(data.end_date)) {
    errors.end_date = 'End date must be after start date';
  }

  return errors;
}

/**
 * Validate settings form data.
 *
 * @param {Object} data - Form data.
 * @returns {Object} Errors object.
 */
export function validateSettingsForm(data) {
  const errors = {};

  if (isEmpty(data.free_item_label)) {
    errors.free_item_label = 'Label is required';
  }

  return errors;
}
