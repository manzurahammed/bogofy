import React, { useState } from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { useRules, useDeleteRule, useUpdateRuleStatus } from '../hooks/useRules';
import { useNotification } from '../hooks/useNotification';
import { PageLoader } from '../components/Shared/Loader';
import { ConfirmModal } from '../components/Shared/Modal';
import AppShell from '../components/Layout/AppShell';
import {
	PlusIcon, GiftIcon, EditIcon, TrashIcon, SearchIcon,
} from '../components/Icons';

const PER_PAGE = 20;

const ruleTypeLabels = {
	buy_x_get_x: __( 'Buy X Get X Free', 'bogofy' ),
	buy_x_get_y: __( 'Buy X Get Y Free', 'bogofy' ),
	buy_cat_get_free: __( 'Category Bogofy', 'bogofy' ),
	buy_x_get_x_discounted: __( 'Buy X Get X Discounted', 'bogofy' ),
};

function RuleRow( { rule, onEdit, onDelete, onToggle } ) {
	const isActive    = rule.status === 'active';
	const statusClass = isActive ? 'bogo-status--live' : 'bogo-status--inactive';
	const statusLabel = isActive ? __( 'Live', 'bogofy' ) : __( 'Inactive', 'bogofy' );
	
	return (
		<div className="bogo-rule">
			<div className="bogo-col">
				<div className="bogo-row" style={{ gap: 8 }}>
					<span className="bogo-rule__name">{rule.title}</span>
					<span style={{
						fontSize: 11,
						color: 'var(--muted)',
						background: 'var(--chip)',
						padding: '2px 8px',
						borderRadius: 999
					}}>
            {ruleTypeLabels[rule.rule_type] || rule.rule_type}
          </span>
				</div>
				<div className="bogo-flow">
          <span className="bogo-flow__node">
            {rule.apply_to === 'specific_products'
	            ? sprintf( __( 'Buy %d items', 'bogofy' ), rule.buy_quantity )
	            : sprintf( __( 'Buy %d from category', 'bogofy' ), rule.buy_quantity )}
          </span>
					<span className="bogo-flow__arrow">→</span>
					<span className="bogo-flow__node bogo-flow__node--get">
            <GiftIcon size={12}/>
						{rule.discount_type === 'free'
							? sprintf( __( 'Get %d free', 'bogofy' ), rule.free_quantity )
							/* translators: 1: quantity, 2: discount percentage */
							: sprintf( __( 'Get %1$d at %2$s%% off', 'bogofy' ), rule.free_quantity, rule.discount_value )}
          </span>
				</div>
				{rule.start_date && (
					<div className="bogo-rule__meta">
						<span>{rule.start_date} → {rule.end_date || __( 'ongoing', 'bogofy' )}</span>
					</div>
				)}
			</div>
			<div className="bogo-col bogo-align-right" style={{ fontSize: 12, minWidth: 60 }}>
				<span style={{ color: 'var(--muted)' }}>{__( 'Priority', 'bogofy' )}</span>
				<span style={{ fontWeight: 600, marginTop: 2 }} className="bogo-mono">{rule.priority}</span>
			</div>
			<div className="bogo-row" style={{ gap: 8 }}>
        <span className={`bogo-status ${statusClass}`}>
          <span className="bogo-status__dot"/>
	        {statusLabel}
        </span>
				<button
					className={`bogo-toggle${isActive ? ' bogo-toggle--on' : ''}`}
					onClick={() => onToggle( rule )}
					title={isActive ? __( 'Deactivate', 'bogofy' ) : __( 'Activate', 'bogofy' )}
				/>
				<button
					className="bogo-button bogo-button--sm bogo-button--ghost"
					onClick={() => onEdit( rule.id )}
					title={__( 'Edit', 'bogofy' )}
				>
					<EditIcon size={13}/>
				</button>
				<button
					className="bogo-button bogo-button--sm bogo-button--ghost"
					onClick={() => onDelete( rule.id )}
					title={__( 'Delete', 'bogofy' )}
					style={{ color: 'var(--danger-clr)' }}
				>
					<TrashIcon size={13}/>
				</button>
			</div>
		</div>
	);
}

function RulesPage() {
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
	
	const handleStatusToggle = async ( rule ) => {
		const newStatus = rule.status === 'active' ? 'inactive' : 'active';
		try {
			await updateStatus.mutateAsync( { id: rule.id, status: newStatus } );
			success( newStatus === 'active' ? __( 'Rule activated', 'bogofy' ) : __( 'Rule deactivated', 'bogofy' ) );
		} catch {
			showError( __( 'Failed to update rule status', 'bogofy' ) );
		}
	};
	
	const handleDelete = async () => {
		try {
			await deleteRule.mutateAsync( deleteModal.ruleId );
			success( __( 'Rule deleted successfully', 'bogofy' ) );
			setDeleteModal( { isOpen: false, ruleId: null } );
		} catch {
			showError( __( 'Failed to delete rule', 'bogofy' ) );
		}
	};
	
	const liveCount = rules?.filter( ( r ) => r.status === 'active' ).length ?? 0;
	
	const actions = (
		<button
			className="bogo-button bogo-button--primary bogo-button--sm"
			onClick={() => {
				window.location.href = 'admin.php?page=bogofy&tab=rules&action=create';
			}}
		>
			<PlusIcon size={14}/> {__( 'New rule', 'bogofy' )}
		</button>
	);
	
	if ( isLoading ) return (
		<AppShell crumb={['Bogofy', __( 'Bogofy Rules', 'bogofy' )]} actions={actions}>
			<PageLoader/>
		</AppShell>
	);
	
	if ( error ) return (
		<AppShell crumb={['Bogofy', __( 'Bogofy Rules', 'bogofy' )]} actions={actions}>
			<div style={{ textAlign: 'center', padding: '48px 20px', color: 'var(--muted)' }}>
				{__( 'Failed to load rules. Please try again.', 'bogofy' )}
			</div>
		</AppShell>
	);
	
	return (
		<AppShell crumb={['Bogofy', __( 'Bogofy Rules', 'bogofy' )]} actions={actions}>
			<div className="bogo-page-header">
				<div>
					<div className="bogo-page-header__title">{__( 'Bogofy Rules', 'bogofy' )}</div>
					{/* translators: 1: active rule count, 2: total rule count */}
					<div
						className="bogo-page-header__desc">{sprintf( __( '%1$d active · %2$d total', 'bogofy' ), liveCount, rules?.length ?? 0 )}</div>
				</div>
				<div className="bogo-row" style={{ gap: 10 }}>
					<div style={{ position: 'relative' }}>
						<input
							className="bogo-form-input"
							placeholder={__( 'Search rules…', 'bogofy' )}
							value={search}
							onChange={( e ) => setSearch( e.target.value )}
							style={{ width: 240, paddingLeft: 32 }}
						/>
						<SearchIcon size={14} stroke="var(--muted-2)"
						            style={{ position: 'absolute', left: 11, top: 11 }}/>
					</div>
					<div className="bogo-tabs">
						<button className={`bogo-tabs__tab${statusFilter === '' ? ' bogo-tabs__tab--active' : ''}`}
						        onClick={() => setStatusFilter( '' )}>{__( 'All', 'bogofy' )}</button>
						<button
							className={`bogo-tabs__tab${statusFilter === 'active' ? ' bogo-tabs__tab--active' : ''}`}
							onClick={() => setStatusFilter( 'active' )}>{__( 'Live', 'bogofy' )}</button>
						<button
							className={`bogo-tabs__tab${statusFilter === 'inactive' ? ' bogo-tabs__tab--active' : ''}`}
							onClick={() => setStatusFilter( 'inactive' )}>{__( 'Inactive', 'bogofy' )}</button>
					</div>
				</div>
			</div>
			
			<div className="bogo-rule-list">
				{rules && rules.length > 0 ? (
					rules.map( ( rule ) => (
						<RuleRow
							key={rule.id}
							rule={rule}
							onEdit={( id ) => {
								window.location.href = `admin.php?page=bogofy&tab=rules&action=edit&rule_id=${id}`;
							}}
							onDelete={( id ) => setDeleteModal( { isOpen: true, ruleId: id } )}
							onToggle={handleStatusToggle}
						/>
					) )
				) : (
					<div className="bogo-panel" style={{ textAlign: 'center', padding: '48px 20px' }}>
						<GiftIcon size={40} stroke="var(--muted-2)"/>
						<div style={{
							marginTop: 14,
							fontSize: 15,
							fontWeight: 600
						}}>{__( 'No rules found', 'bogofy' )}</div>
						<div style={{ fontSize: 13, color: 'var(--muted)', marginTop: 4 }}>
							{search ? __( 'Try a different search term.', 'bogofy' ) : __( 'Create your first Bogofy rule to get started.', 'bogofy' )}
						</div>
						{!search && (
							<button
								className="bogo-button bogo-button--primary bogo-button--sm"
								style={{ marginTop: 16 }}
								onClick={() => {
									window.location.href = 'admin.php?page=bogofy&tab=rules&action=create';
								}}
							>
								<PlusIcon size={13}/> {__( 'Create rule', 'bogofy' )}
							</button>
						)}
					</div>
				)}
			</div>
			
			{rules && ( page > 1 || rules.length === PER_PAGE ) && (
				<div className="bogo-row"
				     style={{ justifyContent: 'space-between', marginTop: 18, color: 'var(--muted)', fontSize: 12 }}>
					{/* translators: %d: number of rules shown */}
					<span>{sprintf( __( 'Showing %d rules', 'bogofy' ), rules.length )}</span>
					<div className="bogo-row" style={{ gap: 6 }}>
						<button className="bogo-button bogo-button--sm"
						        onClick={() => setPage( ( p ) => Math.max( 1, p - 1 ) )}
						        disabled={page === 1}>{__( '‹ Prev', 'bogofy' )}</button>
						<button className="bogo-button bogo-button--sm" onClick={() => setPage( ( p ) => p + 1 )}
						        disabled={rules.length < PER_PAGE}>{__( 'Next ›', 'bogofy' )}</button>
					</div>
				</div>
			)}
			
			<ConfirmModal
				isOpen={deleteModal.isOpen}
				onClose={() => setDeleteModal( { isOpen: false, ruleId: null } )}
				onConfirm={handleDelete}
				title={__( 'Delete Rule', 'bogofy' )}
				message={__( 'Are you sure you want to delete this rule? This action cannot be undone.', 'bogofy' )}
				confirmText={__( 'Delete', 'bogofy' )}
			/>
		</AppShell>
	);
}

export default RulesPage;
