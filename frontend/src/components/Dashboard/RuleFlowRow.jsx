import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { GiftIcon, EditIcon } from '../Icons';

function RuleFlowRow( { rule, onEdit } ) {
	const isLive = rule.status === 'active';

	return (
		<div className="bogo-rule" style={{ gridTemplateColumns: '1fr auto' }}>
			<div className="bogo-col">
				<div className="bogo-rule__name">{rule.title}</div>
				<div className="bogo-flow">
          <span className="bogo-flow__node">
            {sprintf( __( 'Buy %d', 'bogofy' ), rule.buy_quantity )}
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
			</div>
			<div className="bogo-row" style={{ gap: 8 }}>
        <span className={`bogo-status ${isLive ? 'bogo-status--live' : 'bogo-status--inactive'}`}>
          <span className="bogo-status__dot"/>
	        {isLive ? __( 'Live', 'bogofy' ) : __( 'Inactive', 'bogofy' )}
        </span>
				<button
					className="bogo-button bogo-button--sm bogo-button--ghost"
					title={__( 'Edit rule', 'bogofy' )}
					onClick={() => onEdit( rule.id )}
				>
					<EditIcon size={13}/>
				</button>
			</div>
		</div>
	);
}

export default RuleFlowRow;
