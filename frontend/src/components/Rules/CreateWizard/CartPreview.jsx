import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { EyeIcon } from '../../Icons';

function CartPreview( { formData, selectedBuyProducts, selectedFreeProducts, step } ) {
	return (
		<div className="bogo-wizard__card" style={{ padding: 0, overflow: 'hidden' }}>
			<div className="bogo-row" style={{ padding: '14px 18px', borderBottom: '1px solid var(--line)', gap: 8 }}>
				<EyeIcon size={14} stroke="var(--muted)"/>
				<span style={{ fontSize: 12.5, fontWeight: 600 }}>{__( 'Live preview', 'bogofy' )}</span>
				<span style={{
					marginLeft: 'auto',
					color: 'var(--muted)',
					fontSize: 11.5
				}}>{__( 'Cart view', 'bogofy' )}</span>
			</div>
			<div style={{ padding: 16 }}>
				<div className="bogo-preview__cart">
					{selectedBuyProducts.slice( 0, 2 ).map( ( p, i ) => (
						<div key={i} className="bogo-preview__line">
							<div className="bogo-thumb"/>
							<div className="bogo-col">
								<div className="bogo-preview__name">{p.name}</div>
								<div className="bogo-preview__price">{__( 'Qty 1', 'bogofy' )}</div>
							</div>
							<span className="bogo-mono" style={{ fontSize: 12 }}>—</span>
						</div>
					) )}
					{selectedBuyProducts.length === 0 && (
						<div className="bogo-preview__line">
							<div className="bogo-thumb"/>
							<div className="bogo-col">
								<div className="bogo-preview__name">{__( 'Trigger product', 'bogofy' )}</div>
								{/* translators: %d: quantity */}
								<div
									className="bogo-preview__price">{sprintf( __( 'Qty %d', 'bogofy' ), formData.buy_quantity )}</div>
							</div>
							<span className="bogo-mono" style={{ fontSize: 12 }}>—</span>
						</div>
					)}
					{step >= 2 && selectedFreeProducts.length > 0 && (
						<div className="bogo-preview__line"
						     style={{ background: 'var(--accent-soft)', margin: '0 -14px', padding: '8px 14px' }}>
							<div className="bogo-thumb"
							     style={{ background: 'linear-gradient(135deg,#fbcfe8,#f0abfc)' }}/>
							<div className="bogo-col">
								<div className="bogo-preview__name">
									{selectedFreeProducts[0]?.name || __( 'Gift product', 'bogofy' )}{' '}
									<span className="bogo-free-badge">{__( 'FREE', 'bogofy' )}</span>
								</div>
								<div className="bogo-preview__price"
								     style={{ color: 'var(--accent-ink)' }}>{__( 'Bogofy discount', 'bogofy' )}</div>
							</div>
							<div className="bogo-col bogo-align-right">
									<span className="bogo-mono" style={{
										fontSize: 12,
										fontWeight: 600,
										color: 'var(--accent-ink)'
									}}>−$0.00</span>
							</div>
						</div>
					)}
					<div style={{ padding: '10px 0 0', borderTop: '1px solid var(--line)', marginTop: 4 }}>
						<div className="bogo-row"
						     style={{ justifyContent: 'space-between', fontSize: 14, fontWeight: 700, marginTop: 4 }}>
							<span>{__( 'Total', 'bogofy' )}</span>
							<span className="bogo-mono">—</span>
						</div>
					</div>
				</div>
			</div>
		</div>
	);
}

export default CartPreview;
