import { get, post } from './api';

/**
 * Get plugin settings.
 *
 * @returns {Promise<Object>}
 */
export async function getSettings() {
  return get('/settings');
}

/**
 * Update plugin settings.
 *
 * @param {Object} data - Settings data.
 * @returns {Promise<Object>}
 */
export async function updateSettings(data) {
  return post('/settings', data);
}

/**
 * Get dashboard stats.
 *
 * @returns {Promise<Object>}
 */
export async function getStats() {
  return get('/stats');
}

export default {
  getSettings,
  updateSettings,
  getStats,
};
