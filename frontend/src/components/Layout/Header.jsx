import React from 'react';
import PropTypes from 'prop-types';
import { ChevRIcon } from '../Icons';

function Topbar( { crumb = ['Bogofy'], actions } ) {
	return (
		<div className="bogo-topbar">
			<div className="bogo-breadcrumb">
				{crumb.map( ( c, i ) => (
					<React.Fragment key={i}>
						{i > 0 && <ChevRIcon size={12} stroke="var(--muted-2)"/>}
						{i === crumb.length - 1 ? <b>{c}</b> : <span>{c}</span>}
					</React.Fragment>
				) )}
			</div>
			<div className="bogo-fill"/>
			{actions && (
				<div className="bogo-row" style={{ gap: 8, flexShrink: 0 }}>
					{actions}
				</div>
			)}
		</div>
	);
}

Topbar.propTypes = {
	crumb: PropTypes.arrayOf( PropTypes.string ),
	actions: PropTypes.node,
};

export default Topbar;
