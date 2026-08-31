import React from "react";
import { __, sprintf } from "@wordpress/i18n";
import { BoxIcon, GiftIcon, PowerIcon } from "../../Icons";

function ReviewBlock({ title, step, children }) {
  return (
    <div className="bogo-review-block">
      <div
        className="bogo-row"
        style={{ justifyContent: "space-between", marginBottom: 8 }}
      >
        <span
          style={{
            fontSize: 11.5,
            color: "var(--muted)",
            textTransform: "uppercase",
            letterSpacing: "0.04em",
            fontWeight: 500,
          }}
        >
          {title}
        </span>
        {/* translators: %s: step number */}
        <span style={{ fontSize: 11, color: "var(--muted)" }}>
          {sprintf(__("Step %s", "bogofy"), step)}
        </span>
      </div>
      {children}
    </div>
  );
}

// Step 4: Review
function StepReview({
  formData,
  selectedBuyProducts,
  selectedFreeProducts,
  selectedCategories = [],
  onLaunch,
  onDraft,
  isLoading,
}) {
  const isCategoryTrigger = formData.apply_to === "specific_categories";
  const isDiscounted = formData.rule_type === "buy_x_get_x_discounted";
  const giftLabel = isDiscounted
    ? /* translators: %d: discount percentage */
      sprintf(__("%d%% off", "bogofy"), formData.discount_value)
    : __("100% off (Free)", "bogofy");
  return (
    <div className="bogo-wizard__card">
      <div className="bogo-wizard__title">
        {__("Review your rule", "bogofy")}
      </div>
      <div className="bogo-wizard__subtitle">
        {__(
          "Everything looks good? Launch it live or save as draft.",
          "bogofy",
        )}
      </div>

      <div className="bogo-review-grid">
        <ReviewBlock title={__("Offer type", "bogofy")} step="1">
          <div className="bogo-flow">
            {/* translators: %d: quantity */}
            <span className="bogo-flow__node">
              <BoxIcon size={12} />{" "}
              {sprintf(__("Buy %d", "bogofy"), formData.buy_quantity)}
            </span>
            <span className="bogo-flow__arrow">→</span>
            {/* translators: %d: quantity */}
            <span className="bogo-flow__node bogo-flow__node--get">
              <GiftIcon size={12} />{" "}
              {sprintf(__("Get %d free", "bogofy"), formData.free_quantity)}
            </span>
          </div>
        </ReviewBlock>
        <ReviewBlock title={__("Name", "bogofy")} step="1">
          <div
            style={{
              fontWeight: 600,
              fontSize: 13,
            }}
          >
            {formData.title || __("(Unnamed rule)", "bogofy")}
          </div>
        </ReviewBlock>

        <ReviewBlock
          title={
            isCategoryTrigger
              ? __("Trigger categories", "bogofy")
              : __("Trigger products", "bogofy")
          }
          step="2"
        >
          <div style={{ display: "flex", gap: 6, flexWrap: "wrap" }}>
            {isCategoryTrigger && selectedCategories.length > 0 ? (
              selectedCategories.map((c) => (
                <span key={c.id} className="bogo-product-pill">
                  <span className="bogo-thumb" />
                  {c.name}
                </span>
              ))
            ) : selectedBuyProducts.length > 0 ? (
              selectedBuyProducts.map((p) => (
                <span key={p.id} className="bogo-product-pill">
                  <span className="bogo-thumb" />
                  {p.name}
                </span>
              ))
            ) : (
              <span
                style={{
                  color: "var(--muted)",
                  fontSize: 12,
                }}
              >
                {__("Any product", "bogofy")}
              </span>
            )}
          </div>
          {/* translators: %d: minimum quantity */}
          <div
            style={{
              color: "var(--muted)",
              fontSize: 11.5,
              marginTop: 8,
            }}
          >
            {sprintf(__("Min. qty: %d", "bogofy"), formData.buy_quantity)}
          </div>
        </ReviewBlock>
        <ReviewBlock title={__("Gift products", "bogofy")} step="2">
          <div style={{ display: "flex", gap: 6, flexWrap: "wrap" }}>
            {selectedFreeProducts.length > 0 ? (
              selectedFreeProducts.map((p) => (
                <span key={p.id} className="bogo-product-pill">
                  <span className="bogo-thumb" />
                  {p.name}
                </span>
              ))
            ) : (
              <span style={{ color: "var(--muted)", fontSize: 12 }}>—</span>
            )}
          </div>
          <div
            style={{
              color: "var(--accent-ink)",
              fontSize: 11.5,
              marginTop: 8,
              fontWeight: 600,
            }}
          >
            {giftLabel}
          </div>
        </ReviewBlock>

        <ReviewBlock title={__("Options", "bogofy")} step="3">
          <div style={{ color: "var(--muted)", fontSize: 12, lineHeight: 1.6 }}>
            {/* translators: %d: priority */}
            <div>{sprintf(__("Priority: %d", "bogofy"), formData.priority)}</div>
            <div>
              {formData.max_free_qty
                ? /* translators: %s: max free items */
                  sprintf(__("Max free: %s", "bogofy"), formData.max_free_qty)
                : __("Max free: unlimited", "bogofy")}
            </div>
            <div>
              {formData.start_date || formData.end_date
                ? sprintf(
                    /* translators: 1: start date, 2: end date */
                    __("Scheduled: %1$s → %2$s", "bogofy"),
                    formData.start_date || __("now", "bogofy"),
                    formData.end_date || __("∞", "bogofy"),
                  )
                : __("Always on", "bogofy")}
            </div>
          </div>
        </ReviewBlock>
      </div>

      <div
        className="bogo-row"
        style={{ marginTop: 22, gap: 10, justifyContent: "flex-end" }}
      >
        <button
          type="button"
          className="bogo-button"
          onClick={onDraft}
          disabled={isLoading}
        >
          {__("Save as draft", "bogofy")}
        </button>
        <button
          type="button"
          className="bogo-button bogo-button--primary"
          onClick={onLaunch}
          disabled={isLoading}
        >
          <PowerIcon size={14} />
          {isLoading ? __("Saving…", "bogofy") : __("Launch rule", "bogofy")}
        </button>
      </div>
    </div>
  );
}

export default StepReview;
