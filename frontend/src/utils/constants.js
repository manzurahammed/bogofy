/**
 * Application constants.
 */

export const RULE_TYPES = {
  BUY_X_GET_X: 'buy_x_get_x',
  BUY_X_GET_Y: 'buy_x_get_y',
  BUY_CAT_GET_FREE: 'buy_cat_get_free',
  BUY_X_GET_X_DISCOUNTED: 'buy_x_get_x_discounted',
};

export const RULE_TYPE_LABELS = {
  [RULE_TYPES.BUY_X_GET_X]: 'Buy X Get X Free',
  [RULE_TYPES.BUY_X_GET_Y]: 'Buy X Get Y Free',
  [RULE_TYPES.BUY_CAT_GET_FREE]: 'Buy from Category, Get Free',
  [RULE_TYPES.BUY_X_GET_X_DISCOUNTED]: 'Buy X Get X Discounted',
};

export const DISCOUNT_TYPES = {
  FREE: 'free',
  PERCENTAGE: 'percentage',
};

export const APPLY_TO_TYPES = {
  SPECIFIC_PRODUCTS: 'specific_products',
  SPECIFIC_CATEGORIES: 'specific_categories',
  ALL_PRODUCTS: 'all_products',
};

export const APPLY_TO_LABELS = {
  [APPLY_TO_TYPES.SPECIFIC_PRODUCTS]: 'Specific Products',
  [APPLY_TO_TYPES.SPECIFIC_CATEGORIES]: 'Specific Categories',
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
