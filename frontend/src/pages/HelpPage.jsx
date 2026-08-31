import React from "react";
import { __ } from "@wordpress/i18n";
import AppShell from "../components/Layout/AppShell";
import {
  BoxIcon,
  GiftIcon,
  PercentIcon,
  LayersIcon,
  PlusIcon,
} from "../components/Icons";

const ruleTypes = [
  {
    Icon: BoxIcon,
    title: __("Buy X, Get same free", "bogofy"),
    desc: __(
      "Customer buys N of a product and gets M of the same product free. The cheapest items become free.",
      "bogofy",
    ),
  },
  {
    Icon: GiftIcon,
    title: __("Buy X, Get Y free", "bogofy"),
    desc: __(
      "Customer buys a trigger product and receives a different gift product at no cost.",
      "bogofy",
    ),
  },
  {
    Icon: PercentIcon,
    title: __("Buy X, Get X at % off", "bogofy"),
    desc: __(
      "Instead of fully free, the extra item is discounted by a percentage you choose.",
      "bogofy",
    ),
  },
  {
    Icon: LayersIcon,
    title: __("Cross-category BOGO", "bogofy"),
    desc: __(
      "Customer buys from one category and receives a gift product from another.",
      "bogofy",
    ),
  },
];

const faqs = [
  {
    question: __("Does this plugin work with variable products?", "bogofy"),
    answer: __(
      "Yes. Both simple and variable products are supported as trigger and gift products.",
      "bogofy",
    ),
  },
  {
    question: __("Can I schedule deals?", "bogofy"),
    answer: __(
      "Every rule can have an optional start and end date. Leave them blank to keep the rule always on.",
      "bogofy",
    ),
  },
  {
    question: __("What happens when several rules match the same cart?", "bogofy"),
    answer: __(
      "Rules are evaluated by priority — a lower number wins. You set the priority when creating or editing a rule.",
      "bogofy",
    ),
  },
  {
    question: __("Do BOGO discounts stack with coupon codes?", "bogofy"),
    answer: __(
      'That is up to you. Toggle "Stack with coupon codes" in Settings to allow or prevent stacking.',
      "bogofy",
    ),
  },
  {
    question: __("How do I turn everything off temporarily?", "bogofy"),
    answer: __(
      'Use the "Enable plugin" toggle in Settings to switch all Bogofy functionality off without deleting rules.',
      "bogofy",
    ),
  },
];

function HelpPage() {
  return (
    <AppShell crumb={["Bogofy", "Help & docs"]}>
      <div className="bogo-page-header">
        <div>
          <div className="bogo-page-header__title">
            {__("Help & docs", "bogofy")}
          </div>
          <div className="bogo-page-header__desc">
            {__(
              "How Bogofy rules work and answers to common questions.",
              "bogofy",
            )}
          </div>
        </div>
        <button
          className="bogo-button bogo-button--primary bogo-button--sm"
          onClick={() => {
            window.location.href =
              "admin.php?page=bogofy&tab=rules&action=create";
          }}
        >
          <PlusIcon size={14} /> {__("Create a rule", "bogofy")}
        </button>
      </div>

      <div className="bogo-section-header" style={{ margin: "0 0 12px" }}>
        <div className="bogo-section-header__title">
          {__("Rule types", "bogofy")}
        </div>
      </div>
      <div
        className="bogo-type-grid"
        style={{ gridTemplateColumns: "repeat(2, 1fr)", marginTop: 0 }}
      >
        {ruleTypes.map((t) => (
          <div
            key={t.title}
            className="bogo-panel"
            style={{ display: "flex", gap: 14, alignItems: "flex-start" }}
          >
            <div
              className="bogo-pkg"
              style={{
                width: 34,
                height: 34,
                borderRadius: 8,
                background: "var(--accent-soft)",
                border: "1px solid var(--accent)",
                color: "var(--accent-ink)",
                display: "grid",
                placeItems: "center",
                flexShrink: 0,
              }}
            >
              <t.Icon size={16} />
            </div>
            <div>
              <div style={{ fontWeight: 600, fontSize: 13.5 }}>{t.title}</div>
              <div
                style={{
                  color: "var(--muted)",
                  fontSize: 12.5,
                  marginTop: 4,
                  lineHeight: 1.5,
                }}
              >
                {t.desc}
              </div>
            </div>
          </div>
        ))}
      </div>

      <div className="bogo-section-header">
        <div className="bogo-section-header__title">
          {__("Frequently asked questions", "bogofy")}
        </div>
      </div>
      <div className="bogo-settings-list">
        {faqs.map((faq) => (
          <div
            key={faq.question}
            className="bogo-settings-list__row"
            style={{ gridTemplateColumns: "1fr" }}
          >
            <div className="bogo-col">
              <div className="bogo-settings-list__name">{faq.question}</div>
              <div className="bogo-settings-list__desc">{faq.answer}</div>
            </div>
          </div>
        ))}
      </div>
    </AppShell>
  );
}

export default HelpPage;
