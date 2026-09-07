import React, { memo } from "react";
import { __, sprintf } from "@wordpress/i18n";
import { GiftIcon, EditIcon, TrashIcon } from "../../Icons";

const ruleTypeLabels = {
  buy_x_get_x: __("Buy X Get X Free", "bogofy"),
  buy_x_get_y: __("Buy X Get Y Free", "bogofy"),
  buy_cat_get_free: __("Category Bogofy", "bogofy"),
  buy_x_get_x_discounted: __("Buy X Get X Discounted", "bogofy"),
};

const currencySymbol = window.bogoAdmin?.currencySymbol || "$";

/**
 * Format an amount as a compact currency string (e.g. $1,124).
 *
 * @param {number} amount Revenue amount.
 * @returns {string}
 */
const formatRevenue = (amount) => {
  const value = Math.round(Number(amount) || 0);
  return `${currencySymbol}${value.toLocaleString()}`;
};

/**
 * Parse a MySQL / datetime-local string into a Date (or null when invalid).
 *
 * @param {string} value Raw date string.
 * @returns {Date|null}
 */
const parseDate = (value) => {
  if (!value) return null;
  const date = new Date(String(value).replace(" ", "T"));
  return Number.isNaN(date.getTime()) ? null : date;
};

/**
 * Format a date as "Apr 12" (or "Jun 1, 2026" when the year is shown).
 *
 * @param {Date} date Date to format.
 * @param {boolean} withYear Whether to include the year.
 * @returns {string}
 */
const formatDate = (date, withYear) =>
  date.toLocaleDateString("en-US", {
    month: "short",
    day: "numeric",
    ...(withYear ? { year: "numeric" } : {}),
  });

/**
 * Derive the status pill and the schedule meta line for a rule.
 *
 * @param {Object} rule Rule object.
 * @returns {{statusClass: string, statusLabel: string, meta: string|null, isActive: boolean}}
 */
const getRuleState = (rule) => {
  const now = new Date();
  const start = parseDate(rule.start_date);
  const end = parseDate(rule.end_date);
  const isActive = rule.status === "active";
  const isScheduled = isActive && start && start > now;
  const isEnded = isActive && end && end < now;
  const showYear = (date) => date.getFullYear() !== now.getFullYear();

  let statusClass = "bogo-status--inactive";
  let statusLabel = __("Inactive", "bogofy");
  if (isScheduled) {
    statusClass = "bogo-status--scheduled";
    statusLabel = __("Scheduled", "bogofy");
  } else if (isActive && !isEnded) {
    statusClass = "bogo-status--live";
    statusLabel = __("Live", "bogofy");
  }

  let meta = null;
  if (isScheduled) {
    // translators: %s: start date, e.g. "Jun 1, 2026"
    meta = sprintf(__("Starts %s", "bogofy"), formatDate(start, true));
  } else if (isEnded) {
    // translators: %s: end date, e.g. "Apr 30"
    meta = sprintf(__("Ended %s", "bogofy"), formatDate(end, showYear(end)));
  } else if (isActive) {
    const since = start || parseDate(rule.created_at);
    if (since) {
      // translators: %s: start date, e.g. "Apr 28"
      meta = sprintf(
        __("Active since %s", "bogofy"),
        formatDate(since, showYear(since)),
      );
    }
  }

  return { statusClass, statusLabel, meta, isActive };
};

/**
 * A right-aligned stat column (label above a bold value), matching the list design.
 *
 * @param {Object} props
 * @param {string} props.label Column label.
 * @param {React.ReactNode} props.value Column value.
 */
function StatColumn({ label, value }) {
  return (
    <div
      className="bogo-col bogo-align-right bogo-rule__stat"
      style={{ fontSize: 12, minWidth: 64 }}
    >
      <span style={{ color: "var(--muted)" }}>{label}</span>
      <span style={{ fontWeight: 600, marginTop: 2 }} className="bogo-mono">
        {value}
      </span>
    </div>
  );
}

function RuleRow({ rule, onEdit, onDelete, onToggle }) {
  const { statusClass, statusLabel, meta, isActive } = getRuleState(rule);

  return (
    <div className="bogo-rule">
      <div className="bogo-col">
        <div className="bogo-row" style={{ gap: 8 }}>
          <span className="bogo-rule__name">{rule.title}</span>
          <span
            style={{
              fontSize: 11,
              color: "var(--muted)",
              background: "var(--chip)",
              padding: "2px 8px",
              borderRadius: 999,
            }}
          >
            {ruleTypeLabels[rule.rule_type] || rule.rule_type}
          </span>
        </div>
        <div className="bogo-flow">
          <span className="bogo-flow__node">
            {rule.apply_to === "specific_products"
              ? sprintf(__("Buy %d items", "bogofy"), rule.buy_quantity)
              : sprintf(
                  __("Buy %d from category", "bogofy"),
                  rule.buy_quantity,
                )}
          </span>
          <span className="bogo-flow__arrow">→</span>
          <span className="bogo-flow__node bogo-flow__node--get">
            <GiftIcon size={12} />
            {rule.discount_type === "free"
              ? sprintf(__("Get %d free", "bogofy"), rule.free_quantity)
              : /* translators: 1: quantity, 2: discount percentage */
                sprintf(
                  __("Get %1$d at %2$s%% off", "bogofy"),
                  rule.free_quantity,
                  rule.discount_value,
                )}
          </span>
          {meta && (
            <>
              <span className="bogo-flow__sep">·</span>
              <span className="bogo-rule__meta-text">{meta}</span>
            </>
          )}
        </div>
      </div>
      <StatColumn
        label={__("Orders", "bogofy")}
        value={rule.orders_count > 0 ? rule.orders_count : "—"}
      />
      <StatColumn
        label={__("Revenue", "bogofy")}
        value={rule.orders_count > 0 ? formatRevenue(rule.revenue) : "—"}
      />
      <div className="bogo-row" style={{ gap: 8 }}>
        <span className={`bogo-status ${statusClass}`}>
          <span className="bogo-status__dot" />
          {statusLabel}
        </span>
        <button
          className={`bogo-toggle${isActive ? " bogo-toggle--on" : ""}`}
          onClick={() => onToggle(rule)}
          title={
            isActive ? __("Deactivate", "bogofy") : __("Activate", "bogofy")
          }
        />
        <button
          className="bogo-button bogo-button--sm bogo-button--ghost"
          onClick={() => onEdit(rule.id)}
          title={__("Edit", "bogofy")}
        >
          <EditIcon size={13} />
        </button>
        <button
          className="bogo-button bogo-button--sm bogo-button--ghost"
          onClick={() => onDelete(rule.id)}
          title={__("Delete", "bogofy")}
          style={{ color: "var(--danger-clr)" }}
        >
          <TrashIcon size={13} />
        </button>
      </div>
    </div>
  );
}

export default memo(RuleRow);
