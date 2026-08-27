import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { GiftIcon, EditIcon, TrashIcon } from '../Icons';

const ruleTypeLabels = {
	buy_x_get_x: __( 'Buy X Get X Free', 'bogofy' ),
	buy_x_get_y: __( 'Buy X Get Y Free', 'bogofy' ),
	buy_cat_get_free: __( 'Category Bogofy', 'bogofy' ),
	buy_x_get_x_discounted: __( 'Buy X Get X Discounted', 'bogofy' ),
};

function RuleRow( { rule, onEdit, onDelete, onToggle } ) {
	const isActive    = rule.status === 'active';
	const statusClass = isActive ? 'bogo-status--live' : 'bogo-status--inactive';
	const statusLabel = isActive ? __( 'Live', 'bogofy' ) : __( 'Inactive', 'bogofy' );

	return (
		<div className="bogo-rule">
			<div className="bogo-col">
				<div className="bogo-row" style={{ gap: 8 }}>
					<span className="bogo-rule__name">{rule.title}</span>
					<span style={{
						fontSize: 11,
						color: 'var(--muted)',
						background: 'var(--chip)',
						padding: '2px 8px',
						borderRadius: 999
					}}>
            {ruleTypeLabels[rule.rule_type] || rule.rule_type}
          </span>
				</div>
				<div className="bogo-flow">
          <span className="bogo-flow__node">
            {rule.apply_to === 'specific_products'
	            ? sprintf( __( 'Buy %d items', 'bogofy' ), rule.buy_quantity )
	            : sprintf( __( 'Buy %d from category', 'bogofy' ), rule.buy_quantity )}
          </span>
					<span className="bogo-flow__arrow">→</span>
					<span className="bogo-flow__node bogo-flow__node--get">
            <GiftIcon size={12}/>
						{rule.discount_type === 'free'
							? sprintf( __( 'Get %d free', 'bogofy' ), rule.free_quantity )
							/* translators: 1: quantity, 2: discount percentage */
							: sprintf( __( 'Get %1$d at %2$s%% off', 'bogofy' ), rule.free_quantity, rule.discount_value )}
          </span>
				</div>
				{rule.start_date && (
					<div className="bogo-rule__meta">
						<span>{rule.start_date} → {rule.end_date || __( 'ongoing', 'bogofy' )}</span>
					</div>
				)}
			</div>
			<div className="bogo-col bogo-align-right" style={{ fontSize: 12, minWidth: 60 }}>
				<span style={{ color: 'var(--muted)' }}>{__( 'Priority', 'bogofy' )}</span>
				<span style={{ fontWeight: 600, marginTop: 2 }} className="bogo-mono">{rule.priority}</span>
			</div>
			<div className="bogo-row" style={{ gap: 8 }}>
        <span className={`bogo-status ${statusClass}`}>
          <span className="bogo-status__dot"/>
	        {statusLabel}
        </span>
				<button
					className={`bogo-toggle${isActive ? ' bogo-toggle--on' : ''}`}
					onClick={() => onToggle( rule )}
					title={isActive ? __( 'Deactivate', 'bogofy' ) : __( 'Activate', 'bogofy' )}
				/>
				<button
					className="bogo-button bogo-button--sm bogo-button--ghost"
					onClick={() => onEdit( rule.id )}
					title={__( 'Edit', 'bogofy' )}
				>
					<EditIcon size={13}/>
				</button>
				<button
					className="bogo-button bogo-button--sm bogo-button--ghost"
					onClick={() => onDelete( rule.id )}
					title={__( 'Delete', 'bogofy' )}
					style={{ color: 'var(--danger-clr)' }}
				>
					<TrashIcon size={13}/>
				</button>
			</div>
		</div>
	);
}

export default RuleRow;
