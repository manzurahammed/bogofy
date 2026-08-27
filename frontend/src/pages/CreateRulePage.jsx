import React, { useState } from 'react';
import { __ } from '@wordpress/i18n';
import { useCreateRule } from '../hooks/useRules';
import { useNotification } from '../hooks/useNotification';
import AppShell from '../components/Layout/AppShell';
import { ArrowIcon } from '../components/Icons';
import { defaultData } from '../components/Rules/CreateWizard/constants';
import Stepper from '../components/Rules/CreateWizard/Stepper';
import CartPreview from '../components/Rules/CreateWizard/CartPreview';
import SummaryRail from '../components/Rules/CreateWizard/SummaryRail';
import StepOfferType from '../components/Rules/CreateWizard/StepOfferType';
import StepProducts from '../components/Rules/CreateWizard/StepProducts';
import StepReview from '../components/Rules/CreateWizard/StepReview';

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
		if ( !formData.title.trim() ) errs.title = __( 'Rule name is required', 'bogofy' );
		if ( step >= 2 && formData.apply_to === 'specific_products' && selectedBuyProducts.length === 0 ) {
			errs.buy_products = __( 'Select at least one trigger product', 'bogofy' );
		}
		if ( step >= 2 && formData.rule_type === 'buy_x_get_y' && selectedFreeProducts.length === 0 ) {
			errs.free_products = __( 'Select at least one gift product', 'bogofy' );
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
			success( __( 'Rule created successfully', 'bogofy' ) );
			window.location.href = 'admin.php?page=bogofy&tab=rules';
		} catch ( err ) {
			error( err.message || __( 'Failed to create rule', 'bogofy' ) );
		}
	};

	const stepContent = () => {
		switch ( step ) {
			case 1:
				return <StepOfferType formData={formData} setFormData={setFormData}/>;
			case 2:
				return <StepProducts formData={formData} setFormData={setFormData}
				                     selectedBuyProducts={selectedBuyProducts}
				                     setSelectedBuyProducts={setSelectedBuyProducts}
				                     selectedFreeProducts={selectedFreeProducts}
				                     setSelectedFreeProducts={setSelectedFreeProducts} errors={errors}/>;
			case 3:
				return <StepReview formData={formData} selectedBuyProducts={selectedBuyProducts}
				                   selectedFreeProducts={selectedFreeProducts} onLaunch={() => submitRule( 'active' )}
				                   onDraft={() => submitRule( 'inactive' )} isLoading={createRule.isPending}/>;
			default:
				return null;
		}
	};

	return (
		<AppShell
			crumb={['Bogofy', __( 'Bogofy Rules', 'bogofy' ), __( 'New rule', 'bogofy' )]}
			actions={
				<>
					<button className="bogo-button bogo-button--sm bogo-button--ghost"
					        onClick={() => submitRule( 'inactive' )} disabled={createRule.isPending}>
						{__( 'Save draft', 'bogofy' )}
					</button>
					{step < 3 ? (
						<button className="bogo-button bogo-button--primary bogo-button--sm" onClick={goNext}>
							{__( 'Continue', 'bogofy' )} <ArrowIcon size={13}/>
						</button>
					) : null}
				</>
			}
		>
			<div className="bogo-row"
			     style={{ alignItems: 'flex-start', justifyContent: 'space-between', marginBottom: 22 }}>
				<div>
					<div className="bogo-page-header__title">{__( 'Create Bogofy rule', 'bogofy' )}</div>
					<div
						className="bogo-page-header__desc">{__( 'Set up a bogofy offer in 3 steps. You can edit anything later.', 'bogofy' )}</div>
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
								<button className="bogo-button bogo-button--sm"
								        onClick={goPrev}>{__( '← Back', 'bogofy' )}</button>
							) : (
								<button className="bogo-button bogo-button--sm bogo-button--ghost"
								        onClick={() => window.history.back()}>{__( 'Cancel', 'bogofy' )}</button>
							)}
							<button className="bogo-button bogo-button--primary bogo-button--sm" onClick={goNext}>
								{__( 'Continue', 'bogofy' )} <ArrowIcon size={13}/>
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
