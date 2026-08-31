import React from "react";
import { __ } from "@wordpress/i18n";

// Step 3: Options (discount, limits, scheduling, message)
function StepOptions({ formData, setFormData, errors }) {
  const set = (key, value) => setFormData((p) => ({ ...p, [key]: value }));
  const isDiscounted = formData.rule_type === "buy_x_get_x_discounted";

  return (
    <>
      <div className="bogo-wizard__card">
        <div className="bogo-wizard__title">{__("Options", "bogofy")}</div>
        <div className="bogo-wizard__subtitle">
          {__(
            "Fine-tune limits and discounting. Sensible defaults are already set.",
            "bogofy",
          )}
        </div>

        {isDiscounted && (
          <div style={{ marginTop: 16 }}>
            <label className="bogo-form-label">
              {__("Discount percentage", "bogofy")}
            </label>
            <div className="bogo-row" style={{ gap: 10 }}>
              <input
                type="number"
                className="bogo-form-input"
                style={{ width: 90, textAlign: "center" }}
                min="1"
                max="100"
                value={formData.discount_value}
                onChange={(e) => set("discount_value", Number(e.target.value))}
              />
              <span style={{ color: "var(--muted)", fontSize: 12 }}>
                {__("% off each discounted item", "bogofy")}
              </span>
            </div>
            {errors.discount_value && (
              <div className="bogo-form-error">{errors.discount_value}</div>
            )}
          </div>
        )}

        <div className="bogo-row" style={{ gap: 10, marginTop: 16 }}>
          <label className="bogo-form-label" style={{ margin: 0 }}>
            {__("Max free items per order", "bogofy")}
          </label>
          <input
            type="number"
            className="bogo-form-input"
            style={{ width: 90, textAlign: "center" }}
            min="1"
            placeholder={__("∞", "bogofy")}
            value={formData.max_free_qty}
            onChange={(e) => set("max_free_qty", e.target.value)}
          />
          <span style={{ color: "var(--muted)", fontSize: 12 }}>
            {__("blank = unlimited", "bogofy")}
          </span>
        </div>

        <div className="bogo-row" style={{ gap: 10, marginTop: 14 }}>
          <label className="bogo-form-label" style={{ margin: 0 }}>
            {__("Priority", "bogofy")}
          </label>
          <input
            type="number"
            className="bogo-form-input"
            style={{ width: 90, textAlign: "center" }}
            min="1"
            value={formData.priority}
            onChange={(e) => set("priority", Number(e.target.value))}
          />
          <span style={{ color: "var(--muted)", fontSize: 12 }}>
            {__("lower wins when rules overlap", "bogofy")}
          </span>
        </div>
      </div>

      <div className="bogo-wizard__card">
        <div className="bogo-wizard__title">
          {__("Schedule", "bogofy")}{" "}
          <span
            style={{ color: "var(--muted)", fontWeight: 400, fontSize: 13 }}
          >
            {__("· optional", "bogofy")}
          </span>
        </div>
        <div className="bogo-wizard__subtitle">
          {__("Leave blank to keep the rule always on.", "bogofy")}
        </div>
        <div
          style={{
            display: "grid",
            gridTemplateColumns: "1fr 1fr",
            gap: 14,
            marginTop: 14,
          }}
        >
          <div>
            <label className="bogo-form-label">
              {__("Start date", "bogofy")}
            </label>
            <input
              type="datetime-local"
              className="bogo-form-input"
              value={formData.start_date}
              onChange={(e) => set("start_date", e.target.value)}
            />
          </div>
          <div>
            <label className="bogo-form-label">{__("End date", "bogofy")}</label>
            <input
              type="datetime-local"
              className="bogo-form-input"
              value={formData.end_date}
              onChange={(e) => set("end_date", e.target.value)}
            />
          </div>
        </div>
      </div>

      <div className="bogo-wizard__card">
        <div className="bogo-wizard__title">
          {__("Display message", "bogofy")}{" "}
          <span
            style={{ color: "var(--muted)", fontWeight: 400, fontSize: 13 }}
          >
            {__("· optional", "bogofy")}
          </span>
        </div>
        <div className="bogo-wizard__subtitle">
          {__("Shown on product pages when the offer is available.", "bogofy")}
        </div>
        <textarea
          className="bogo-form-input"
          style={{ marginTop: 12, minHeight: 72, resize: "vertical" }}
          rows="3"
          placeholder={__("e.g., Buy {buy_qty}, Get {free_qty} FREE!", "bogofy")}
          value={formData.message_template}
          onChange={(e) => set("message_template", e.target.value)}
        />
        <div style={{ color: "var(--muted)", fontSize: 11.5, marginTop: 8 }}>
          {__(
            "Placeholders: {buy_qty}, {free_qty}, {free_product}, {discount}%, {start_date}, {end_date}",
            "bogofy",
          )}
        </div>
      </div>
    </>
  );
}

export default StepOptions;
