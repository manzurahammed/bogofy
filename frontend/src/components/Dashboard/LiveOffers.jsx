import React from 'react';
import { __ } from '@wordpress/i18n';
import { PlusIcon, ArrowIcon, GiftIcon } from '../Icons';
import RuleFlowRow from './RuleFlowRow';

function LiveOffers( { rules, onEdit, onViewAll, onCreate } ) {
	return (
		<>
			<div className="bogo-section-header">
				<div className="bogo-col">
					<div className="bogo-section-header__title">{__( 'Live offers', 'bogofy' )}</div>
					<div className="bogo-section-header__meta">{__( 'Currently active rules', 'bogofy' )}</div>
				</div>
				<button className="bogo-button bogo-button--sm" onClick={onViewAll}>
					{__( 'View all rules', 'bogofy' )} <ArrowIcon size={13}/>
				</button>
			</div>

			<div className="bogo-rule-list">
				{rules && rules.length > 0 ? (
					rules.map( ( rule ) => (
						<RuleFlowRow key={rule.id} rule={rule} onEdit={onEdit}/>
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
							onClick={onCreate}
						>
							<PlusIcon size={13}/> {__( 'Create rule', 'bogofy' )}
						</button>
					</div>
				)}
			</div>
		</>
	);
}

export default LiveOffers;
