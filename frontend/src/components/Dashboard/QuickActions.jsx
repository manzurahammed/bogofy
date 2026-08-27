import React from 'react';
import { __ } from '@wordpress/i18n';
import { PlusIcon, ArrowIcon, TagIcon, SettingsIcon, HelpIcon } from '../Icons';

const quickActions = [
	{
		label: __( 'Create a new rule', 'bogofy' ),
		desc: __( 'Set up a Bogofy offer in 3 steps', 'bogofy' ),
		Icon: PlusIcon,
		query: 'tab=rules&action=create'
	},
	{
		label: __( 'Manage rules', 'bogofy' ),
		desc: __( 'Edit, activate, or delete existing offers', 'bogofy' ),
		Icon: TagIcon,
		query: 'tab=rules'
	},
	{
		label: __( 'Display settings', 'bogofy' ),
		desc: __( 'Badges, labels, and cart messages', 'bogofy' ),
		Icon: SettingsIcon,
		query: 'tab=settings'
	},
	{
		label: __( 'Help & docs', 'bogofy' ),
		desc: __( 'Rule types and common questions', 'bogofy' ),
		Icon: HelpIcon,
		query: 'tab=help'
	},
];

function QuickActions( { onNavigate } ) {
	return (
		<div className="bogo-chart-card" style={{ marginTop: 18 }}>
			<div className="bogo-section-header__title"
			     style={{ marginBottom: 14 }}>{__( 'Quick actions', 'bogofy' )}</div>
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
						onClick={() => onNavigate( a.query )}
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
	);
}

export default QuickActions;
