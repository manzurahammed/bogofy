/**
 * Client-side formatting utilities.
 */

/**
 * Format date for display.
 *
 * @param {string} dateString - ISO date string.
 * @param {Object} options - Intl.DateTimeFormat options.
 * @returns {string}
 */
export function formatDate(dateString, options = {}) {
  if (!dateString) return '';

  const defaultOptions = {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  };

  const date = new Date(dateString);
  return date.toLocaleDateString(undefined, { ...defaultOptions, ...options });
}

/**
 * Format date and time for display.
 *
 * @param {string} dateString - ISO date string.
 * @returns {string}
 */
export function formatDateTime(dateString) {
  if (!dateString) return '';

  const date = new Date(dateString);
  return date.toLocaleDateString(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

/**
 * Format currency amount.
 *
 * @param {number} amount - Amount to format.
 * @param {string} currency - Currency code.
 * @returns {string}
 */
export function formatCurrency(amount, currency = 'USD') {
  return new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency,
  }).format(amount);
}

/**
 * Format percentage.
 *
 * @param {number} value - Percentage value.
 * @returns {string}
 */
export function formatPercentage(value) {
  return `${value}%`;
}

/**
 * Format rule type for display.
 *
 * @param {string} type - Rule type.
 * @returns {string}
 */
export function formatRuleType(type) {
  const labels = {
    buy_x_get_x: 'Buy X Get X Free',
    buy_x_get_y: 'Buy X Get Y Free',
    buy_cat_get_free: 'Buy from Category, Get Free',
    buy_x_get_x_discounted: 'Buy X Get X Discounted',
  };

  return labels[type] || type;
}

/**
 * Format rule summary.
 *
 * @param {Object} rule - Rule object.
 * @returns {string}
 */
export function formatRuleSummary(rule) {
  const { buy_quantity, free_quantity, discount_type, discount_value } = rule;

  if (discount_type === 'free') {
    return `Buy ${buy_quantity}, Get ${free_quantity} FREE`;
  }

  return `Buy ${buy_quantity}, Get ${free_quantity} at ${discount_value}% OFF`;
}

/**
 * Truncate text with ellipsis.
 *
 * @param {string} text - Text to truncate.
 * @param {number} maxLength - Maximum length.
 * @returns {string}
 */
export function truncate(text, maxLength = 50) {
  if (!text || text.length <= maxLength) return text;
  return `${text.substring(0, maxLength)}...`;
}

/**
 * Format datetime for input field.
 *
 * @param {string} dateString - ISO date string.
 * @returns {string}
 */
export function formatDateTimeForInput(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toISOString().slice(0, 16);
}
