import React, { useState, useEffect } from 'react';
import { useSettings, useUpdateSettings } from '../hooks/useSettings';
import { useNotification } from '../hooks/useNotification';
import { PageLoader } from '../components/Shared/Loader';
import { Toggle } from '../components/Shared/Toggle';

function SettingsPage() {
  const { data: settings, isLoading, error: fetchError } = useSettings();
  const updateSettings = useUpdateSettings();
  const { success, error } = useNotification();

  const [formData, setFormData] = useState({
    enabled: true,
    free_item_label: 'FREE (BOGO Deal)',
    cart_notice_text: 'Congratulations! You got a free item with your purchase.',
    show_product_page_messages: true,
    show_shop_badges: true,
    stack_with_coupons: true,
  });

  useEffect(() => {
    if (settings) {
      setFormData(settings);
    }
  }, [settings]);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const handleToggle = (name) => {
    setFormData((prev) => ({ ...prev, [name]: !prev[name] }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      await updateSettings.mutateAsync(formData);
      success('Settings saved successfully');
    } catch (err) {
      error(err.message || 'Failed to save settings');
    }
  };

  if (isLoading) {
    return <PageLoader />;
  }

  if (fetchError) {
    return (
      <div className="bogo-text-center bogo-py-12">
        <p className="bogo-text-danger-500">Failed to load settings. Please try again.</p>
      </div>
    );
  }

  return (
    <div className="bogo-max-w-2xl">
      <form onSubmit={handleSubmit} className="bogo-space-y-6">
        {/* General Settings */}
        <div className="bogo-card bogo-p-6">
          <h3 className="bogo-text-lg bogo-font-semibold bogo-text-gray-900 bogo-mb-4">
            General Settings
          </h3>

          <div className="bogo-space-y-4">
            <div className="bogo-flex bogo-items-center bogo-justify-between">
              <div>
                <label className="bogo-font-medium bogo-text-gray-900">Enable Plugin</label>
                <p className="bogo-text-sm bogo-text-gray-500">
                  Turn BOGO functionality on or off
                </p>
              </div>
              <Toggle
                checked={formData.enabled}
                onChange={() => handleToggle('enabled')}
              />
            </div>

            <div className="bogo-flex bogo-items-center bogo-justify-between">
              <div>
                <label className="bogo-font-medium bogo-text-gray-900">Stack with Coupons</label>
                <p className="bogo-text-sm bogo-text-gray-500">
                  Allow BOGO discounts to stack with coupon codes
                </p>
              </div>
              <Toggle
                checked={formData.stack_with_coupons}
                onChange={() => handleToggle('stack_with_coupons')}
              />
            </div>
          </div>
        </div>

        {/* Display Settings */}
        <div className="bogo-card bogo-p-6">
          <h3 className="bogo-text-lg bogo-font-semibold bogo-text-gray-900 bogo-mb-4">
            Display Settings
          </h3>

          <div className="bogo-space-y-4">
            <div className="bogo-flex bogo-items-center bogo-justify-between">
              <div>
                <label className="bogo-font-medium bogo-text-gray-900">Show Product Page Messages</label>
                <p className="bogo-text-sm bogo-text-gray-500">
                  Display BOGO offer messages on product pages
                </p>
              </div>
              <Toggle
                checked={formData.show_product_page_messages}
                onChange={() => handleToggle('show_product_page_messages')}
              />
            </div>

            <div className="bogo-flex bogo-items-center bogo-justify-between">
              <div>
                <label className="bogo-font-medium bogo-text-gray-900">Show Shop Badges</label>
                <p className="bogo-text-sm bogo-text-gray-500">
                  Display BOGO badges on shop/archive pages
                </p>
              </div>
              <Toggle
                checked={formData.show_shop_badges}
                onChange={() => handleToggle('show_shop_badges')}
              />
            </div>
          </div>
        </div>

        {/* Text Labels */}
        <div className="bogo-card bogo-p-6">
          <h3 className="bogo-text-lg bogo-font-semibold bogo-text-gray-900 bogo-mb-4">
            Text Labels
          </h3>

          <div className="bogo-space-y-4">
            <div>
              <label className="bogo-label">Free Item Label</label>
              <input
                type="text"
                name="free_item_label"
                value={formData.free_item_label}
                onChange={handleChange}
                className="bogo-input"
                placeholder="FREE (BOGO Deal)"
              />
              <p className="bogo-text-xs bogo-text-gray-500 bogo-mt-1">
                Label shown next to free items in cart
              </p>
            </div>

            <div>
              <label className="bogo-label">Cart Notice Text</label>
              <textarea
                name="cart_notice_text"
                value={formData.cart_notice_text}
                onChange={handleChange}
                rows="2"
                className="bogo-input"
                placeholder="Congratulations! You got a free item with your purchase."
              />
              <p className="bogo-text-xs bogo-text-gray-500 bogo-mt-1">
                Notice shown when BOGO deal is applied
              </p>
            </div>
          </div>
        </div>

        {/* Submit */}
        <div className="bogo-flex bogo-justify-end">
          <button
            type="submit"
            disabled={updateSettings.isPending}
            className="bogo-btn bogo-btn-primary"
          >
            {updateSettings.isPending ? 'Saving...' : 'Save Settings'}
          </button>
        </div>
      </form>
    </div>
  );
}

export default SettingsPage;
