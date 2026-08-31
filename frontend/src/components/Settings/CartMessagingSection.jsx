import React from "react";
import { __ } from "@wordpress/i18n";
import SettingsRow from "./SettingsRow";

function CartMessagingSection({ formData, onChange }) {
  return (
    <div>
      <div className="bogo-section-header" style={{ margin: "0 0 12px" }}>
        <div className="bogo-section-header__title">
          {__("Cart messaging", "bogofy")}
        </div>
        <div className="bogo-section-header__meta">
          {__("Encourage shoppers to qualify", "bogofy")}
        </div>
      </div>
      <div className="bogo-settings-list">
        <SettingsRow
          name={__("Cart notice text", "bogofy")}
          desc={__(
            "Notice shown when a Bogofy deal is applied at checkout.",
            "bogofy",
          )}
        >
          <input
            className="bogo-form-input"
            name="cart_notice_text"
            value={formData.cart_notice_text}
            onChange={onChange}
            style={{ width: 320 }}
          />
        </SettingsRow>
      </div>
    </div>
  );
}

export default CartMessagingSection;
