import { useState, useCallback, useMemo } from 'react';
import { __ } from '@wordpress/i18n';
import { useRules, useDeleteRule, useUpdateRuleStatus } from '../../../hooks/useRules';
import { useNotification } from '../../../hooks/useNotification';

const PER_PAGE = 20;

/**
 * Data + interaction logic for the rules list screen.
 *
 * @returns {Object} List state and handlers consumed by the RulesPage UI.
 */
export function useRulesManager() {
	const [page, setPage]                 = useState( 1 );
	const [search, setSearch]             = useState( '' );
	const [statusFilter, setStatusFilter] = useState( '' );
	const [deleteModal, setDeleteModal]   = useState( { isOpen: false, ruleId: null } );

	const { data: rules, isLoading, error } = useRules( {
		                                                    page,
		                                                    per_page: PER_PAGE,
		                                                    search,
		                                                    status: statusFilter,
	                                                    } );

	const deleteRule                    = useDeleteRule();
	const updateStatus                  = useUpdateRuleStatus();
	const { success, error: showError } = useNotification();

	const toggleStatus = useCallback( async ( rule ) => {
		const newStatus = rule.status === 'active' ? 'inactive' : 'active';
		try {
			await updateStatus.mutateAsync( { id: rule.id, status: newStatus } );
			success( newStatus === 'active' ? __( 'Rule activated', 'bogofy' ) : __( 'Rule deactivated', 'bogofy' ) );
		} catch {
			showError( __( 'Failed to update rule status', 'bogofy' ) );
		}
	}, [updateStatus, success, showError] );

	const confirmDelete = useCallback( async () => {
		try {
			await deleteRule.mutateAsync( deleteModal.ruleId );
			success( __( 'Rule deleted successfully', 'bogofy' ) );
			setDeleteModal( { isOpen: false, ruleId: null } );
		} catch {
			showError( __( 'Failed to delete rule', 'bogofy' ) );
		}
	}, [deleteRule, deleteModal.ruleId, success, showError] );

	const openDelete  = useCallback( ( ruleId ) => setDeleteModal( { isOpen: true, ruleId } ), [] );
	const closeDelete = useCallback( () => setDeleteModal( { isOpen: false, ruleId: null } ), [] );

	const liveCount = useMemo(
		() => rules?.filter( ( r ) => r.status === 'active' ).length ?? 0,
		[rules]
	);

	return {
		perPage: PER_PAGE,
		rules,
		isLoading,
		error,
		page,
		setPage,
		search,
		setSearch,
		statusFilter,
		setStatusFilter,
		deleteModal,
		liveCount,
		toggleStatus,
		confirmDelete,
		openDelete,
		closeDelete,
	};
}
