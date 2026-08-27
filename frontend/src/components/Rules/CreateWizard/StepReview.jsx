import React from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { BoxIcon, GiftIcon, PowerIcon } from '../../Icons';

function ReviewBlock( { title, step, children } ) {
	return (
		<div className="bogo-review-block">
			<div className="bogo-row" style={{ justifyContent: 'space-between', marginBottom: 8 }}>
				<span style={{
					fontSize: 11.5,
					color: 'var(--muted)',
					textTransform: 'uppercase',
					letterSpacing: '0.04em',
					fontWeight: 500
				}}>{title}</span>
				{/* translators: %s: step number */}
				<span
					style={{ fontSize: 11, color: 'var(--muted)' }}>{sprintf( __( 'Step %s', 'bogofy' ), step )}</span>
			</div>
			{children}
		</div>
	);
}

// Step 3: Review
function StepReview( { formData, selectedBuyProducts, selectedFreeProducts, onLaunch, onDraft, isLoading } ) {
	return (
		<div className="bogo-wizard__card">
			<div className="bogo-wizard__title">{__( 'Review your rule', 'bogofy' )}</div>
			<div
				className="bogo-wizard__subtitle">{__( 'Everything looks good? Launch it live or save as draft.', 'bogofy' )}</div>

			<div className="bogo-review-grid">
				<ReviewBlock title={__( 'Offer type', 'bogofy' )} step="1">
					<div className="bogo-flow">
						{/* translators: %d: quantity */}
						<span className="bogo-flow__node"><BoxIcon
							size={12}/> {sprintf( __( 'Buy %d', 'bogofy' ), formData.buy_quantity )}</span>
						<span className="bogo-flow__arrow">→</span>
						{/* translators: %d: quantity */}
						<span className="bogo-flow__node bogo-flow__node--get"><GiftIcon
							size={12}/> {sprintf( __( 'Get %d free', 'bogofy' ), formData.free_quantity )}</span>
					</div>
				</ReviewBlock>
				<ReviewBlock title={__( 'Name', 'bogofy' )} step="1">
					<div style={{
						fontWeight: 600,
						fontSize: 13
					}}>{formData.title || __( '(Unnamed rule)', 'bogofy' )}</div>
				</ReviewBlock>

				<ReviewBlock title={__( 'Trigger products', 'bogofy' )} step="2">
					<div style={{ display: 'flex', gap: 6, flexWrap: 'wrap' }}>
						{selectedBuyProducts.length > 0
							? selectedBuyProducts.map( ( p ) => (
								<span key={p.id} className="bogo-product-pill"><span
									className="bogo-thumb"/>{p.name}</span>
							) )
							/* translators: %s: apply-to type */
							: <span style={{
								color: 'var(--muted)',
								fontSize: 12
							}}>{sprintf( __( 'All products (%s)', 'bogofy' ), formData.apply_to )}</span>
						}
					</div>
					{/* translators: %d: minimum quantity */}
					<div style={{
						color: 'var(--muted)',
						fontSize: 11.5,
						marginTop: 8
					}}>{sprintf( __( 'Min. qty: %d', 'bogofy' ), formData.buy_quantity )}</div>
				</ReviewBlock>
				<ReviewBlock title={__( 'Gift products', 'bogofy' )} step="2">
					<div style={{ display: 'flex', gap: 6, flexWrap: 'wrap' }}>
						{selectedFreeProducts.length > 0
							? selectedFreeProducts.map( ( p ) => (
								<span key={p.id} className="bogo-product-pill"><span
									className="bogo-thumb"/>{p.name}</span>
							) )
							: <span style={{ color: 'var(--muted)', fontSize: 12 }}>—</span>
						}
					</div>
					<div style={{ color: 'var(--accent-ink)', fontSize: 11.5, marginTop: 8, fontWeight: 600 }}>
						{__( '100% off (Free)', 'bogofy' )}
					</div>
				</ReviewBlock>
			</div>

			<div className="bogo-row" style={{ marginTop: 22, gap: 10, justifyContent: 'flex-end' }}>
				<button type="button" className="bogo-button" onClick={onDraft} disabled={isLoading}>
					{__( 'Save as draft', 'bogofy' )}
				</button>
				<button type="button" className="bogo-button bogo-button--primary" onClick={onLaunch}
				        disabled={isLoading}>
					<PowerIcon size={14}/>
					{isLoading ? __( 'Saving…', 'bogofy' ) : __( 'Launch rule', 'bogofy' )}
				</button>
			</div>
		</div>
	);
}

export default StepReview;
