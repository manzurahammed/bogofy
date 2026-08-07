import React from 'react';
import AppShell from '../components/Layout/AppShell';
import { BoxIcon, GiftIcon, PlusIcon } from '../components/Icons';

const ruleTypes = [
  {
    Icon: BoxIcon,
    title: 'Buy X, Get same free',
    desc: 'Customer buys N of a product and gets M of the same product free. The cheapest items become free.',
  },
  {
    Icon: GiftIcon,
    title: 'Buy X, Get Y free',
    desc: 'Customer buys a trigger product and receives a different gift product at no cost.',
  },
];

const faqs = [
  {
    q: 'Does this plugin work with variable products?',
    a: 'Yes. Both simple and variable products are supported as trigger and gift products.',
  },
  {
    q: 'How do I turn everything off temporarily?',
    a: 'Use the "Enable plugin" toggle in Settings to switch all BOGO functionality off without deleting rules.',
  },
];

function HelpPage() {
  return (
    <AppShell crumb={['Bogo', 'Help & docs']}>
      <div className="bogo-page-header">
        <div>
          <div className="bogo-page-header__title">Help &amp; docs</div>
          <div className="bogo-page-header__desc">How BOGO rules work and answers to common questions.</div>
        </div>
        <button
          className="bogo-button bogo-button--primary bogo-button--sm"
          onClick={() => { window.location.href = 'admin.php?page=buy-one-get-one&tab=rules&action=create'; }}
        >
          <PlusIcon size={14} /> Create a rule
        </button>
      </div>

      <div className="bogo-section-header" style={{ margin: '0 0 12px' }}>
        <div className="bogo-section-header__title">Rule types</div>
      </div>
      <div className="bogo-type-grid" style={{ gridTemplateColumns: 'repeat(2, 1fr)', marginTop: 0 }}>
        {ruleTypes.map((t) => (
          <div key={t.title} className="bogo-panel" style={{ display: 'flex', gap: 14, alignItems: 'flex-start' }}>
            <div className="bogo-pkg" style={{ width: 34, height: 34, borderRadius: 8, background: 'var(--accent-soft)', border: '1px solid var(--accent)', color: 'var(--accent-ink)', display: 'grid', placeItems: 'center', flexShrink: 0 }}>
              <t.Icon size={16} />
            </div>
            <div>
              <div style={{ fontWeight: 600, fontSize: 13.5 }}>{t.title}</div>
              <div style={{ color: 'var(--muted)', fontSize: 12.5, marginTop: 4, lineHeight: 1.5 }}>{t.desc}</div>
            </div>
          </div>
        ))}
      </div>

      <div className="bogo-section-header">
        <div className="bogo-section-header__title">Frequently asked questions</div>
      </div>
      <div className="bogo-settings-list">
        {faqs.map((f) => (
          <div key={f.q} className="bogo-settings-list__row" style={{ gridTemplateColumns: '1fr' }}>
            <div className="bogo-col">
              <div className="bogo-settings-list__name">{f.q}</div>
              <div className="bogo-settings-list__desc">{f.a}</div>
            </div>
          </div>
        ))}
      </div>
    </AppShell>
  );
}

export default HelpPage;
