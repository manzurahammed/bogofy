import React from "react";
import { __ } from "@wordpress/i18n";
import { SearchIcon } from "../../Icons";

const statusTabs = [
  { value: "", label: __("All", "bogofy") },
  { value: "active", label: __("Live", "bogofy") },
  { value: "inactive", label: __("Inactive", "bogofy") },
];

function RulesToolbar({ search, onSearch, statusFilter, onStatusChange }) {
  return (
    <div className="bogo-row" style={{ gap: 10 }}>
      <div style={{ position: "relative" }}>
        <input
          className="bogo-form-input"
          placeholder={__("Search rules…", "bogofy")}
          value={search}
          onChange={(e) => onSearch(e.target.value)}
          style={{ width: 240, paddingLeft: 32 }}
        />
        <SearchIcon
          size={14}
          stroke="var(--muted-2)"
          style={{ position: "absolute", left: 11, top: 11 }}
        />
      </div>
      <div className="bogo-tabs">
        {statusTabs.map((tab) => (
          <button
            key={tab.value}
            className={`bogo-tabs__tab${statusFilter === tab.value ? " bogo-tabs__tab--active" : ""}`}
            onClick={() => onStatusChange(tab.value)}
          >
            {tab.label}
          </button>
        ))}
      </div>
    </div>
  );
}

export default RulesToolbar;
