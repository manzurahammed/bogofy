import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import * as rulesApi from '../services/rulesApi';

/**
 * Hook for fetching rules list.
 *
 * @param {Object} params - Query parameters.
 * @returns {Object}
 */
export function useRules(params = {}) {
  return useQuery({
    queryKey: ['rules', params],
    queryFn: () => rulesApi.getRules(params),
  });
}

/**
 * Hook for fetching single rule.
 *
 * @param {number} id - Rule ID.
 * @returns {Object}
 */
export function useRule(id) {
  return useQuery({
    queryKey: ['rule', id],
    queryFn: () => rulesApi.getRule(id),
    enabled: !!id,
  });
}

/**
 * Hook for creating a rule.
 *
 * @returns {Object}
 */
export function useCreateRule() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data) => rulesApi.createRule(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['rules'] });
    },
  });
}

/**
 * Hook for updating a rule.
 *
 * @returns {Object}
 */
export function useUpdateRule() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, data }) => rulesApi.updateRule(id, data),
    onSuccess: (_, { id }) => {
      queryClient.invalidateQueries({ queryKey: ['rules'] });
      queryClient.invalidateQueries({ queryKey: ['rule', id] });
    },
  });
}

/**
 * Hook for deleting a rule.
 *
 * @returns {Object}
 */
export function useDeleteRule() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id) => rulesApi.deleteRule(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['rules'] });
    },
  });
}

/**
 * Hook for updating rule status.
 *
 * @returns {Object}
 */
export function useUpdateRuleStatus() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, status }) => rulesApi.updateRuleStatus(id, status),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['rules'] });
    },
  });
}

/**
 * Hook for bulk actions.
 *
 * @returns {Object}
 */
export function useBulkAction() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ action, ids }) => rulesApi.bulkAction(action, ids),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['rules'] });
    },
  });
}

export default {
  useRules,
  useRule,
  useCreateRule,
  useUpdateRule,
  useDeleteRule,
  useUpdateRuleStatus,
  useBulkAction,
};
