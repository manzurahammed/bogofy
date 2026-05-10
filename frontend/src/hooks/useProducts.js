import { useState, useCallback } from 'react';
import { useQuery } from '@tanstack/react-query';
import * as rulesApi from '../services/rulesApi';

/**
 * Hook for searching products with debounce.
 *
 * @param {Object} options - Hook options.
 * @returns {Object}
 */
export function useProductSearch(options = {}) {
  const [searchTerm, setSearchTerm] = useState('');
  const { minChars = 2 } = options;

  const { data, isLoading, error } = useQuery({
    queryKey: ['products', 'search', searchTerm],
    queryFn: () => rulesApi.searchProducts(searchTerm),
    enabled: searchTerm.length >= minChars,
  });

  const search = useCallback((term) => {
    setSearchTerm(term);
  }, []);

  const clear = useCallback(() => {
    setSearchTerm('');
  }, []);

  return {
    products: data || [],
    isLoading,
    error,
    searchTerm,
    search,
    clear,
  };
}

/**
 * Hook for searching categories.
 *
 * @returns {Object}
 */
export function useCategorySearch() {
  const [searchTerm, setSearchTerm] = useState('');

  const { data, isLoading, error } = useQuery({
    queryKey: ['categories', 'search', searchTerm],
    queryFn: () => rulesApi.searchCategories(searchTerm),
  });

  const search = useCallback((term) => {
    setSearchTerm(term);
  }, []);

  const clear = useCallback(() => {
    setSearchTerm('');
  }, []);

  return {
    categories: data || [],
    isLoading,
    error,
    searchTerm,
    search,
    clear,
  };
}

export default {
  useProductSearch,
  useCategorySearch,
};
