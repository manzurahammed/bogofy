import React from 'react';
import { __, sprintf } from '@wordpress/i18n';

function RulesPagination( { page, count, perPage, onPrev, onNext } ) {
	return (
		<div className="bogo-row"
		     style={{ justifyContent: 'space-between', marginTop: 18, color: 'var(--muted)', fontSize: 12 }}>
			{/* translators: %d: number of rules shown */}
			<span>{sprintf( __( 'Showing %d rules', 'bogofy' ), count )}</span>
			<div className="bogo-row" style={{ gap: 6 }}>
				<button className="bogo-button bogo-button--sm"
				        onClick={onPrev}
				        disabled={page === 1}>{__( '‹ Prev', 'bogofy' )}</button>
				<button className="bogo-button bogo-button--sm" onClick={onNext}
				        disabled={count < perPage}>{__( 'Next ›', 'bogofy' )}</button>
			</div>
		</div>
	);
}

export default RulesPagination;
