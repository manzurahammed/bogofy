import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { useStats } from '../hooks/useSettings';
import { useRules } from '../hooks/useRules';
import { PageLoader } from '../components/Shared/Loader';
import AppShell from '../components/Layout/AppShell';
import {
	PlusIcon, ArrowIcon, GiftIcon, TagIcon, SettingsIcon, HelpIcon, EditIcon,
} from '../components/Icons';

const goTo = ( query ) => {
	window.location.href = `admin.php?page=buy-one-get-one&${query}`;
};

function RuleFlowRow( { rule } ) {
	const isLive = rule.status === 'active';
	
	return (
		<div className="bogo-rule" style={{ gridTemplateColumns: '1fr auto' }}>
			<div className="bogo-col">
				<div className="bogo-rule__name">{rule.title}</div>
				<div className="bogo-flow">
          <span className="bogo-flow__node">
            {sprintf(__('Buy %d', 'buy-one-get-one'), rule.buy_quantity)}
          </span>
					<span className="bogo-flow__arrow">→</span>
					<span className="bogo-flow__node bogo-flow__node--get">
            <GiftIcon size={12}/>
            {rule.discount_type === 'free'
              ? sprintf(__('Get %d free', 'buy-one-get-one'), rule.free_quantity)
              /* translators: 1: quantity, 2: discount percentage */
              : sprintf(__('Get %1$d at %2$s%% off', 'buy-one-get-one'), rule.free_quantity, rule.discount_value)}
          </span>
				</div>
			</div>
			<div className="bogo-row" style={{ gap: 8 }}>
        <span className={`bogo-status ${isLive ? 'bogo-status--live' : 'bogo-status--inactive'}`}>
          <span className="bogo-status__dot"/>
	        {isLive ? __('Live', 'buy-one-get-one') : __('Inactive', 'buy-one-get-one')}
        </span>
				<button
					className="bogo-button bogo-button--sm bogo-button--ghost"
					title={__('Edit rule', 'buy-one-get-one')}
					onClick={() => goTo( `tab=rules&action=edit&rule_id=${rule.id}` )}
				>
					<EditIcon size={13}/>
				</button>
			</div>
		</div>
	);
}

const quickActions = [
	{
		label: __('Create a new rule', 'buy-one-get-one'),
		desc: __('Set up a BOGO offer in 3 steps', 'buy-one-get-one'),
		Icon: PlusIcon,
		query: 'tab=rules&action=create'
	},
	{ label: __('Manage rules', 'buy-one-get-one'), desc: __('Edit, activate, or delete existing offers', 'buy-one-get-one'), Icon: TagIcon, query: 'tab=rules' },
	{ label: __('Display settings', 'buy-one-get-one'), desc: __('Badges, labels, and cart messages', 'buy-one-get-one'), Icon: SettingsIcon, query: 'tab=settings' },
	{ label: __('Help & docs', 'buy-one-get-one'), desc: __('Rule types and common questions', 'buy-one-get-one'), Icon: HelpIcon, query: 'tab=help' },
];

function Dashboard() {
	const { data: stats, isLoading: statsLoading } = useStats();
	const { data: rules, isLoading: rulesLoading } = useRules( { page: 1, per_page: 3, status: 'active' } );
	
	const isLoading = statsLoading || rulesLoading;
	
	if ( isLoading ) return (
		<AppShell crumb={['Bogo', __('Dashboard', 'buy-one-get-one')]}>
			<PageLoader/>
		</AppShell>
	);

	const kpis = [
		/* translators: %d: total number of rules */
		{ l: __('Active rules', 'buy-one-get-one'), v: stats?.active_rules ?? '—', d: sprintf(__('%d total rules', 'buy-one-get-one'), stats?.total_rules ?? 0) },
		{ l: __('BOGO orders', 'buy-one-get-one'), v: stats?.bogo_orders ?? '—', d: __('Orders with BOGO applied', 'buy-one-get-one') },
		{
			l: __('Discount given', 'buy-one-get-one'),
			v: stats?.total_discount ? `$${Number( stats.total_discount ).toFixed( 0 )}` : '—',
			d: __('Total discounts issued', 'buy-one-get-one')
		},
		/* translators: %d: number of currently active rules */
		{ l: __('Total rules', 'buy-one-get-one'), v: stats?.total_rules ?? '—', d: sprintf(__('%d currently active', 'buy-one-get-one'), stats?.active_rules ?? 0) },
	];
	
	return (
		<AppShell
			crumb={['Bogo', __('Dashboard', 'buy-one-get-one')]}
			actions={
				<button
					className="bogo-button bogo-button--primary bogo-button--sm"
					onClick={() => goTo( 'tab=rules&action=create' )}
				>
					<PlusIcon size={14}/> {__('New rule', 'buy-one-get-one')}
				</button>
			}
		>
			<div className="bogo-page-header">
				<div>
					<div className="bogo-page-header__title">{__('Dashboard', 'buy-one-get-one')}</div>
					<div className="bogo-page-header__desc">{__('Overview of your BOGO offers and performance.', 'buy-one-get-one')}</div>
				</div>
			</div>
			
			{/* KPIs */}
			<div className="bogo-kpi-grid">
				{kpis.map( ( k, i ) => (
					<div key={i} className="bogo-kpi">
						<div className="bogo-kpi__label">{k.l}</div>
						<div className="bogo-kpi__value">{k.v}</div>
						<div className="bogo-kpi__delta">{k.d}</div>
					</div>
				) )}
			</div>
			
			{/* Quick actions */}
			<div className="bogo-chart-card" style={{ marginTop: 18 }}>
				<div className="bogo-section-header__title" style={{ marginBottom: 14 }}>{__('Quick actions', 'buy-one-get-one')}</div>
				<div className="bogo-col" style={{ gap: 8 }}>
					{quickActions.map( ( a ) => (
						<button
							key={a.label}
							className="bogo-rule"
							style={{
								gridTemplateColumns: 'auto 1fr auto',
								cursor: 'pointer',
								padding: '12px 16px',
								textAlign: 'left',
								fontFamily: 'inherit'
							}}
							onClick={() => goTo( a.query )}
						>
              <span className="bogo-pkg" style={{
	              width: 32,
	              height: 32,
	              borderRadius: 8,
	              background: 'var(--chip)',
	              display: 'grid',
	              placeItems: 'center',
	              color: 'var(--ink-2)'
              }}>
                <a.Icon size={15}/>
              </span>
							<span className="bogo-col">
                <span style={{ fontWeight: 500, fontSize: 13 }}>{a.label}</span>
                <span style={{ fontSize: 12, color: 'var(--muted)', marginTop: 2 }}>{a.desc}</span>
              </span>
							<ArrowIcon size={14} stroke="var(--muted-2)"/>
						</button>
					) )}
				</div>
			</div>
			
			{/* Live offers */}
			<div className="bogo-section-header">
				<div className="bogo-col">
					<div className="bogo-section-header__title">{__('Live offers', 'buy-one-get-one')}</div>
					<div className="bogo-section-header__meta">{__('Currently active rules', 'buy-one-get-one')}</div>
				</div>
				<button
					className="bogo-button bogo-button--sm"
					onClick={() => goTo( 'tab=rules' )}
				>
					{__('View all rules', 'buy-one-get-one')} <ArrowIcon size={13}/>
				</button>
			</div>
			
			<div className="bogo-rule-list">
				{rules && rules.length > 0 ? (
					rules.map( ( rule ) => <RuleFlowRow key={rule.id} rule={rule}/> )
				) : (
					<div className="bogo-panel"
					     style={{ textAlign: 'center', padding: '32px 20px', color: 'var(--muted)' }}>
						<GiftIcon size={32} stroke="var(--muted-2)"/>
						<div style={{ marginTop: 12, fontSize: 14, fontWeight: 500, color: 'var(--ink)' }}>{__('No active rules yet', 'buy-one-get-one')}</div>
						<div style={{ fontSize: 13, marginTop: 4 }}>{__('Create your first BOGO rule to get started.', 'buy-one-get-one')}</div>
						<button
							className="bogo-button bogo-button--primary bogo-button--sm"
							style={{ marginTop: 14 }}
							onClick={() => goTo( 'tab=rules&action=create' )}
						>
							<PlusIcon size={13}/> {__('Create rule', 'buy-one-get-one')}
						</button>
					</div>
				)}
			</div>
		</AppShell>
	);
}

export default Dashboard;
