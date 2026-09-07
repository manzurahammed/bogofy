import React, { useCallback } from "react";
import { __ } from "@wordpress/i18n";
import { useStats } from "@/hooks/useSettings";
import { useRules } from "@/hooks/useRules";
import { PageLoader } from "../components/Shared/Loader";
import AppShell from "../components/Layout/AppShell";
import StatsGrid from "../components/Dashboard/StatsGrid";
import QuickActions from "../components/Dashboard/QuickActions";
import LiveOffers from "../components/Dashboard/LiveOffers";
import { PlusIcon } from "../components/Icons";
import { goToAdmin } from "../utils/navigation";

function Dashboard() {
  const { data: stats, isLoading: statsLoading } = useStats();
  const { data: rules, isLoading: rulesLoading } = useRules({
    page: 1,
    per_page: 3,
    status: "active",
  });

  const handleEdit = useCallback(
    (id) => goToAdmin(`tab=rules&action=edit&rule_id=${id}`),
    [],
  );
  const handleCreate = useCallback(
    () => goToAdmin("tab=rules&action=create"),
    [],
  );
  const handleViewAll = useCallback(() => goToAdmin("tab=rules"), []);

  if (statsLoading || rulesLoading)
    return (
      <AppShell crumb={["Bogofy", __("Dashboard", "bogofy")]}>
        <PageLoader />
      </AppShell>
    );

  return (
    <AppShell
      crumb={["Bogofy", __("Dashboard", "bogofy")]}
      actions={
        <button
          className="bogo-button bogo-button--primary bogo-button--sm"
          onClick={handleCreate}
        >
          <PlusIcon size={14} /> {__("New rule", "bogofy")}
        </button>
      }
    >
      <div className="bogo-page-header">
        <div>
          <div className="bogo-page-header__title">
            {__("Dashboard", "bogofy")}
          </div>
          <div className="bogo-page-header__desc">
            {__("Overview of your Bogofy offers and performance.", "bogofy")}
          </div>
        </div>
      </div>

      <StatsGrid stats={stats} />

      <QuickActions onNavigate={goToAdmin} />

      <LiveOffers
        rules={rules}
        onEdit={handleEdit}
        onViewAll={handleViewAll}
        onCreate={handleCreate}
      />
    </AppShell>
  );
}

export default Dashboard;
