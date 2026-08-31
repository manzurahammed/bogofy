import React, { useMemo } from "react";
import { __, sprintf } from "@wordpress/i18n";

function buildKpis(stats) {
  return [
    /* translators: %d: total number of rules */
    {
      l: __("Active rules", "bogofy"),
      v: stats?.active_rules ?? "—",
      d: sprintf(__("%d total rules", "bogofy"), stats?.total_rules ?? 0),
    },
    {
      l: __("Bogofy orders", "bogofy"),
      v: stats?.bogo_orders ?? "—",
      d: __("Orders with Bogofy applied", "bogofy"),
    },
    {
      l: __("Discount given", "bogofy"),
      v: stats?.total_discount
        ? `$${Number(stats.total_discount).toFixed(0)}`
        : "—",
      d: __("Total discounts issued", "bogofy"),
    },
    /* translators: %d: number of currently active rules */
    {
      l: __("Total rules", "bogofy"),
      v: stats?.total_rules ?? "—",
      d: sprintf(__("%d currently active", "bogofy"), stats?.active_rules ?? 0),
    },
  ];
}

function StatsGrid({ stats }) {
  const kpis = useMemo(() => buildKpis(stats), [stats]);

  return (
    <div className="bogo-kpi-grid">
      {kpis.map((k, i) => (
        <div key={i} className="bogo-kpi">
          <div className="bogo-kpi__label">{k.l}</div>
          <div className="bogo-kpi__value">{k.v}</div>
          <div className="bogo-kpi__delta">{k.d}</div>
        </div>
      ))}
    </div>
  );
}

export default StatsGrid;
