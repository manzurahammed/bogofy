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

function CartMessagingSection({ formData, onToggle, onChange }) {
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
        <SettingsRow
          name={__("Show “add more” nudges in cart", "bogofy")}
          desc={__(
            "Tell shoppers how many more qualifying items unlock their next free or discounted item.",
            "bogofy",
          )}
        >
          <ToggleButton
            on={formData.show_cart_nudges}
            onClick={() => onToggle("show_cart_nudges")}
          />
        </SettingsRow>
        <SettingsRow
          name={__("Nudge text", "bogofy")}
          desc={__(
            "Leave empty for the default. Placeholders: {remaining}, {free_qty}, {discount}.",
            "bogofy",
          )}
        >
          <input
            className="bogo-form-input"
            name="cart_nudge_text"
            value={formData.cart_nudge_text || ""}
            onChange={onChange}
            placeholder={__(
              "Add {remaining} more items to get {free_qty} free!",
              "bogofy",
            )}
            style={{ width: 320 }}
          />
        </SettingsRow>
      </div>
    </div>
  );
}

export default CartMessagingSection;
