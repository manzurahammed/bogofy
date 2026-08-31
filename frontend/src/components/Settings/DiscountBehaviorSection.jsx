import React from "react";
import { __ } from "@wordpress/i18n";
import SettingsRow from "./SettingsRow";

function ToggleButton({ on, onClick }) {
  return (
    <button
      type="button"
      className={`bogo-toggle${on ? " bogo-toggle--on" : ""}`}
      onClick={onClick}
    />
  );
}

function DiscountBehaviorSection({ formData, onToggle }) {
  return (
    <div>
      <div className="bogo-section-header" style={{ margin: "0 0 12px" }}>
        <div className="bogo-section-header__title">
          {__("Discount behaviour", "bogofy")}
        </div>
        <div className="bogo-section-header__meta">
          {__("How offers combine", "bogofy")}
        </div>
      </div>
      <div className="bogo-settings-list">
        <SettingsRow
          name={__("Stack with coupon codes", "bogofy")}
          desc={__(
            "Allow BOGO discounts to stack with coupon codes.",
            "bogofy",
          )}
        >
          <ToggleButton
            on={formData.stack_with_coupons}
            onClick={() => onToggle("stack_with_coupons")}
          />
        </SettingsRow>
      </div>
    </div>
  );
}

export default DiscountBehaviorSection;
