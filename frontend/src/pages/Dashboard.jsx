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
	window.location.href = `admin.php?page=bogofy&${query}`;
};

function RuleFlowRow( { rule } ) {
	const isLive = rule.status === 'active';
	
	return (
		<div className="bogo-rule" style={{ gridTemplateColumns: '1fr auto' }}>
			<div className="bogo-col">
				<div className="bogo-rule__name">{rule.title}</div>
				<div className="bogo-flow">
          <span className="bogo-flow__node">
            {sprintf(__('Buy %d', 'bogofy'), rule.buy_quantity)}
          </span>
					<span className="bogo-flow__arrow">→</span>
					<span className="bogo-flow__node bogo-flow__node--get">
            <GiftIcon size={12}/>
            {rule.discount_type === 'free'
              ? sprintf(__('Get %d free', 'bogofy'), rule.free_quantity)
              /* translators: 1: quantity, 2: discount percentage */
              : sprintf(__('Get %1$d at %2$s%% off', 'bogofy'), rule.free_quantity, rule.discount_value)}
          </span>
				</div>
			</div>
			<div className="bogo-row" style={{ gap: 8 }}>
        <span className={`bogo-status ${isLive ? 'bogo-status--live' : 'bogo-status--inactive'}`}>
          <span className="bogo-status__dot"/>
	        {isLive ? __('Live', 'bogofy') : __('Inactive', 'bogofy')}
        </span>
				<button
					className="bogo-button bogo-button--sm bogo-button--ghost"
					title={__('Edit rule', 'bogofy')}
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
		label: __('Create a new rule', 'bogofy'),
		desc: __('Set up a Bogofy offer in 3 steps', 'bogofy'),
		Icon: PlusIcon,
		query: 'tab=rules&action=create'
	},
	{ label: __('Manage rules', 'bogofy'), desc: __('Edit, activate, or delete existing offers', 'bogofy'), Icon: TagIcon, query: 'tab=rules' },
	{ label: __('Display settings', 'bogofy'), desc: __('Badges, labels, and cart messages', 'bogofy'), Icon: SettingsIcon, query: 'tab=settings' },
	{ label: __('Help & docs', 'bogofy'), desc: __('Rule types and common questions', 'bogofy'), Icon: HelpIcon, query: 'tab=help' },
];

function Dashboard() {
	const { data: stats, isLoading: statsLoading } = useStats();
	const { data: rules, isLoading: rulesLoading } = useRules( { page: 1, per_page: 3, status: 'active' } );
	
	const isLoading = statsLoading || rulesLoading;
	
	if ( isLoading ) return (
		<AppShell crumb={['Bogofy', __('Dashboard', 'bogofy')]}>
			<PageLoader/>
		</AppShell>
	);

	const kpis = [
		/* translators: %d: total number of rules */
		{ l: __('Active rules', 'bogofy'), v: stats?.active_rules ?? '—', d: sprintf(__('%d total rules', 'bogofy'), stats?.total_rules ?? 0) },
		{ l: __('Bogofy orders', 'bogofy'), v: stats?.bogo_orders ?? '—', d: __('Orders with Bogofy applied', 'bogofy') },
		{
			l: __('Discount given', 'bogofy'),
			v: stats?.total_discount ? `$${Number( stats.total_discount ).toFixed( 0 )}` : '—',
			d: __('Total discounts issued', 'bogofy')
		},
		/* translators: %d: number of currently active rules */
		{ l: __('Total rules', 'bogofy'), v: stats?.total_rules ?? '—', d: sprintf(__('%d currently active', 'bogofy'), stats?.active_rules ?? 0) },
	];
	
	return (
		<AppShell
			crumb={['Bogofy', __('Dashboard', 'bogofy')]}
			actions={
				<button
					className="bogo-button bogo-button--primary bogo-button--sm"
					onClick={() => goTo( 'tab=rules&action=create' )}
				>
					<PlusIcon size={14}/> {__('New rule', 'bogofy')}
				</button>
			}
		>
			<div className="bogo-page-header">
				<div>
					<div className="bogo-page-header__title">{__('Dashboard', 'bogofy')}</div>
					<div className="bogo-page-header__desc">{__('Overview of your Bogofy offers and performance.', 'bogofy')}</div>
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
				<div className="bogo-section-header__title" style={{ marginBottom: 14 }}>{__('Quick actions', 'bogofy')}</div>
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
					<div className="bogo-section-header__title">{__('Live offers', 'bogofy')}</div>
					<div className="bogo-section-header__meta">{__('Currently active rules', 'bogofy')}</div>
				</div>
				<button
					className="bogo-button bogo-button--sm"
					onClick={() => goTo( 'tab=rules' )}
				>
					{__('View all rules', 'bogofy')} <ArrowIcon size={13}/>
				</button>
			</div>
			
			<div className="bogo-rule-list">
				{rules && rules.length > 0 ? (
					rules.map( ( rule ) => <RuleFlowRow key={rule.id} rule={rule}/> )
				) : (
					<div className="bogo-panel"
					     style={{ textAlign: 'center', padding: '32px 20px', color: 'var(--muted)' }}>
						<GiftIcon size={32} stroke="var(--muted-2)"/>
						<div style={{ marginTop: 12, fontSize: 14, fontWeight: 500, color: 'var(--ink)' }}>{__('No active rules yet', 'bogofy')}</div>
						<div style={{ fontSize: 13, marginTop: 4 }}>{__('Create your first Bogofy rule to get started.', 'bogofy')}</div>
						<button
							className="bogo-button bogo-button--primary bogo-button--sm"
							style={{ marginTop: 14 }}
							onClick={() => goTo( 'tab=rules&action=create' )}
						>
							<PlusIcon size={13}/> {__('Create rule', 'bogofy')}
						</button>
					</div>
				)}
			</div>
		</AppShell>
	);
}

export default Dashboard;
