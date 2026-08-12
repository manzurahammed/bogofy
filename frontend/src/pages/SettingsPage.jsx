import React, { useState, useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import { useSettings, useUpdateSettings } from '../hooks/useSettings';
import { useNotification } from '../hooks/useNotification';
import { PageLoader } from '../components/Shared/Loader';
import AppShell from '../components/Layout/AppShell';
import { CheckIcon, GiftIcon } from '../components/Icons';

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
      success(__('Settings saved successfully', 'buy-one-get-one'));
    } catch (err) {
      error(err.message || __('Failed to save settings', 'buy-one-get-one'));
    }
  };

  if (isLoading) return (
    <AppShell crumb={['Bogo', __('Settings', 'buy-one-get-one')]}>
      <PageLoader />
    </AppShell>
  );

  if (fetchError) return (
    <AppShell crumb={['Bogo', __('Settings', 'buy-one-get-one')]}>
      <div style={{ textAlign: 'center', padding: 48, color: 'var(--muted)' }}>{__('Failed to load settings.', 'buy-one-get-one')}</div>
    </AppShell>
  );

  return (
    <AppShell
      crumb={['Bogo', __('Settings', 'buy-one-get-one')]}
      actions={
        <>
          <button type="button" className="bogo-button bogo-button--sm bogo-button--ghost" onClick={() => setFormData(settings || formData)}>
            {__('Discard', 'buy-one-get-one')}
          </button>
          <button
            type="button"
            className="bogo-button bogo-button--primary bogo-button--sm"
            onClick={handleSubmit}
            disabled={updateSettings.isPending}
          >
            <CheckIcon size={14} />
            {updateSettings.isPending ? __('Saving…', 'buy-one-get-one') : __('Save changes', 'buy-one-get-one')}
          </button>
        </>
      }
    >
      <div style={{ maxWidth: 760 }}>
        <form onSubmit={handleSubmit} className="bogo-col" style={{ gap: 22 }}>
          <div>
            <div className="bogo-page-header__title">{__('Settings', 'buy-one-get-one')}</div>
            <div className="bogo-page-header__desc">{__('How BOGO offers are presented on product pages and in the cart.', 'buy-one-get-one')}</div>
          </div>

          {/* Storefront badges */}
          <div>
            <div className="bogo-section-header" style={{ margin: '0 0 12px' }}>
              <div className="bogo-section-header__title">{__('Storefront badges', 'buy-one-get-one')}</div>
              <div className="bogo-section-header__meta">{__('Visible to shoppers', 'buy-one-get-one')}</div>
            </div>
            <div className="bogo-settings-list">
              <div className="bogo-settings-list__row">
                <div className="bogo-col">
                  <div className="bogo-settings-list__name">{__('Enable plugin', 'buy-one-get-one')}</div>
                  <div className="bogo-settings-list__desc">{__('Turn all BOGO functionality on or off globally.', 'buy-one-get-one')}</div>
                </div>
                <button
                  type="button"
                  className={`bogo-toggle${formData.enabled ? ' bogo-toggle--on' : ''}`}
                  onClick={() => handleToggle('enabled')}
                />
              </div>
              <div className="bogo-settings-list__row">
                <div className="bogo-col">
                  <div className="bogo-settings-list__name">{__('Show “BOGO” badge on product cards', 'buy-one-get-one')}</div>
                  <div className="bogo-settings-list__desc">{__('Highlights eligible products in shop & category pages.', 'buy-one-get-one')}</div>
                </div>
                <button
                  type="button"
                  className={`bogo-toggle${formData.show_shop_badges ? ' bogo-toggle--on' : ''}`}
                  onClick={() => handleToggle('show_shop_badges')}
                />
              </div>
              <div className="bogo-settings-list__row">
                <div className="bogo-col">
                  <div className="bogo-settings-list__name">{__('Show product page messages', 'buy-one-get-one')}</div>
                  <div className="bogo-settings-list__desc">{__('Display BOGO offer messages on individual product pages.', 'buy-one-get-one')}</div>
                </div>
                <button
                  type="button"
                  className={`bogo-toggle${formData.show_product_page_messages ? ' bogo-toggle--on' : ''}`}
                  onClick={() => handleToggle('show_product_page_messages')}
                />
              </div>
              <div className="bogo-settings-list__row">
                <div className="bogo-col">
                  <div className="bogo-settings-list__name">{__('Free item label', 'buy-one-get-one')}</div>
                  <div className="bogo-settings-list__desc">{__('Label shown next to free items in cart.', 'buy-one-get-one')}</div>
                </div>
                <input
                  className="bogo-form-input"
                  name="free_item_label"
                  value={formData.free_item_label}
                  onChange={handleChange}
                  style={{ width: 260 }}
                />
              </div>
            </div>
          </div>

          {/* Cart messaging */}
          <div>
            <div className="bogo-section-header" style={{ margin: '0 0 12px' }}>
              <div className="bogo-section-header__title">{__('Cart messaging', 'buy-one-get-one')}</div>
              <div className="bogo-section-header__meta">{__('Encourage shoppers to qualify', 'buy-one-get-one')}</div>
            </div>
            <div className="bogo-settings-list">
              <div className="bogo-settings-list__row">
                <div className="bogo-col">
                  <div className="bogo-settings-list__name">{__('Cart notice text', 'buy-one-get-one')}</div>
                  <div className="bogo-settings-list__desc">{__('Notice shown when a BOGO deal is applied at checkout.', 'buy-one-get-one')}</div>
                </div>
                <input
                  className="bogo-form-input"
                  name="cart_notice_text"
                  value={formData.cart_notice_text}
                  onChange={handleChange}
                  style={{ width: 320 }}
                />
              </div>
            </div>
          </div>

          {/* Preview */}
          <div>
            <div className="bogo-section-header" style={{ margin: '0 0 12px' }}>
              <div className="bogo-section-header__title">{__('Preview', 'buy-one-get-one')}</div>
              <div className="bogo-section-header__meta">{__('How shoppers will see it', 'buy-one-get-one')}</div>
            </div>
            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 14 }}>
              <div className="bogo-panel" style={{ padding: 18 }}>
                <div style={{ fontSize: 11.5, color: 'var(--muted)', marginBottom: 10 }}>{__('Product card badge', 'buy-one-get-one')}</div>
                <div style={{ borderRadius: 10, overflow: 'hidden', border: '1px solid var(--line)' }}>
                  <div style={{ height: 100, background: 'linear-gradient(135deg, #f0d3c8, #e0a78c)', position: 'relative' }}>
                    <span style={{
                      position: 'absolute', top: 10, left: 10,
                      background: 'var(--accent)', color: '#003a23',
                      fontSize: 10.5, fontWeight: 700, padding: '4px 10px',
                      borderRadius: 999, letterSpacing: '0.04em',
                    }}>
                      {formData.free_item_label || __('FREE (BOGO Deal)', 'buy-one-get-one')}
                    </span>
                  </div>
                  <div style={{ padding: 12 }}>
                    <div style={{ fontSize: 13, fontWeight: 500 }}>{__('Example Product', 'buy-one-get-one')}</div>
                    <div className="bogo-mono" style={{ fontSize: 12, color: 'var(--muted)', marginTop: 3 }}>$48.00</div>
                  </div>
                </div>
              </div>
              <div className="bogo-panel" style={{ padding: 18 }}>
                <div style={{ fontSize: 11.5, color: 'var(--muted)', marginBottom: 10 }}>{__('Cart notice', 'buy-one-get-one')}</div>
                <div style={{ background: 'var(--accent-soft)', padding: 12, borderRadius: 10, display: 'flex', gap: 10, alignItems: 'center' }}>
                  <GiftIcon size={18} stroke="var(--accent-ink)" />
                  <div style={{ fontSize: 13, fontWeight: 600, color: 'var(--accent-ink)', lineHeight: 1.4 }}>
                    {formData.cart_notice_text || __('Congratulations! You got a free item!', 'buy-one-get-one')}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </form>
      </div>
    </AppShell>
  );
}

export default SettingsPage;
