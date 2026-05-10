import { get, post, put, patch, del } from './api';

/**
 * Get all rules.
 *
 * @param {Object} params - Query parameters.
 * @returns {Promise<Array>}
 */
export async function getRules(params = {}) {
  return get('/rules', params);
}

/**
 * Get single rule by ID.
 *
 * @param {number} id - Rule ID.
 * @returns {Promise<Object>}
 */
export async function getRule(id) {
  return get(`/rules/${id}`);
}

/**
 * Create new rule.
 *
 * @param {Object} data - Rule data.
 * @returns {Promise<Object>}
 */
export async function createRule(data) {
  return post('/rules', data);
}

/**
 * Update existing rule.
 *
 * @param {number} id - Rule ID.
 * @param {Object} data - Rule data.
 * @returns {Promise<Object>}
 */
export async function updateRule(id, data) {
  return put(`/rules/${id}`, data);
}

/**
 * Delete rule.
 *
 * @param {number} id - Rule ID.
 * @returns {Promise<null>}
 */
export async function deleteRule(id) {
  return del(`/rules/${id}`);
}

/**
 * Toggle rule status.
 *
 * @param {number} id - Rule ID.
 * @param {string} status - New status.
 * @returns {Promise<Object>}
 */
export async function updateRuleStatus(id, status) {
  return patch(`/rules/${id}/status`, { status });
}

/**
 * Perform bulk action on rules.
 *
 * @param {string} action - Action type (delete, activate, deactivate).
 * @param {Array<number>} ids - Rule IDs.
 * @returns {Promise<Object>}
 */
export async function bulkAction(action, ids) {
  return post('/rules/bulk', { action, ids });
}

/**
 * Search products.
 *
 * @param {string} query - Search query.
 * @returns {Promise<Array>}
 */
export async function searchProducts(query) {
  return get('/products/search', { q: query });
}

/**
 * Search categories.
 *
 * @param {string} query - Search query.
 * @returns {Promise<Array>}
 */
export async function searchCategories(query = '') {
  return get('/categories/search', { q: query });
}

export default {
  getRules,
  getRule,
  createRule,
  updateRule,
  deleteRule,
  updateRuleStatus,
  bulkAction,
  searchProducts,
  searchCategories,
};
