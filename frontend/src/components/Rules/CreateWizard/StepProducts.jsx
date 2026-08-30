import React from 'react';
import { __ } from '@wordpress/i18n';
import { ProductSearch } from '../../Shared/ProductSearch';
import { CheckIcon, GiftIcon } from '../../Icons';

// Step 2: Products
function StepProducts( {
	                       formData,
	                       setFormData,
	                       selectedBuyProducts,
	                       setSelectedBuyProducts,
	                       selectedFreeProducts,
	                       setSelectedFreeProducts,
	                       errors
                       } ) {
	const showFreeSelector = formData.rule_type === 'buy_x_get_y';

	return (
		<>
			<div className="bogo-wizard__card">
				<div className="bogo-wizard__title">{__( 'Trigger products', 'bogofy' )} <span
					style={{
						color: 'var(--muted)',
						fontWeight: 400,
						fontSize: 13
					}}>{__( '· “Buy X”', 'bogofy' )}</span></div>
				<div
					className="bogo-wizard__subtitle">{__( 'Customer must add these to cart for the offer to apply.', 'bogofy' )}</div>

				<div style={{ display: 'flex', gap: 6, marginTop: 16, marginBottom: 14, flexWrap: 'wrap' }}>
					{[
						{ v: 'specific_products', l: __( 'Specific products', 'bogofy' ) },
						{ v: 'all_products', l: __( 'Any product', 'bogofy' ) },
					].map( ( opt ) => (
						<button
							key={opt.v}
							type="button"
							className={`bogo-chip${formData.apply_to === opt.v ? ' bogo-chip--selected' : ''}`}
							onClick={() => setFormData( ( p ) => ( { ...p, apply_to: opt.v } ) )}
						>
							{formData.apply_to === opt.v && <CheckIcon size={11}/>}
							{opt.l}
						</button>
					) )}
				</div>

				{formData.apply_to === 'specific_products' && (
					<div style={{ marginBottom: 14 }}>
						<ProductSearch
							selectedProducts={selectedBuyProducts}
							onChange={setSelectedBuyProducts}
							placeholder={__( 'Search for trigger products…', 'bogofy' )}
						/>
						{errors.buy_products && <div className="bogo-form-error">{errors.buy_products}</div>}
					</div>
				)}

				<div className="bogo-row" style={{ gap: 10, marginTop: 8 }}>
					<label className="bogo-form-label"
					       style={{ margin: 0 }}>{__( 'Minimum quantity to trigger', 'bogofy' )}</label>
					<input
						type="number"
						className="bogo-form-input"
						style={{ width: 70, textAlign: 'center' }}
						min="1"
						value={formData.buy_quantity}
						onChange={( e ) => setFormData( ( p ) => ( { ...p, buy_quantity: Number( e.target.value ) } ) )}
					/>
					<span style={{ color: 'var(--muted)', fontSize: 12 }}>{__( 'items in cart', 'bogofy' )}</span>
				</div>
			</div>

			{showFreeSelector && (
				<div className="bogo-wizard__card">
					<div className="bogo-wizard__title">{__( 'Gift products', 'bogofy' )} <span
						style={{
							color: 'var(--muted)',
							fontWeight: 400,
							fontSize: 13
						}}>{__( '· “Get Y”', 'bogofy' )}</span></div>
					<div
						className="bogo-wizard__subtitle">{__( 'What customers receive when the trigger is met.', 'bogofy' )}</div>
					<div style={{ marginTop: 14 }}>
						<ProductSearch
							selectedProducts={selectedFreeProducts}
							onChange={setSelectedFreeProducts}
							placeholder={__( 'Search for gift products…', 'bogofy' )}
						/>
						{errors.free_products && <div className="bogo-form-error">{errors.free_products}</div>}
					</div>

					<div className="bogo-row"
					     style={{ marginTop: 16, gap: 14, padding: 14, background: '#fafaf9', borderRadius: 10 }}>
						<GiftIcon size={18} stroke="var(--accent-ink)"/>
						<div className="bogo-col bogo-fill">
							<div style={{ fontWeight: 600, fontSize: 13 }}>{__( '100% off (Free)', 'bogofy' )}</div>
							<div style={{
								fontSize: 12,
								color: 'var(--muted)',
								marginTop: 2
							}}>{__( 'The gift item is added to the cart at no cost.', 'bogofy' )}</div>
						</div>
					</div>

					<div className="bogo-row" style={{ gap: 10, marginTop: 14 }}>
						<label className="bogo-form-label"
						       style={{ margin: 0 }}>{__( 'Gift quantity', 'bogofy' )}</label>
						<input
							type="number"
							className="bogo-form-input"
							style={{ width: 70, textAlign: 'center' }}
							min="1"
							value={formData.free_quantity}
							onChange={( e ) => setFormData( ( p ) => ( {
								...p,
								free_quantity: Number( e.target.value )
							} ) )}
						/>
						<span style={{ color: 'var(--muted)', fontSize: 12 }}>{__( 'items gifted', 'bogofy' )}</span>
					</div>
				</div>
			)}
		</>
	);
}

export default StepProducts;
