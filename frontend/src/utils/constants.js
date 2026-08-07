/**
 * Application constants.
 */

export const RULE_TYPES = {
  BUY_X_GET_X: 'buy_x_get_x',
  BUY_X_GET_Y: 'buy_x_get_y',
};

export const RULE_TYPE_LABELS = {
  [RULE_TYPES.BUY_X_GET_X]: 'Buy X Get X Free',
  [RULE_TYPES.BUY_X_GET_Y]: 'Buy X Get Y Free',
};

export const DISCOUNT_TYPES = {
  FREE: 'free',
};

export const APPLY_TO_TYPES = {
  SPECIFIC_PRODUCTS: 'specific_products',
  ALL_PRODUCTS: 'all_products',
};

export const APPLY_TO_LABELS = {
  [APPLY_TO_TYPES.SPECIFIC_PRODUCTS]: 'Specific Products',
  [APPLY_TO_TYPES.ALL_PRODUCTS]: 'All Products',
};

export const STATUS = {
  ACTIVE: 'active',
  INACTIVE: 'inactive',
};

export const ROUTES = {
  DASHBOARD: 'dashboard',
  RULES: 'rules',
  SETTINGS: 'settings',
};

export const API_ENDPOINTS = {
  RULES: '/rules',
  SETTINGS: '/settings',
  STATS: '/stats',
  PRODUCTS_SEARCH: '/products/search',
  CATEGORIES_SEARCH: '/categories/search',
};
