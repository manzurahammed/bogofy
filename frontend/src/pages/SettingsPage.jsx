import React, { useState, useEffect } from "react";
import { __ } from "@wordpress/i18n";
import { useSettings, useUpdateSettings } from "../hooks/useSettings";
import { useNotification } from "../hooks/useNotification";
import { PageLoader } from "../components/Shared/Loader";
import AppShell from "../components/Layout/AppShell";
import { CheckIcon } from "../components/Icons";
import StorefrontBadgesSection from "../components/Settings/StorefrontBadgesSection";
import CartMessagingSection from "../components/Settings/CartMessagingSection";
import SettingsPreview from "../components/Settings/SettingsPreview";

function SettingsPage() {
  const { data: settings, isLoading, error: fetchError } = useSettings();
  const updateSettings = useUpdateSettings();
  const { success, error } = useNotification();

  const [formData, setFormData] = useState({
    enabled: true,
    free_item_label: "FREE (Bogofy Deal)",
    cart_notice_text:
      "Congratulations! You got a free item with your purchase.",
    show_product_page_messages: true,
    show_shop_badges: true,
  });

  useEffect(() => {
    if (settings) setFormData(settings);
  }, [settings]);

  const handleToggle = (name) => {
    setFormData((prev) => ({ ...prev, [name]: !prev[name] }));
  };

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      await updateSettings.mutateAsync(formData);
      success(__("Settings saved successfully", "bogofy"));
    } catch (err) {
      error(err.message || __("Failed to save settings", "bogofy"));
    }
  };

  if (isLoading)
    return (
      <AppShell crumb={["Bogofy", __("Settings", "bogofy")]}>
        <PageLoader />
      </AppShell>
    );

  if (fetchError)
    return (
      <AppShell crumb={["Bogofy", __("Settings", "bogofy")]}>
        <div
          style={{
            textAlign: "center",
            padding: 48,
            color: "var(--muted)",
          }}
        >
          {__("Failed to load settings.", "bogofy")}
        </div>
      </AppShell>
    );

  return (
    <AppShell
      crumb={["Bogofy", __("Settings", "bogofy")]}
      actions={
        <>
          <button
            type="button"
            className="bogo-button bogo-button--sm bogo-button--ghost"
            onClick={() => setFormData(settings || formData)}
          >
            {__("Discard", "bogofy")}
          </button>
          <button
            type="button"
            className="bogo-button bogo-button--primary bogo-button--sm"
            onClick={handleSubmit}
            disabled={updateSettings.isPending}
          >
            <CheckIcon size={14} />
            {updateSettings.isPending
              ? __("Saving…", "bogofy")
              : __("Save changes", "bogofy")}
          </button>
        </>
      }
    >
      <div style={{ maxWidth: 760 }}>
        <form onSubmit={handleSubmit} className="bogo-col" style={{ gap: 22 }}>
          <div>
            <div className="bogo-page-header__title">
              {__("Settings", "bogofy")}
            </div>
            <div className="bogo-page-header__desc">
              {__(
                "How Bogofy offers are presented on product pages and in the cart.",
                "bogofy",
              )}
            </div>
          </div>

          <StorefrontBadgesSection
            formData={formData}
            onToggle={handleToggle}
            onChange={handleChange}
          />
          <CartMessagingSection formData={formData} onChange={handleChange} />
          <SettingsPreview formData={formData} />
        </form>
      </div>
    </AppShell>
  );
}

export default SettingsPage;
