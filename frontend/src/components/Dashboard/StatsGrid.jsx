import React, { useMemo } from "react";
import { __, sprintf } from "@wordpress/i18n";

const currencySymbol = window.bogoAdmin?.currencySymbol || "$";

function buildKpis(stats) {
  return [
    {
      label: __("Active rules", "bogofy"),
      value: stats?.active_rules ?? "—",
      /* translators: %d: total number of rules */
      delta: sprintf(__("%d total rules", "bogofy"), stats?.total_rules ?? 0),
    },
    {
      label: __("Bogofy orders", "bogofy"),
      value: stats?.bogo_orders ?? "—",
      delta: __("Orders with Bogofy applied", "bogofy"),
    },
    {
      label: __("Discount given", "bogofy"),
      value: stats?.total_discount
        ? `${currencySymbol}${Number(stats.total_discount).toLocaleString(undefined, { maximumFractionDigits: 0 })}`
        : "—",
      delta: __("Total discounts issued", "bogofy"),
    },
    {
      label: __("Total rules", "bogofy"),
      value: stats?.total_rules ?? "—",
      /* translators: %d: number of currently active rules */
      delta: sprintf(__("%d currently active", "bogofy"), stats?.active_rules ?? 0),
    },
  ];
}

function StatsGrid({ stats }) {
  const kpis = useMemo(() => buildKpis(stats), [stats]);

  return (
    <div className="bogo-kpi-grid">
      {kpis.map((kpi) => (
        <div key={kpi.label} className="bogo-kpi">
          <div className="bogo-kpi__label">{kpi.label}</div>
          <div className="bogo-kpi__value">{kpi.value}</div>
          <div className="bogo-kpi__delta">{kpi.delta}</div>
        </div>
      ))}
    </div>
  );
}

export default StatsGrid;
