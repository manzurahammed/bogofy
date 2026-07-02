import React from 'react';
import { useStats } from '../hooks/useSettings';
import { PageLoader } from '../components/Shared/Loader';
import AppShell from '../components/Layout/AppShell';
import { ChartIcon, InfoIcon } from '../components/Icons';

function AnalyticsPage() {
  const { data: stats, isLoading } = useStats();

  if (isLoading) {
    return (
      <AppShell crumb={['Bogo', 'Analytics']}>
        <PageLoader />
      </AppShell>
    );
  }

  const kpis = [
    { l: 'Active rules', v: stats?.active_rules ?? 0, d: 'Rules currently live' },
    { l: 'Total rules', v: stats?.total_rules ?? 0, d: 'All rules ever created' },
    { l: 'Orders with BOGO', v: stats?.bogo_orders ?? 0, d: 'Orders with a BOGO discount' },
    { l: 'Discount given', v: `$${Number(stats?.total_discount ?? 0).toFixed(0)}`, d: 'Total discounts issued' },
  ];

  return (
    <AppShell crumb={['Bogo', 'Analytics']}>
      <div className="bogo-page-header">
        <div>
          <div className="bogo-page-header__title">Analytics</div>
          <div className="bogo-page-header__desc">How your BOGO offers are performing across the store.</div>
        </div>
      </div>

      <div className="bogo-kpi-grid">
        {kpis.map((k) => (
          <div key={k.l} className="bogo-kpi">
            <div className="bogo-kpi__label">{k.l}</div>
            <div className="bogo-kpi__value">{k.v}</div>
            <div className="bogo-kpi__delta">{k.d}</div>
          </div>
        ))}
      </div>

      <div className="bogo-chart-card" style={{ marginTop: 18 }}>
        <div className="bogo-section-header__title" style={{ marginBottom: 4 }}>Revenue &amp; orders over time</div>
        <div className="bogo-section-header__meta" style={{ marginBottom: 18 }}>Per-rule charts will appear here once enough order data is collected</div>
        <div style={{
          height: 160, borderRadius: 10, background: 'var(--chip)',
          display: 'flex', alignItems: 'center', justifyContent: 'center',
          color: 'var(--muted)', fontSize: 13, flexDirection: 'column', gap: 8,
        }}>
          <ChartIcon size={28} stroke="var(--muted-2)" />
          <div>No chart data yet</div>
        </div>
      </div>

      <div className="bogo-panel" style={{ marginTop: 18, display: 'flex', gap: 14, alignItems: 'flex-start', padding: '18px 20px' }}>
        <InfoIcon size={20} stroke="var(--info)" />
        <div className="bogo-col">
          <div style={{ fontWeight: 600, fontSize: 14 }}>Advanced analytics coming soon</div>
          <div style={{ color: 'var(--muted)', fontSize: 13, marginTop: 3 }}>
            Detailed per-rule analytics, conversion funnels, and revenue attribution are in development.
            For now, use WooCommerce order reports to track BOGO performance.
          </div>
        </div>
      </div>
    </AppShell>
  );
}

export default AnalyticsPage;
