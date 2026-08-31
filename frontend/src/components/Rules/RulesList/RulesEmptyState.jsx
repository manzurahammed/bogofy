import React from "react";
import { __ } from "@wordpress/i18n";
import { PlusIcon, GiftIcon } from "../../Icons";

function RulesEmptyState({ search, onCreate }) {
  return (
    <div
      className="bogo-panel"
      style={{ textAlign: "center", padding: "48px 20px" }}
    >
      <GiftIcon size={40} stroke="var(--muted-2)" />
      <div
        style={{
          marginTop: 14,
          fontSize: 15,
          fontWeight: 600,
        }}
      >
        {__("No rules found", "bogofy")}
      </div>
      <div style={{ fontSize: 13, color: "var(--muted)", marginTop: 4 }}>
        {search
          ? __("Try a different search term.", "bogofy")
          : __("Create your first Bogofy rule to get started.", "bogofy")}
      </div>
      {!search && (
        <button
          className="bogo-button bogo-button--primary bogo-button--sm"
          style={{ marginTop: 16 }}
          onClick={onCreate}
        >
          <PlusIcon size={13} /> {__("Create rule", "bogofy")}
        </button>
      )}
    </div>
  );
}

export default RulesEmptyState;
