import React, { useState } from 'react';
import { useRules, useDeleteRule, useUpdateRuleStatus, useBulkAction } from '../hooks/useRules';
import { useNotification } from '../hooks/useNotification';
import { PageLoader } from '../components/Shared/Loader';
import { Toggle } from '../components/Shared/Toggle';
import { ConfirmModal } from '../components/Shared/Modal';
import clsx from 'clsx';

const ruleTypeLabels = {
  buy_x_get_x: 'Buy X Get X Free',
  buy_x_get_y: 'Buy X Get Y Free',
  buy_cat_get_free: 'Category BOGO',
  buy_x_get_x_discounted: 'Buy X Get X Discounted',
};

function RulesPage() {
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [selectedIds, setSelectedIds] = useState([]);
  const [deleteModal, setDeleteModal] = useState({ isOpen: false, ruleId: null });

  const { data: rules, isLoading, error } = useRules({
    page,
    per_page: 20,
    search,
    status: statusFilter,
  });

  const deleteRule = useDeleteRule();
  const updateStatus = useUpdateRuleStatus();
  const bulkAction = useBulkAction();
  const { success, error: showError } = useNotification();

  const handleStatusToggle = async (rule) => {
    const newStatus = rule.status === 'active' ? 'inactive' : 'active';
    try {
      await updateStatus.mutateAsync({ id: rule.id, status: newStatus });
      success(`Rule ${newStatus === 'active' ? 'activated' : 'deactivated'}`);
    } catch (err) {
      showError('Failed to update rule status');
    }
  };

  const handleDelete = async () => {
    try {
      await deleteRule.mutateAsync(deleteModal.ruleId);
      success('Rule deleted successfully');
      setDeleteModal({ isOpen: false, ruleId: null });
    } catch (err) {
      showError('Failed to delete rule');
    }
  };

  const handleBulkAction = async (action) => {
    if (selectedIds.length === 0) return;
    try {
      await bulkAction.mutateAsync({ action, ids: selectedIds });
      success(`${selectedIds.length} rules ${action === 'delete' ? 'deleted' : action + 'd'}`);
      setSelectedIds([]);
    } catch (err) {
      showError('Bulk action failed');
    }
  };

  const toggleSelectAll = () => {
    if (selectedIds.length === rules?.length) {
      setSelectedIds([]);
    } else {
      setSelectedIds(rules?.map((r) => r.id) || []);
    }
  };

  const toggleSelect = (id) => {
    setSelectedIds((prev) =>
      prev.includes(id) ? prev.filter((i) => i !== id) : [...prev, id]
    );
  };

  if (isLoading) {
    return <PageLoader />;
  }

  if (error) {
    return (
      <div className="bogo-text-center bogo-py-12">
        <p className="bogo-text-danger-500">Failed to load rules. Please try again.</p>
      </div>
    );
  }

  return (
    <div className="bogo-space-y-4">
      {/* Filters */}
      <div className="bogo-flex bogo-flex-wrap bogo-items-center bogo-gap-4">
        <div className="bogo-flex-1 bogo-min-w-64">
          <input
            type="text"
            placeholder="Search rules..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="bogo-input"
          />
        </div>
        <select
          value={statusFilter}
          onChange={(e) => setStatusFilter(e.target.value)}
          className="bogo-select bogo-w-40"
        >
          <option value="">All Status</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
      </div>

      {/* Bulk Actions */}
      {selectedIds.length > 0 && (
        <div className="bogo-flex bogo-items-center bogo-gap-4 bogo-p-4 bogo-bg-primary-50 bogo-rounded-lg">
          <span className="bogo-text-sm bogo-font-medium bogo-text-primary-700">
            {selectedIds.length} selected
          </span>
          <button
            onClick={() => handleBulkAction('activate')}
            className="bogo-text-sm bogo-text-primary-600 hover:bogo-text-primary-700"
          >
            Activate
          </button>
          <button
            onClick={() => handleBulkAction('deactivate')}
            className="bogo-text-sm bogo-text-primary-600 hover:bogo-text-primary-700"
          >
            Deactivate
          </button>
          <button
            onClick={() => handleBulkAction('delete')}
            className="bogo-text-sm bogo-text-danger-600 hover:bogo-text-danger-700"
          >
            Delete
          </button>
        </div>
      )}

      {/* Rules Table */}
      <div className="bogo-card bogo-overflow-hidden">
        {rules && rules.length > 0 ? (
          <table className="bogo-table">
            <thead>
              <tr>
                <th className="bogo-w-10">
                  <input
                    type="checkbox"
                    checked={selectedIds.length === rules.length}
                    onChange={toggleSelectAll}
                    className="bogo-rounded"
                  />
                </th>
                <th>Title</th>
                <th>Type</th>
                <th>Buy / Get</th>
                <th>Status</th>
                <th className="bogo-w-32">Actions</th>
              </tr>
            </thead>
            <tbody className="bogo-divide-y bogo-divide-gray-200">
              {rules.map((rule) => (
                <tr key={rule.id} className="hover:bogo-bg-gray-50">
                  <td>
                    <input
                      type="checkbox"
                      checked={selectedIds.includes(rule.id)}
                      onChange={() => toggleSelect(rule.id)}
                      className="bogo-rounded"
                    />
                  </td>
                  <td>
                    <div className="bogo-font-medium bogo-text-gray-900">
                      {rule.title}
                    </div>
                    {rule.start_date && (
                      <div className="bogo-text-xs bogo-text-gray-500">
                        {rule.start_date} - {rule.end_date || 'No end'}
                      </div>
                    )}
                  </td>
                  <td>
                    <span className="bogo-badge bogo-bg-gray-100 bogo-text-gray-700">
                      {ruleTypeLabels[rule.rule_type] || rule.rule_type}
                    </span>
                  </td>
                  <td>
                    <span className="bogo-text-sm">
                      Buy {rule.buy_quantity} / Get {rule.free_quantity}
                      {rule.discount_type === 'percentage' && ` @ ${rule.discount_value}% off`}
                      {rule.discount_type === 'free' && ' FREE'}
                    </span>
                  </td>
                  <td>
                    <Toggle
                      checked={rule.status === 'active'}
                      onChange={() => handleStatusToggle(rule)}
                    />
                  </td>
                  <td>
                    <div className="bogo-flex bogo-items-center bogo-gap-2">
                      <button
                        onClick={() => {
                          window.location.href = `admin.php?page=buy-one-get-one&tab=rules&action=edit&rule_id=${rule.id}`;
                        }}
                        className="bogo-text-primary-600 hover:bogo-text-primary-700"
                        title="Edit"
                      >
                        <svg className="bogo-w-5 bogo-h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                      </button>
                      <button
                        onClick={() => setDeleteModal({ isOpen: true, ruleId: rule.id })}
                        className="bogo-text-danger-600 hover:bogo-text-danger-700"
                        title="Delete"
                      >
                        <svg className="bogo-w-5 bogo-h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        ) : (
          <div className="bogo-text-center bogo-py-12">
            <svg className="bogo-mx-auto bogo-h-12 bogo-w-12 bogo-text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z" />
            </svg>
            <h3 className="bogo-mt-2 bogo-text-sm bogo-font-medium bogo-text-gray-900">
              No rules found
            </h3>
            <p className="bogo-mt-1 bogo-text-sm bogo-text-gray-500">
              Get started by creating a new BOGO rule.
            </p>
            <button
              onClick={() => {
                window.location.href = 'admin.php?page=buy-one-get-one&tab=rules&action=create';
              }}
              className="bogo-mt-4 bogo-btn bogo-btn-primary"
            >
              Create Rule
            </button>
          </div>
        )}
      </div>

      {/* Delete Confirmation Modal */}
      <ConfirmModal
        isOpen={deleteModal.isOpen}
        onClose={() => setDeleteModal({ isOpen: false, ruleId: null })}
        onConfirm={handleDelete}
        title="Delete Rule"
        message="Are you sure you want to delete this rule? This action cannot be undone."
        confirmText="Delete"
      />
    </div>
  );
}

export default RulesPage;
