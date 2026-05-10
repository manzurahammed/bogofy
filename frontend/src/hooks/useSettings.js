import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import * as settingsApi from '../services/settingsApi';

/**
 * Hook for fetching settings.
 *
 * @returns {Object}
 */
export function useSettings() {
  return useQuery({
    queryKey: ['settings'],
    queryFn: settingsApi.getSettings,
  });
}

/**
 * Hook for updating settings.
 *
 * @returns {Object}
 */
export function useUpdateSettings() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data) => settingsApi.updateSettings(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['settings'] });
    },
  });
}

/**
 * Hook for fetching dashboard stats.
 *
 * @returns {Object}
 */
export function useStats() {
  return useQuery({
    queryKey: ['stats'],
    queryFn: settingsApi.getStats,
  });
}

export default {
  useSettings,
  useUpdateSettings,
  useStats,
};
