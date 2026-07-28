import React, { useState } from 'react';
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
  buy_x_get_x: 'Buy X Get X Free',
  buy_x_get_y: 'Buy X Get Y Free',
  buy_cat_get_free: 'Category BOGO',
  buy_x_get_x_discounted: 'Buy X Get X Discounted',
};

function RuleRow({ rule, onEdit, onDelete, onToggle }) {
  const isActive = rule.status === 'active';
  const statusClass = isActive ? 'bogo-status--live' : 'bogo-status--inactive';
  const statusLabel = isActive ? 'Live' : 'Inactive';

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
            Buy {rule.buy_quantity}
            {rule.apply_to === 'specific_products' ? ' items' : ' from category'}
          </span>
          <span className="bogo-flow__arrow">→</span>
          <span className="bogo-flow__node bogo-flow__node--get">
            <GiftIcon size={12} />
            Get {rule.free_quantity}
            {rule.discount_type === 'free' ? ' free' : ` at ${rule.discount_value}% off`}
          </span>
        </div>
        {rule.start_date && (
          <div className="bogo-rule__meta">
            <span>{rule.start_date} → {rule.end_date || 'ongoing'}</span>
          </div>
        )}
      </div>
      <div className="bogo-col bogo-align-right" style={{ fontSize: 12, minWidth: 60 }}>
        <span style={{ color: 'var(--muted)' }}>Priority</span>
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
          title={isActive ? 'Deactivate' : 'Activate'}
        />
        <button
          className="bogo-button bogo-button--sm bogo-button--ghost"
          onClick={() => onEdit(rule.id)}
          title="Edit"
        >
          <EditIcon size={13} />
        </button>
        <button
          className="bogo-button bogo-button--sm bogo-button--ghost"
          onClick={() => onDelete(rule.id)}
          title="Delete"
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
      success(`Rule ${newStatus === 'active' ? 'activated' : 'deactivated'}`);
    } catch {
      showError('Failed to update rule status');
    }
  };

  const handleDelete = async () => {
    try {
      await deleteRule.mutateAsync(deleteModal.ruleId);
      success('Rule deleted successfully');
      setDeleteModal({ isOpen: false, ruleId: null });
    } catch {
      showError('Failed to delete rule');
    }
  };

  const liveCount = rules?.filter((r) => r.status === 'active').length ?? 0;

  const actions = (
    <button
      className="bogo-button bogo-button--primary bogo-button--sm"
      onClick={() => { window.location.href = 'admin.php?page=buy-one-get-one&tab=rules&action=create'; }}
    >
      <PlusIcon size={14} /> New rule
    </button>
  );

  if (isLoading) return (
    <AppShell crumb={['Bogo', 'BOGO Rules']} actions={actions}>
      <PageLoader />
    </AppShell>
  );

  if (error) return (
    <AppShell crumb={['Bogo', 'BOGO Rules']} actions={actions}>
      <div style={{ textAlign: 'center', padding: '48px 20px', color: 'var(--muted)' }}>
        Failed to load rules. Please try again.
      </div>
    </AppShell>
  );

  return (
    <AppShell crumb={['Bogo', 'BOGO Rules']} actions={actions}>
      <div className="bogo-page-header">
        <div>
          <div className="bogo-page-header__title">BOGO Rules</div>
          <div className="bogo-page-header__desc">{liveCount} active · {rules?.length ?? 0} total</div>
        </div>
        <div className="bogo-row" style={{ gap: 10 }}>
          <div style={{ position: 'relative' }}>
            <input
              className="bogo-form-input"
              placeholder="Search rules…"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              style={{ width: 240, paddingLeft: 32 }}
            />
            <SearchIcon size={14} stroke="var(--muted-2)" style={{ position: 'absolute', left: 11, top: 11 }} />
          </div>
          <div className="bogo-tabs">
            <button className={`bogo-tabs__tab${statusFilter === '' ? ' bogo-tabs__tab--active' : ''}`} onClick={() => setStatusFilter('')}>All</button>
            <button className={`bogo-tabs__tab${statusFilter === 'active' ? ' bogo-tabs__tab--active' : ''}`} onClick={() => setStatusFilter('active')}>Live</button>
            <button className={`bogo-tabs__tab${statusFilter === 'inactive' ? ' bogo-tabs__tab--active' : ''}`} onClick={() => setStatusFilter('inactive')}>Inactive</button>
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
            <div style={{ marginTop: 14, fontSize: 15, fontWeight: 600 }}>No rules found</div>
            <div style={{ fontSize: 13, color: 'var(--muted)', marginTop: 4 }}>
              {search ? 'Try a different search term.' : 'Create your first BOGO rule to get started.'}
            </div>
            {!search && (
              <button
                className="bogo-button bogo-button--primary bogo-button--sm"
                style={{ marginTop: 16 }}
                onClick={() => { window.location.href = 'admin.php?page=buy-one-get-one&tab=rules&action=create'; }}
              >
                <PlusIcon size={13} /> Create rule
              </button>
            )}
          </div>
        )}
      </div>

      {rules && (page > 1 || rules.length === PER_PAGE) && (
        <div className="bogo-row" style={{ justifyContent: 'space-between', marginTop: 18, color: 'var(--muted)', fontSize: 12 }}>
          <span>Showing {rules.length} rules</span>
          <div className="bogo-row" style={{ gap: 6 }}>
            <button className="bogo-button bogo-button--sm" onClick={() => setPage((p) => Math.max(1, p - 1))} disabled={page === 1}>‹ Prev</button>
            <button className="bogo-button bogo-button--sm" onClick={() => setPage((p) => p + 1)} disabled={rules.length < PER_PAGE}>Next ›</button>
          </div>
        </div>
      )}

      <ConfirmModal
        isOpen={deleteModal.isOpen}
        onClose={() => setDeleteModal({ isOpen: false, ruleId: null })}
        onConfirm={handleDelete}
        title="Delete Rule"
        message="Are you sure you want to delete this rule? This action cannot be undone."
        confirmText="Delete"
      />
    </AppShell>
  );
}

export default RulesPage;
