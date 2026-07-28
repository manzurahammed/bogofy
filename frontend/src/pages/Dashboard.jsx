import React from 'react';
import { useStats } from '../hooks/useSettings';
import { useRules } from '../hooks/useRules';
import { PageLoader } from '../components/Shared/Loader';
import AppShell from '../components/Layout/AppShell';
import {
  PlusIcon, ArrowIcon, GiftIcon, TagIcon, SettingsIcon, HelpIcon, EditIcon,
} from '../components/Icons';

const goTo = (query) => { window.location.href = `admin.php?page=buy-one-get-one&${query}`; };

function RuleFlowRow({ rule }) {
  const isLive = rule.status === 'active';

  return (
    <div className="bogo-rule" style={{ gridTemplateColumns: '1fr auto' }}>
      <div className="bogo-col">
        <div className="bogo-rule__name">{rule.title}</div>
        <div className="bogo-flow">
          <span className="bogo-flow__node">
            Buy {rule.buy_quantity}
          </span>
          <span className="bogo-flow__arrow">→</span>
          <span className="bogo-flow__node bogo-flow__node--get">
            <GiftIcon size={12} />
            Get {rule.free_quantity}
            {rule.discount_type === 'free' ? ' free' : ` at ${rule.discount_value}% off`}
          </span>
        </div>
      </div>
      <div className="bogo-row" style={{ gap: 8 }}>
        <span className={`bogo-status ${isLive ? 'bogo-status--live' : 'bogo-status--inactive'}`}>
          <span className="bogo-status__dot" />
          {isLive ? 'Live' : 'Inactive'}
        </span>
        <button
          className="bogo-button bogo-button--sm bogo-button--ghost"
          title="Edit rule"
          onClick={() => goTo(`tab=rules&action=edit&rule_id=${rule.id}`)}
        >
          <EditIcon size={13} />
        </button>
      </div>
    </div>
  );
}

const quickActions = [
  { label: 'Create a new rule', desc: 'Set up a BOGO offer in 4 steps', Icon: PlusIcon, query: 'tab=rules&action=create' },
  { label: 'Manage rules', desc: 'Edit, activate, or delete existing offers', Icon: TagIcon, query: 'tab=rules' },
  { label: 'Display settings', desc: 'Badges, labels, and cart messages', Icon: SettingsIcon, query: 'tab=settings' },
  { label: 'Help & docs', desc: 'Rule types and common questions', Icon: HelpIcon, query: 'tab=help' },
];

function Dashboard() {
  const { data: stats, isLoading: statsLoading } = useStats();
  const { data: rules, isLoading: rulesLoading } = useRules({ page: 1, per_page: 3, status: 'active' });

  const isLoading = statsLoading || rulesLoading;

  if (isLoading) return (
    <AppShell crumb={['Bogo', 'Dashboard']}>
      <PageLoader />
    </AppShell>
  );

  const kpis = [
    { l: 'Active rules', v: stats?.active_rules ?? '—', d: `${stats?.total_rules ?? 0} total rules` },
    { l: 'BOGO orders', v: stats?.bogo_orders ?? '—', d: 'Orders with BOGO applied' },
    { l: 'Discount given', v: stats?.total_discount ? `$${Number(stats.total_discount).toFixed(0)}` : '—', d: 'Total discounts issued' },
    { l: 'Total rules', v: stats?.total_rules ?? '—', d: `${stats?.active_rules ?? 0} currently active` },
  ];

  return (
    <AppShell
      crumb={['Bogo', 'Dashboard']}
      actions={
        <button
          className="bogo-button bogo-button--primary bogo-button--sm"
          onClick={() => goTo('tab=rules&action=create')}
        >
          <PlusIcon size={14} /> New rule
        </button>
      }
    >
      <div className="bogo-page-header">
        <div>
          <div className="bogo-page-header__title">Dashboard</div>
          <div className="bogo-page-header__desc">Overview of your BOGO offers and performance.</div>
        </div>
      </div>

      {/* KPIs */}
      <div className="bogo-kpi-grid">
        {kpis.map((k, i) => (
          <div key={i} className="bogo-kpi">
            <div className="bogo-kpi__label">{k.l}</div>
            <div className="bogo-kpi__value">{k.v}</div>
            <div className="bogo-kpi__delta">{k.d}</div>
          </div>
        ))}
      </div>

      {/* Quick actions */}
      <div className="bogo-chart-card" style={{ marginTop: 18 }}>
        <div className="bogo-section-header__title" style={{ marginBottom: 14 }}>Quick actions</div>
        <div className="bogo-col" style={{ gap: 8 }}>
          {quickActions.map((a) => (
            <button
              key={a.label}
              className="bogo-rule"
              style={{ gridTemplateColumns: 'auto 1fr auto', cursor: 'pointer', padding: '12px 16px', textAlign: 'left', fontFamily: 'inherit' }}
              onClick={() => goTo(a.query)}
            >
              <span className="bogo-pkg" style={{ width: 32, height: 32, borderRadius: 8, background: 'var(--chip)', display: 'grid', placeItems: 'center', color: 'var(--ink-2)' }}>
                <a.Icon size={15} />
              </span>
              <span className="bogo-col">
                <span style={{ fontWeight: 500, fontSize: 13 }}>{a.label}</span>
                <span style={{ fontSize: 12, color: 'var(--muted)', marginTop: 2 }}>{a.desc}</span>
              </span>
              <ArrowIcon size={14} stroke="var(--muted-2)" />
            </button>
          ))}
        </div>
      </div>

      {/* Live offers */}
      <div className="bogo-section-header">
        <div className="bogo-col">
          <div className="bogo-section-header__title">Live offers</div>
          <div className="bogo-section-header__meta">Currently active rules</div>
        </div>
        <button
          className="bogo-button bogo-button--sm"
          onClick={() => goTo('tab=rules')}
        >
          View all rules <ArrowIcon size={13} />
        </button>
      </div>

      <div className="bogo-rule-list">
        {rules && rules.length > 0 ? (
          rules.map((rule) => <RuleFlowRow key={rule.id} rule={rule} />)
        ) : (
          <div className="bogo-panel" style={{ textAlign: 'center', padding: '32px 20px', color: 'var(--muted)' }}>
            <GiftIcon size={32} stroke="var(--muted-2)" />
            <div style={{ marginTop: 12, fontSize: 14, fontWeight: 500, color: 'var(--ink)' }}>No active rules yet</div>
            <div style={{ fontSize: 13, marginTop: 4 }}>Create your first BOGO rule to get started.</div>
            <button
              className="bogo-button bogo-button--primary bogo-button--sm"
              style={{ marginTop: 14 }}
              onClick={() => goTo('tab=rules&action=create')}
            >
              <PlusIcon size={13} /> Create rule
            </button>
          </div>
        )}
      </div>
    </AppShell>
  );
}

export default Dashboard;
