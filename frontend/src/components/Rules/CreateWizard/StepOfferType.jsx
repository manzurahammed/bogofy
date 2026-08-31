import React from "react";
import { __ } from "@wordpress/i18n";
import { BoxIcon, GiftIcon } from "../../Icons";
import { ruleTypes } from "./constants";

// Step 1: Offer type
function StepOfferType({ formData, setFormData }) {
  return (
    <div className="bogo-wizard__card">
      <div className="bogo-wizard__title">
        {__("Choose offer type", "bogofy")}
      </div>
      <div className="bogo-wizard__subtitle">
        {__(
          "The structure of your rule — determines what other steps look like.",
          "bogofy",
        )}
      </div>
      <div className="bogo-type-grid">
        {ruleTypes.map((t) => (
          <div
            key={t.value}
            className={`bogo-type-card${formData.rule_type === t.value ? " bogo-type-card--selected" : ""}`}
            onClick={() => setFormData((p) => ({ ...p, rule_type: t.value }))}
          >
            <div className="bogo-type-card__visual">
              <div className="bogo-pkg">
                <BoxIcon size={14} />
              </div>
              <span style={{ color: "var(--muted-2)", fontSize: 12 }}>→</span>
              <div
                className={`bogo-pkg${formData.rule_type === t.value ? " bogo-pkg--get" : ""}`}
              >
                {t.vis === "gift" && <GiftIcon size={14} />}
                {t.vis === "same" && <BoxIcon size={14} />}
              </div>
            </div>
            <div>
              <div className="bogo-type-card__title">{t.label}</div>
              <div className="bogo-type-card__desc">{t.desc}</div>
            </div>
          </div>
        ))}
      </div>

      <div style={{ marginTop: 22 }}>
        <label className="bogo-form-label">{__("Rule name", "bogofy")}</label>
        <input
          className="bogo-form-input"
          placeholder={__("e.g., Summer Swim — Buy 2 Get 1", "bogofy")}
          value={formData.title}
          onChange={(e) =>
            setFormData((p) => ({ ...p, title: e.target.value }))
          }
        />
      </div>
    </div>
  );
}

export default StepOfferType;
