import { useState, useCallback } from 'react';
import { __ } from '@wordpress/i18n';
import { useCreateRule } from '../../../hooks/useRules';
import { useNotification } from '../../../hooks/useNotification';
import { goToAdmin } from '../../../utils/navigation';
import { defaultData } from './constants';

/**
 * Encapsulates all state and behaviour for the create-rule wizard.
 *
 * @returns {Object} Wizard state and handlers consumed by the wizard UI.
 */
export function useCreateRuleWizard() {
	const [step, setStep]                                 = useState( 1 );
	const [formData, setFormData]                         = useState( defaultData );
	const [selectedBuyProducts, setSelectedBuyProducts]   = useState( [] );
	const [selectedFreeProducts, setSelectedFreeProducts] = useState( [] );
	const [errors, setErrors]                             = useState( {} );

	const createRule         = useCreateRule();
	const { success, error } = useNotification();

	const validate = useCallback( () => {
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
	}, [formData, step, selectedBuyProducts, selectedFreeProducts] );

	const goNext = useCallback( () => {
		if ( !validate() ) return;
		setStep( ( s ) => Math.min( 3, s + 1 ) );
	}, [validate] );

	const goPrev = useCallback( () => setStep( ( s ) => Math.max( 1, s - 1 ) ), [] );

	const submitRule = useCallback( async ( status ) => {
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
			goToAdmin( 'tab=rules' );
		} catch ( err ) {
			error( err.message || __( 'Failed to create rule', 'bogofy' ) );
		}
	}, [validate, formData, selectedBuyProducts, selectedFreeProducts, createRule, success, error] );

	return {
		step,
		formData,
		setFormData,
		selectedBuyProducts,
		setSelectedBuyProducts,
		selectedFreeProducts,
		setSelectedFreeProducts,
		errors,
		goNext,
		goPrev,
		submitRule,
		isPending: createRule.isPending,
	};
}
