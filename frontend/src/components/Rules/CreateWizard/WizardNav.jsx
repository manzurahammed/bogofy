import React from 'react';
import { __ } from '@wordpress/i18n';
import { ArrowIcon } from '../../Icons';

function WizardNav( { step, onPrev, onNext } ) {
	return (
		<div className="bogo-row" style={{ justifyContent: 'space-between', marginTop: 4 }}>
			{step > 1 ? (
				<button className="bogo-button bogo-button--sm"
				        onClick={onPrev}>{__( '← Back', 'bogofy' )}</button>
			) : (
				<button className="bogo-button bogo-button--sm bogo-button--ghost"
				        onClick={() => window.history.back()}>{__( 'Cancel', 'bogofy' )}</button>
			)}
			<button className="bogo-button bogo-button--primary bogo-button--sm" onClick={onNext}>
				{__( 'Continue', 'bogofy' )} <ArrowIcon size={13}/>
			</button>
		</div>
	);
}

export default WizardNav;
