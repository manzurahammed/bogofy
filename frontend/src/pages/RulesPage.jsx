import React, { useCallback } from "react";
import { __, sprintf } from "@wordpress/i18n";
import { PageLoader } from "../components/Shared/Loader";
import { ConfirmModal } from "../components/Shared/Modal";
import AppShell from "../components/Layout/AppShell";
import RuleRow from "../components/Rules/RulesList/RuleRow";
import RulesToolbar from "../components/Rules/RulesList/RulesToolbar";
import RulesEmptyState from "../components/Rules/RulesList/RulesEmptyState";
import RulesPagination from "../components/Rules/RulesList/RulesPagination";
import { useRulesManager } from "../components/Rules/RulesList/useRulesManager";
import { PlusIcon } from "../components/Icons";
import { goToAdmin } from "../utils/navigation";

const CRUMB = ["Bogofy", __("Bogofy Rules", "bogofy")];

function RulesPage() {
  const {
    perPage,
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
  } = useRulesManager();

  const handleEdit = useCallback(
    (id) => goToAdmin(`tab=rules&action=edit&rule_id=${id}`),
    [],
  );
  const handleCreate = useCallback(
    () => goToAdmin("tab=rules&action=create"),
    [],
  );

  const actions = (
    <button
      className="bogo-button bogo-button--primary bogo-button--sm"
      onClick={handleCreate}
    >
      <PlusIcon size={14} /> {__("New rule", "bogofy")}
    </button>
  );

  if (isLoading)
    return (
      <AppShell crumb={CRUMB} actions={actions}>
        <PageLoader />
      </AppShell>
    );

  if (error)
    return (
      <AppShell crumb={CRUMB} actions={actions}>
        <div
          style={{
            textAlign: "center",
            padding: "48px 20px",
            color: "var(--muted)",
          }}
        >
          {__("Failed to load rules. Please try again.", "bogofy")}
        </div>
      </AppShell>
    );

  const hasRules = rules && rules.length > 0;

  return (
    <AppShell crumb={CRUMB} actions={actions}>
      <div className="bogo-page-header">
        <div>
          <div className="bogo-page-header__title">
            {__("Bogofy Rules", "bogofy")}
          </div>
          {/* translators: 1: active rule count, 2: total rule count */}
          <div className="bogo-page-header__desc">
            {sprintf(
              __("%1$d active · %2$d total", "bogofy"),
              liveCount,
              rules?.length ?? 0,
            )}
          </div>
        </div>
        <RulesToolbar
          search={search}
          onSearch={setSearch}
          statusFilter={statusFilter}
          onStatusChange={setStatusFilter}
        />
      </div>

      <div className="bogo-rule-list">
        {hasRules ? (
          rules.map((rule) => (
            <RuleRow
              key={rule.id}
              rule={rule}
              onEdit={handleEdit}
              onDelete={openDelete}
              onToggle={toggleStatus}
            />
          ))
        ) : (
          <RulesEmptyState search={search} onCreate={handleCreate} />
        )}
      </div>

      {rules && (page > 1 || rules.length === perPage) && (
        <RulesPagination
          page={page}
          count={rules.length}
          perPage={perPage}
          onPrev={() => setPage((p) => Math.max(1, p - 1))}
          onNext={() => setPage((p) => p + 1)}
        />
      )}

      <ConfirmModal
        isOpen={deleteModal.isOpen}
        onClose={closeDelete}
        onConfirm={confirmDelete}
        title={__("Delete Rule", "bogofy")}
        message={__(
          "Are you sure you want to delete this rule? This action cannot be undone.",
          "bogofy",
        )}
        confirmText={__("Delete", "bogofy")}
      />
    </AppShell>
  );
}

export default RulesPage;
