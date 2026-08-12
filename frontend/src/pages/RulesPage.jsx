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
  buy_x_get_x: __('Buy X Get X Free', 'buy-one-get-one'),
  buy_x_get_y: __('Buy X Get Y Free', 'buy-one-get-one'),
  buy_cat_get_free: __('Category BOGO', 'buy-one-get-one'),
  buy_x_get_x_discounted: __('Buy X Get X Discounted', 'buy-one-get-one'),
};

function RuleRow({ rule, onEdit, onDelete, onToggle }) {
  const isActive = rule.status === 'active';
  const statusClass = isActive ? 'bogo-status--live' : 'bogo-status--inactive';
  const statusLabel = isActive ? __('Live', 'buy-one-get-one') : __('Inactive', 'buy-one-get-one');

  return (
    <div className="bogo-rule">
      <div className="bogo-col">
        <div className="bogo-row" style={{ gap: 8 }}>
          <span className="bogo-rule__name">{rule.title}</span>
          <span style={{ fontSize: 11, color: 'var(--muted)', background: 'var(--chip)', padding: '2px 8px', borderRadius: 999 }}>
            {ruleTypeLabels[rule.rule_type] || rule.rule_type}
          </span>
        </div>
        <div className="bogo-flow">
          <span className="bogo-flow__node">
            {rule.apply_to === 'specific_products'
              ? sprintf(__('Buy %d items', 'buy-one-get-one'), rule.buy_quantity)
              : sprintf(__('Buy %d from category', 'buy-one-get-one'), rule.buy_quantity)}
          </span>
          <span className="bogo-flow__arrow">→</span>
          <span className="bogo-flow__node bogo-flow__node--get">
            <GiftIcon size={12} />
            {rule.discount_type === 'free'
              ? sprintf(__('Get %d free', 'buy-one-get-one'), rule.free_quantity)
              /* translators: 1: quantity, 2: discount percentage */
              : sprintf(__('Get %1$d at %2$s%% off', 'buy-one-get-one'), rule.free_quantity, rule.discount_value)}
          </span>
        </div>
        {rule.start_date && (
          <div className="bogo-rule__meta">
            <span>{rule.start_date} → {rule.end_date || __('ongoing', 'buy-one-get-one')}</span>
          </div>
        )}
      </div>
      <div className="bogo-col bogo-align-right" style={{ fontSize: 12, minWidth: 60 }}>
        <span style={{ color: 'var(--muted)' }}>{__('Priority', 'buy-one-get-one')}</span>
        <span style={{ fontWeight: 600, marginTop: 2 }} className="bogo-mono">{rule.priority}</span>
      </div>
      <div className="bogo-row" style={{ gap: 8 }}>
        <span className={`bogo-status ${statusClass}`}>
          <span className="bogo-status__dot" />
          {statusLabel}
        </span>
        <button
          className={`bogo-toggle${isActive ? ' bogo-toggle--on' : ''}`}
          onClick={() => onToggle(rule)}
          title={isActive ? __('Deactivate', 'buy-one-get-one') : __('Activate', 'buy-one-get-one')}
        />
        <button
          className="bogo-button bogo-button--sm bogo-button--ghost"
          onClick={() => onEdit(rule.id)}
          title={__('Edit', 'buy-one-get-one')}
        >
          <EditIcon size={13} />
        </button>
        <button
          className="bogo-button bogo-button--sm bogo-button--ghost"
          onClick={() => onDelete(rule.id)}
          title={__('Delete', 'buy-one-get-one')}
          style={{ color: 'var(--danger-clr)' }}
        >
          <TrashIcon size={13} />
        </button>
      </div>
    </div>
  );
}

function RulesPage() {
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [deleteModal, setDeleteModal] = useState({ isOpen: false, ruleId: null });

  const { data: rules, isLoading, error } = useRules({
    page,
    per_page: PER_PAGE,
    search,
    status: statusFilter,
  });

  const deleteRule = useDeleteRule();
  const updateStatus = useUpdateRuleStatus();
  const { success, error: showError } = useNotification();

  const handleStatusToggle = async (rule) => {
    const newStatus = rule.status === 'active' ? 'inactive' : 'active';
    try {
      await updateStatus.mutateAsync({ id: rule.id, status: newStatus });
      success(newStatus === 'active' ? __('Rule activated', 'buy-one-get-one') : __('Rule deactivated', 'buy-one-get-one'));
    } catch {
      showError(__('Failed to update rule status', 'buy-one-get-one'));
    }
  };

  const handleDelete = async () => {
    try {
      await deleteRule.mutateAsync(deleteModal.ruleId);
      success(__('Rule deleted successfully', 'buy-one-get-one'));
      setDeleteModal({ isOpen: false, ruleId: null });
    } catch {
      showError(__('Failed to delete rule', 'buy-one-get-one'));
    }
  };

  const liveCount = rules?.filter((r) => r.status === 'active').length ?? 0;

  const actions = (
    <button
      className="bogo-button bogo-button--primary bogo-button--sm"
      onClick={() => { window.location.href = 'admin.php?page=buy-one-get-one&tab=rules&action=create'; }}
    >
      <PlusIcon size={14} /> {__('New rule', 'buy-one-get-one')}
    </button>
  );

  if (isLoading) return (
    <AppShell crumb={['Bogo', __('BOGO Rules', 'buy-one-get-one')]} actions={actions}>
      <PageLoader />
    </AppShell>
  );

  if (error) return (
    <AppShell crumb={['Bogo', __('BOGO Rules', 'buy-one-get-one')]} actions={actions}>
      <div style={{ textAlign: 'center', padding: '48px 20px', color: 'var(--muted)' }}>
        {__('Failed to load rules. Please try again.', 'buy-one-get-one')}
      </div>
    </AppShell>
  );

  return (
    <AppShell crumb={['Bogo', __('BOGO Rules', 'buy-one-get-one')]} actions={actions}>
      <div className="bogo-page-header">
        <div>
          <div className="bogo-page-header__title">{__('BOGO Rules', 'buy-one-get-one')}</div>
          {/* translators: 1: active rule count, 2: total rule count */}
          <div className="bogo-page-header__desc">{sprintf(__('%1$d active · %2$d total', 'buy-one-get-one'), liveCount, rules?.length ?? 0)}</div>
        </div>
        <div className="bogo-row" style={{ gap: 10 }}>
          <div style={{ position: 'relative' }}>
            <input
              className="bogo-form-input"
              placeholder={__('Search rules…', 'buy-one-get-one')}
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              style={{ width: 240, paddingLeft: 32 }}
            />
            <SearchIcon size={14} stroke="var(--muted-2)" style={{ position: 'absolute', left: 11, top: 11 }} />
          </div>
          <div className="bogo-tabs">
            <button className={`bogo-tabs__tab${statusFilter === '' ? ' bogo-tabs__tab--active' : ''}`} onClick={() => setStatusFilter('')}>{__('All', 'buy-one-get-one')}</button>
            <button className={`bogo-tabs__tab${statusFilter === 'active' ? ' bogo-tabs__tab--active' : ''}`} onClick={() => setStatusFilter('active')}>{__('Live', 'buy-one-get-one')}</button>
            <button className={`bogo-tabs__tab${statusFilter === 'inactive' ? ' bogo-tabs__tab--active' : ''}`} onClick={() => setStatusFilter('inactive')}>{__('Inactive', 'buy-one-get-one')}</button>
          </div>
        </div>
      </div>

      <div className="bogo-rule-list">
        {rules && rules.length > 0 ? (
          rules.map((rule) => (
            <RuleRow
              key={rule.id}
              rule={rule}
              onEdit={(id) => { window.location.href = `admin.php?page=buy-one-get-one&tab=rules&action=edit&rule_id=${id}`; }}
              onDelete={(id) => setDeleteModal({ isOpen: true, ruleId: id })}
              onToggle={handleStatusToggle}
            />
          ))
        ) : (
          <div className="bogo-panel" style={{ textAlign: 'center', padding: '48px 20px' }}>
            <GiftIcon size={40} stroke="var(--muted-2)" />
            <div style={{ marginTop: 14, fontSize: 15, fontWeight: 600 }}>{__('No rules found', 'buy-one-get-one')}</div>
            <div style={{ fontSize: 13, color: 'var(--muted)', marginTop: 4 }}>
              {search ? __('Try a different search term.', 'buy-one-get-one') : __('Create your first BOGO rule to get started.', 'buy-one-get-one')}
            </div>
            {!search && (
              <button
                className="bogo-button bogo-button--primary bogo-button--sm"
                style={{ marginTop: 16 }}
                onClick={() => { window.location.href = 'admin.php?page=buy-one-get-one&tab=rules&action=create'; }}
              >
                <PlusIcon size={13} /> {__('Create rule', 'buy-one-get-one')}
              </button>
            )}
          </div>
        )}
      </div>

      {rules && (page > 1 || rules.length === PER_PAGE) && (
        <div className="bogo-row" style={{ justifyContent: 'space-between', marginTop: 18, color: 'var(--muted)', fontSize: 12 }}>
          {/* translators: %d: number of rules shown */}
          <span>{sprintf(__('Showing %d rules', 'buy-one-get-one'), rules.length)}</span>
          <div className="bogo-row" style={{ gap: 6 }}>
            <button className="bogo-button bogo-button--sm" onClick={() => setPage((p) => Math.max(1, p - 1))} disabled={page === 1}>{__('‹ Prev', 'buy-one-get-one')}</button>
            <button className="bogo-button bogo-button--sm" onClick={() => setPage((p) => p + 1)} disabled={rules.length < PER_PAGE}>{__('Next ›', 'buy-one-get-one')}</button>
          </div>
        </div>
      )}

      <ConfirmModal
        isOpen={deleteModal.isOpen}
        onClose={() => setDeleteModal({ isOpen: false, ruleId: null })}
        onConfirm={handleDelete}
        title={__('Delete Rule', 'buy-one-get-one')}
        message={__('Are you sure you want to delete this rule? This action cannot be undone.', 'buy-one-get-one')}
        confirmText={__('Delete', 'buy-one-get-one')}
      />
    </AppShell>
  );
}

export default RulesPage;
