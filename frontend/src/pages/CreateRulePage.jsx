import React, { useState } from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { useCreateRule } from '../hooks/useRules';
import { useNotification } from '../hooks/useNotification';
import AppShell from '../components/Layout/AppShell';
import { ProductSearch } from '../components/Shared/ProductSearch';
import {
	CheckIcon, ArrowIcon, BoxIcon, GiftIcon,
	EyeIcon, PowerIcon,
} from '../components/Icons';

const ruleTypes = [
	{
		value: 'buy_x_get_x',
		label: __('Buy X, Get same free', 'buy-one-get-one'),
		desc: __('Cheapest of N identical items becomes free.', 'buy-one-get-one'),
		vis: 'same'
	},
	{
		value: 'buy_x_get_y',
		label: __('Buy X, Get Y free', 'buy-one-get-one'),
		desc: __('Customer adds trigger item; gift drops to $0.', 'buy-one-get-one'),
		vis: 'gift'
	},
];

const defaultData = {
	title: '',
	rule_type: 'buy_x_get_y',
	status: 'active',
	buy_quantity: 1,
	free_quantity: 1,
	discount_type: 'free',
	discount_value: 100,
	apply_to: 'specific_products',
	buy_product_ids: [],
	free_product_ids: [],
	category_ids: [],
	max_free_qty: '',
	message_template: '',
	priority: 10,
	start_date: '',
	end_date: '',
};

function Stepper( { active } ) {
	const steps = [__('Offer type', 'buy-one-get-one'), __('Products', 'buy-one-get-one'), __('Review', 'buy-one-get-one')];
	return (
		<div className="bogo-stepper">
			{steps.map( ( s, i ) => {
				const n     = i + 1;
				const state = n < active ? 'bogo-stepper__step--done' : n === active ? 'bogo-stepper__step--active' : '';
				return (
					<React.Fragment key={i}>
						<div className={`bogo-stepper__step ${state}`}>
              <span className="bogo-stepper__num">
                {n < active ? <CheckIcon size={12} sw={2.5}/> : n}
              </span>
							<span>{s}</span>
						</div>
						{i < steps.length - 1 && <div className="bogo-stepper__sep"/>}
					</React.Fragment>
				);
			} )}
		</div>
	);
}

function CartPreview( { formData, selectedBuyProducts, selectedFreeProducts, step } ) {
	return (
		<div className="bogo-wizard__card" style={{ padding: 0, overflow: 'hidden' }}>
			<div className="bogo-row" style={{ padding: '14px 18px', borderBottom: '1px solid var(--line)', gap: 8 }}>
				<EyeIcon size={14} stroke="var(--muted)"/>
				<span style={{ fontSize: 12.5, fontWeight: 600 }}>{__('Live preview', 'buy-one-get-one')}</span>
				<span style={{ marginLeft: 'auto', color: 'var(--muted)', fontSize: 11.5 }}>{__('Cart view', 'buy-one-get-one')}</span>
			</div>
			<div style={{ padding: 16 }}>
				<div className="bogo-preview__cart">
					{selectedBuyProducts.slice( 0, 2 ).map( ( p, i ) => (
						<div key={i} className="bogo-preview__line">
							<div className="bogo-thumb"/>
							<div className="bogo-col">
								<div className="bogo-preview__name">{p.name}</div>
								<div className="bogo-preview__price">{__('Qty 1', 'buy-one-get-one')}</div>
							</div>
							<span className="bogo-mono" style={{ fontSize: 12 }}>—</span>
						</div>
					) )}
					{selectedBuyProducts.length === 0 && (
						<div className="bogo-preview__line">
							<div className="bogo-thumb"/>
							<div className="bogo-col">
								<div className="bogo-preview__name">{__('Trigger product', 'buy-one-get-one')}</div>
								{/* translators: %d: quantity */}
								<div className="bogo-preview__price">{sprintf(__('Qty %d', 'buy-one-get-one'), formData.buy_quantity)}</div>
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
									{selectedFreeProducts[0]?.name || __('Gift product', 'buy-one-get-one')}{' '}
									<span className="bogo-free-badge">{__('FREE', 'buy-one-get-one')}</span>
								</div>
								<div className="bogo-preview__price" style={{ color: 'var(--accent-ink)' }}>{__('BOGO discount', 'buy-one-get-one')}</div>
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
							<span>{__('Total', 'buy-one-get-one')}</span>
							<span className="bogo-mono">—</span>
						</div>
					</div>
				</div>
			</div>
		</div>
	);
}

function SummaryRail( { formData, step, selectedBuyProducts, selectedFreeProducts } ) {
	const SumRow = ( { l, v, done } ) => (
		<div className="bogo-summary-row">
			<span style={{ color: 'var(--muted)' }}>{l}</span>
			<span style={{
				display: 'inline-flex',
				alignItems: 'center',
				gap: 6,
				color: done ? 'var(--ink)' : 'var(--muted-2)',
				fontWeight: done ? 500 : 400
			}}>
        {done && <span style={{ width: 5, height: 5, borderRadius: 999, background: 'var(--accent)' }}/>}
				{v}
      </span>
		</div>
	);
	
	return (
		<div className="bogo-wizard__card">
			<div style={{ fontSize: 12.5, fontWeight: 600, marginBottom: 10 }}>{__('Summary', 'buy-one-get-one')}</div>
			<div className="bogo-col" style={{ gap: 9 }}>
				<SumRow l={__('Type', 'buy-one-get-one')} v={ruleTypes.find( ( r ) => r.value === formData.rule_type )?.label || '—'}
				        done={step >= 1}/>
				<SumRow l={__('Name', 'buy-one-get-one')} v={formData.title || __('Not set', 'buy-one-get-one')} done={!!formData.title}/>
				<SumRow l={__('Trigger', 'buy-one-get-one')}
				        /* translators: 1: number of products, 2: minimum quantity */
				        v={selectedBuyProducts.length > 0 ? sprintf(__('%1$d products · min %2$d', 'buy-one-get-one'), selectedBuyProducts.length, formData.buy_quantity) : __('Not set', 'buy-one-get-one')}
				        done={selectedBuyProducts.length > 0}/>
				<SumRow l={__('Gift', 'buy-one-get-one')}
				        /* translators: %d: number of products */
				        v={selectedFreeProducts.length > 0 ? sprintf(__('%d products', 'buy-one-get-one'), selectedFreeProducts.length) : __('Not set', 'buy-one-get-one')}
				        done={selectedFreeProducts.length > 0}/>
			</div>
		</div>
	);
}

// Step 1: Offer type
function Step1( { formData, setFormData } ) {
	return (
		<div className="bogo-wizard__card">
			<div className="bogo-wizard__title">{__('Choose offer type', 'buy-one-get-one')}</div>
			<div className="bogo-wizard__subtitle">{__('The structure of your rule — determines what other steps look like.', 'buy-one-get-one')}</div>
			<div className="bogo-type-grid">
				{ruleTypes.map( ( t ) => (
					<div
						key={t.value}
						className={`bogo-type-card${formData.rule_type === t.value ? ' bogo-type-card--selected' : ''}`}
						onClick={() => setFormData( ( p ) => ( { ...p, rule_type: t.value } ) )}
					>
						<div className="bogo-type-card__visual">
							<div className="bogo-pkg"><BoxIcon size={14}/></div>
							<span style={{ color: 'var(--muted-2)', fontSize: 12 }}>→</span>
							<div className={`bogo-pkg${formData.rule_type === t.value ? ' bogo-pkg--get' : ''}`}>
								{t.vis === 'gift' && <GiftIcon size={14}/>}
								{t.vis === 'same' && <BoxIcon size={14}/>}
							</div>
						</div>
						<div>
							<div className="bogo-type-card__title">{t.label}</div>
							<div className="bogo-type-card__desc">{t.desc}</div>
						</div>
					</div>
				) )}
			</div>
			
			<div style={{ marginTop: 22 }}>
				<label className="bogo-form-label">{__('Rule name', 'buy-one-get-one')}</label>
				<input
					className="bogo-form-input"
					placeholder={__('e.g., Summer Swim — Buy 2 Get 1', 'buy-one-get-one')}
					value={formData.title}
					onChange={( e ) => setFormData( ( p ) => ( { ...p, title: e.target.value } ) )}
				/>
			</div>
		</div>
	);
}

// Step 2: Products
function Step2( {
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
				<div className="bogo-wizard__title">{__('Trigger products', 'buy-one-get-one')} <span
					style={{ color: 'var(--muted)', fontWeight: 400, fontSize: 13 }}>{__('· “Buy X”', 'buy-one-get-one')}</span></div>
				<div className="bogo-wizard__subtitle">{__('Customer must add these to cart for the offer to apply.', 'buy-one-get-one')}</div>

				<div style={{ display: 'flex', gap: 6, marginTop: 16, marginBottom: 14, flexWrap: 'wrap' }}>
					{[
						{ v: 'specific_products', l: __('Specific products', 'buy-one-get-one') },
						{ v: 'all_products', l: __('Any product', 'buy-one-get-one') },
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
							placeholder={__('Search for trigger products…', 'buy-one-get-one')}
						/>
						{errors.buy_products && <div className="bogo-form-error">{errors.buy_products}</div>}
					</div>
				)}

				<div className="bogo-row" style={{ gap: 10, marginTop: 8 }}>
					<label className="bogo-form-label" style={{ margin: 0 }}>{__('Minimum quantity to trigger', 'buy-one-get-one')}</label>
					<input
						type="number"
						className="bogo-form-input"
						style={{ width: 70, textAlign: 'center' }}
						min="1"
						value={formData.buy_quantity}
						onChange={( e ) => setFormData( ( p ) => ( { ...p, buy_quantity: Number( e.target.value ) } ) )}
					/>
					<span style={{ color: 'var(--muted)', fontSize: 12 }}>{__('items in cart', 'buy-one-get-one')}</span>
				</div>
			</div>
			
			{showFreeSelector && (
				<div className="bogo-wizard__card">
					<div className="bogo-wizard__title">{__('Gift products', 'buy-one-get-one')} <span
						style={{ color: 'var(--muted)', fontWeight: 400, fontSize: 13 }}>{__('· “Get Y”', 'buy-one-get-one')}</span></div>
					<div className="bogo-wizard__subtitle">{__('What customers receive when the trigger is met.', 'buy-one-get-one')}</div>
					<div style={{ marginTop: 14 }}>
						<ProductSearch
							selectedProducts={selectedFreeProducts}
							onChange={setSelectedFreeProducts}
							placeholder={__('Search for gift products…', 'buy-one-get-one')}
						/>
						{errors.free_products && <div className="bogo-form-error">{errors.free_products}</div>}
					</div>
					
					<div className="bogo-row"
					     style={{ marginTop: 16, gap: 14, padding: 14, background: '#fafaf9', borderRadius: 10 }}>
						<GiftIcon size={18} stroke="var(--accent-ink)"/>
						<div className="bogo-col bogo-fill">
							<div style={{ fontWeight: 600, fontSize: 13 }}>{__('100% off (Free)', 'buy-one-get-one')}</div>
							<div style={{ fontSize: 12, color: 'var(--muted)', marginTop: 2 }}>{__('The gift item is added to the cart at no cost.', 'buy-one-get-one')}</div>
						</div>
					</div>

					<div className="bogo-row" style={{ gap: 10, marginTop: 14 }}>
						<label className="bogo-form-label" style={{ margin: 0 }}>{__('Gift quantity', 'buy-one-get-one')}</label>
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
						<span style={{ color: 'var(--muted)', fontSize: 12 }}>{__('items gifted', 'buy-one-get-one')}</span>
					</div>
				</div>
			)}
		</>
	);
}

// Step 3: Review
function Step4( { formData, selectedBuyProducts, selectedFreeProducts, onLaunch, onDraft, isLoading } ) {
	const ReviewBlock = ( { title, step, children } ) => (
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
				<span style={{ fontSize: 11, color: 'var(--muted)' }}>{sprintf(__('Step %s', 'buy-one-get-one'), step)}</span>
			</div>
			{children}
		</div>
	);

	return (
		<div className="bogo-wizard__card">
			<div className="bogo-wizard__title">{__('Review your rule', 'buy-one-get-one')}</div>
			<div className="bogo-wizard__subtitle">{__('Everything looks good? Launch it live or save as draft.', 'buy-one-get-one')}</div>

			<div className="bogo-review-grid">
				<ReviewBlock title={__('Offer type', 'buy-one-get-one')} step="1">
					<div className="bogo-flow">
						{/* translators: %d: quantity */}
						<span className="bogo-flow__node"><BoxIcon size={12}/> {sprintf(__('Buy %d', 'buy-one-get-one'), formData.buy_quantity)}</span>
						<span className="bogo-flow__arrow">→</span>
						{/* translators: %d: quantity */}
						<span className="bogo-flow__node bogo-flow__node--get"><GiftIcon
							size={12}/> {sprintf(__('Get %d free', 'buy-one-get-one'), formData.free_quantity)}</span>
					</div>
				</ReviewBlock>
				<ReviewBlock title={__('Name', 'buy-one-get-one')} step="1">
					<div style={{ fontWeight: 600, fontSize: 13 }}>{formData.title || __('(Unnamed rule)', 'buy-one-get-one')}</div>
				</ReviewBlock>

				<ReviewBlock title={__('Trigger products', 'buy-one-get-one')} step="2">
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
							}}>{sprintf(__('All products (%s)', 'buy-one-get-one'), formData.apply_to)}</span>
						}
					</div>
					{/* translators: %d: minimum quantity */}
					<div style={{ color: 'var(--muted)', fontSize: 11.5, marginTop: 8 }}>{sprintf(__('Min. qty: %d', 'buy-one-get-one'), formData.buy_quantity)}</div>
				</ReviewBlock>
				<ReviewBlock title={__('Gift products', 'buy-one-get-one')} step="2">
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
						{__('100% off (Free)', 'buy-one-get-one')}
					</div>
				</ReviewBlock>
			</div>

			<div className="bogo-row" style={{ marginTop: 22, gap: 10, justifyContent: 'flex-end' }}>
				<button type="button" className="bogo-button" onClick={onDraft} disabled={isLoading}>
					{__('Save as draft', 'buy-one-get-one')}
				</button>
				<button type="button" className="bogo-button bogo-button--primary" onClick={onLaunch}
				        disabled={isLoading}>
					<PowerIcon size={14}/>
					{isLoading ? __('Saving…', 'buy-one-get-one') : __('Launch rule', 'buy-one-get-one')}
				</button>
			</div>
		</div>
	);
}

function CreateRulePage() {
	const [step, setStep]                                 = useState( 1 );
	const [formData, setFormData]                         = useState( defaultData );
	const [selectedBuyProducts, setSelectedBuyProducts]   = useState( [] );
	const [selectedFreeProducts, setSelectedFreeProducts] = useState( [] );
	const [errors, setErrors]                             = useState( {} );
	
	const createRule         = useCreateRule();
	const { success, error } = useNotification();
	
	const validate = () => {
		const errs = {};
		if ( !formData.title.trim() ) errs.title = __('Rule name is required', 'buy-one-get-one');
		if ( step >= 2 && formData.apply_to === 'specific_products' && selectedBuyProducts.length === 0 ) {
			errs.buy_products = __('Select at least one trigger product', 'buy-one-get-one');
		}
		if ( step >= 2 && formData.rule_type === 'buy_x_get_y' && selectedFreeProducts.length === 0 ) {
			errs.free_products = __('Select at least one gift product', 'buy-one-get-one');
		}
		setErrors( errs );
		return Object.keys( errs ).length === 0;
	};
	
	const goNext = () => {
		if ( !validate() ) return;
		setStep( ( s ) => Math.min( 3, s + 1 ) );
	};
	
	const goPrev = () => setStep( ( s ) => Math.max( 1, s - 1 ) );
	
	const submitRule = async ( status ) => {
		if ( !validate() ) return;
		const data = {
			...formData,
			status,
			buy_product_ids: selectedBuyProducts.map( ( p ) => p.id ),
			free_product_ids: selectedFreeProducts.map( ( p ) => p.id ),
		};
		try {
			await createRule.mutateAsync( data );
			success( __('Rule created successfully', 'buy-one-get-one') );
			window.location.href = 'admin.php?page=buy-one-get-one&tab=rules';
		} catch ( err ) {
			error( err.message || __('Failed to create rule', 'buy-one-get-one') );
		}
	};
	
	const stepContent = () => {
		switch ( step ) {
			case 1:
				return <Step1 formData={formData} setFormData={setFormData}/>;
			case 2:
				return <Step2 formData={formData} setFormData={setFormData} selectedBuyProducts={selectedBuyProducts}
				              setSelectedBuyProducts={setSelectedBuyProducts}
				              selectedFreeProducts={selectedFreeProducts}
				              setSelectedFreeProducts={setSelectedFreeProducts} errors={errors}/>;
			case 3:
				return <Step4 formData={formData} selectedBuyProducts={selectedBuyProducts}
				              selectedFreeProducts={selectedFreeProducts} onLaunch={() => submitRule( 'active' )}
				              onDraft={() => submitRule( 'inactive' )} isLoading={createRule.isPending}/>;
			default:
				return null;
		}
	};
	
	return (
		<AppShell
			crumb={['Bogo', __('BOGO Rules', 'buy-one-get-one'), __('New rule', 'buy-one-get-one')]}
			actions={
				<>
					<button className="bogo-button bogo-button--sm bogo-button--ghost"
					        onClick={() => submitRule( 'inactive' )} disabled={createRule.isPending}>
						{__('Save draft', 'buy-one-get-one')}
					</button>
					{step < 3 ? (
						<button className="bogo-button bogo-button--primary bogo-button--sm" onClick={goNext}>
							{__('Continue', 'buy-one-get-one')} <ArrowIcon size={13}/>
						</button>
					) : null}
				</>
			}
		>
			<div className="bogo-row"
			     style={{ alignItems: 'flex-start', justifyContent: 'space-between', marginBottom: 22 }}>
				<div>
					<div className="bogo-page-header__title">{__('Create BOGO rule', 'buy-one-get-one')}</div>
					<div className="bogo-page-header__desc">{__('Set up a buy-one-get-one offer in 3 steps. You can edit anything later.', 'buy-one-get-one')}</div>
				</div>
				<Stepper active={step}/>
			</div>
			
			{errors.title && step === 1 && (
				<div style={{
					marginBottom: 14,
					padding: '10px 14px',
					background: '#fff0f0',
					border: '1px solid #ffc0c0',
					borderRadius: 10,
					color: 'var(--danger-clr)',
					fontSize: 13
				}}>
					{errors.title}
				</div>
			)}
			
			<div className="bogo-wizard">
				<div className="bogo-col" style={{ gap: 16 }}>
					{stepContent()}
					
					{step < 3 && (
						<div className="bogo-row" style={{ justifyContent: 'space-between', marginTop: 4 }}>
							{step > 1 ? (
								<button className="bogo-button bogo-button--sm" onClick={goPrev}>{__('← Back', 'buy-one-get-one')}</button>
							) : (
								<button className="bogo-button bogo-button--sm bogo-button--ghost"
								        onClick={() => window.history.back()}>{__('Cancel', 'buy-one-get-one')}</button>
							)}
							<button className="bogo-button bogo-button--primary bogo-button--sm" onClick={goNext}>
								{__('Continue', 'buy-one-get-one')} <ArrowIcon size={13}/>
							</button>
						</div>
					)}
				</div>
				
				<div className="bogo-col" style={{ gap: 16 }}>
					<CartPreview formData={formData} selectedBuyProducts={selectedBuyProducts}
					             selectedFreeProducts={selectedFreeProducts} step={step}/>
					<SummaryRail formData={formData} step={step} selectedBuyProducts={selectedBuyProducts}
					             selectedFreeProducts={selectedFreeProducts}/>
				</div>
			</div>
		</AppShell>
	);
}

export default CreateRulePage;
