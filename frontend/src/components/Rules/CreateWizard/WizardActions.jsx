import React from 'react';
import { __ } from '@wordpress/i18n';
import { ArrowIcon } from '../../Icons';

function WizardActions( { step, onSaveDraft, onNext, isPending } ) {
	return (
		<>
			<button className="bogo-button bogo-button--sm bogo-button--ghost"
			        onClick={onSaveDraft} disabled={isPending}>
				{__( 'Save draft', 'bogofy' )}
			</button>
			{step < 3 ? (
				<button className="bogo-button bogo-button--primary bogo-button--sm" onClick={onNext}>
					{__( 'Continue', 'bogofy' )} <ArrowIcon size={13}/>
				</button>
			) : null}
		</>
	);
}

export default WizardActions;
