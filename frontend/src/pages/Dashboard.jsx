import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { useStats } from '../hooks/useSettings';
import { useRules } from '../hooks/useRules';
import { PageLoader } from '../components/Shared/Loader';
import AppShell from '../components/Layout/AppShell';
import RuleFlowRow from '../components/Dashboard/RuleFlowRow';
import QuickActions from '../components/Dashboard/QuickActions';
import { PlusIcon, ArrowIcon, GiftIcon } from '../components/Icons';

const goTo = ( query ) => {
	window.location.href = `admin.php?page=bogofy&${query}`;
};

function Dashboard() {
	const { data: stats, isLoading: statsLoading } = useStats();
	const { data: rules, isLoading: rulesLoading } = useRules( { page: 1, per_page: 3, status: 'active' } );

	const isLoading = statsLoading || rulesLoading;

	if ( isLoading ) return (
		<AppShell crumb={['Bogofy', __( 'Dashboard', 'bogofy' )]}>
			<PageLoader/>
		</AppShell>
	);

	const kpis = [
		/* translators: %d: total number of rules */
		{
			l: __( 'Active rules', 'bogofy' ),
			v: stats?.active_rules ?? '—',
			d: sprintf( __( '%d total rules', 'bogofy' ), stats?.total_rules ?? 0 )
		},
		{
			l: __( 'Bogofy orders', 'bogofy' ),
			v: stats?.bogo_orders ?? '—',
			d: __( 'Orders with Bogofy applied', 'bogofy' )
		},
		{
			l: __( 'Discount given', 'bogofy' ),
			v: stats?.total_discount ? `$${Number( stats.total_discount ).toFixed( 0 )}` : '—',
			d: __( 'Total discounts issued', 'bogofy' )
		},
		/* translators: %d: number of currently active rules */
		{
			l: __( 'Total rules', 'bogofy' ),
			v: stats?.total_rules ?? '—',
			d: sprintf( __( '%d currently active', 'bogofy' ), stats?.active_rules ?? 0 )
		},
	];

	return (
		<AppShell
			crumb={['Bogofy', __( 'Dashboard', 'bogofy' )]}
			actions={
				<button
					className="bogo-button bogo-button--primary bogo-button--sm"
					onClick={() => goTo( 'tab=rules&action=create' )}
				>
					<PlusIcon size={14}/> {__( 'New rule', 'bogofy' )}
				</button>
			}
		>
			<div className="bogo-page-header">
				<div>
					<div className="bogo-page-header__title">{__( 'Dashboard', 'bogofy' )}</div>
					<div
						className="bogo-page-header__desc">{__( 'Overview of your Bogofy offers and performance.', 'bogofy' )}</div>
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
			<QuickActions onNavigate={goTo}/>

			{/* Live offers */}
			<div className="bogo-section-header">
				<div className="bogo-col">
					<div className="bogo-section-header__title">{__( 'Live offers', 'bogofy' )}</div>
					<div className="bogo-section-header__meta">{__( 'Currently active rules', 'bogofy' )}</div>
				</div>
				<button
					className="bogo-button bogo-button--sm"
					onClick={() => goTo( 'tab=rules' )}
				>
					{__( 'View all rules', 'bogofy' )} <ArrowIcon size={13}/>
				</button>
			</div>

			<div className="bogo-rule-list">
				{rules && rules.length > 0 ? (
					rules.map( ( rule ) => (
						<RuleFlowRow
							key={rule.id}
							rule={rule}
							onEdit={( id ) => goTo( `tab=rules&action=edit&rule_id=${id}` )}
						/>
					) )
				) : (
					<div className="bogo-panel"
					     style={{ textAlign: 'center', padding: '32px 20px', color: 'var(--muted)' }}>
						<GiftIcon size={32} stroke="var(--muted-2)"/>
						<div style={{
							marginTop: 12,
							fontSize: 14,
							fontWeight: 500,
							color: 'var(--ink)'
						}}>{__( 'No active rules yet', 'bogofy' )}</div>
						<div style={{
							fontSize: 13,
							marginTop: 4
						}}>{__( 'Create your first Bogofy rule to get started.', 'bogofy' )}</div>
						<button
							className="bogo-button bogo-button--primary bogo-button--sm"
							style={{ marginTop: 14 }}
							onClick={() => goTo( 'tab=rules&action=create' )}
						>
							<PlusIcon size={13}/> {__( 'Create rule', 'bogofy' )}
						</button>
					</div>
				)}
			</div>
		</AppShell>
	);
}

export default Dashboard;
