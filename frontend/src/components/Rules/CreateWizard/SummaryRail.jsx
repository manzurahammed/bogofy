import React from "react";
import { __, sprintf } from "@wordpress/i18n";
import { ruleTypes } from "./constants";

function SumRow({ l, v, done }) {
  return (
    <div className="bogo-summary-row">
      <span style={{ color: "var(--muted)" }}>{l}</span>
      <span
        style={{
          display: "inline-flex",
          alignItems: "center",
          gap: 6,
          color: done ? "var(--ink)" : "var(--muted-2)",
          fontWeight: done ? 500 : 400,
        }}
      >
        {done && (
          <span
            style={{
              width: 5,
              height: 5,
              borderRadius: 999,
              background: "var(--accent)",
            }}
          />
        )}
        {v}
      </span>
    </div>
  );
}

// Short label for the cart minimums set on the rule.
function conditionsSummary(conditions = {}) {
  const parts = [];
  if (Number(conditions.min_subtotal) > 0) {
    /* translators: %s: minimum cart subtotal */
    parts.push(sprintf(__("min %s", "bogofy"), conditions.min_subtotal));
  }
  if (Number(conditions.min_items) > 0) {
    /* translators: %s: minimum number of items */
    parts.push(sprintf(__("%s+ items", "bogofy"), conditions.min_items));
  }
  if (conditions.exclude_sale_items) {
    parts.push(__("no sale items", "bogofy"));
  }
  return parts.join(" · ");
}

function SummaryRail({
  formData,
  step,
  selectedBuyProducts,
  selectedFreeProducts,
}) {
  const conditions = conditionsSummary(formData.conditions);

  return (
    <div className="bogo-wizard__card">
      <div style={{ fontSize: 12.5, fontWeight: 600, marginBottom: 10 }}>
        {__("Summary", "bogofy")}
      </div>
      <div className="bogo-col" style={{ gap: 9 }}>
        <SumRow
          l={__("Type", "bogofy")}
          v={
            ruleTypes.find((r) => r.value === formData.rule_type)?.label || "—"
          }
          done={step >= 1}
        />
        <SumRow
          l={__("Name", "bogofy")}
          v={formData.title || __("Not set", "bogofy")}
          done={!!formData.title}
        />
        <SumRow
          l={__("Trigger", "bogofy")}
          /* translators: 1: number of products, 2: minimum quantity */
          v={
            selectedBuyProducts.length > 0
              ? sprintf(
                  __("%1$d products · min %2$d", "bogofy"),
                  selectedBuyProducts.length,
                  formData.buy_quantity,
                )
              : __("Not set", "bogofy")
          }
          done={selectedBuyProducts.length > 0}
        />
        <SumRow
          l={__("Gift", "bogofy")}
          /* translators: %d: number of products */
          v={
            selectedFreeProducts.length > 0
              ? sprintf(
                  __("%d products", "bogofy"),
                  selectedFreeProducts.length,
                )
              : __("Not set", "bogofy")
          }
          done={selectedFreeProducts.length > 0}
        />
        <SumRow
          l={__("Conditions", "bogofy")}
          v={conditions || __("None", "bogofy")}
          done={!!conditions}
        />
      </div>
    </div>
  );
}

export default SummaryRail;
