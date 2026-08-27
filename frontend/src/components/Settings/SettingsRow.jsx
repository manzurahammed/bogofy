import React from 'react';

/**
 * A single settings row: label + description on the left, a control on the right.
 *
 * @param {Object} props
 * @param {string} props.name     Row title.
 * @param {string} props.desc     Row description.
 * @param {React.ReactNode} props.children The control (toggle, input, …).
 */
function SettingsRow( { name, desc, children } ) {
	return (
		<div className="bogo-settings-list__row">
			<div className="bogo-col">
				<div className="bogo-settings-list__name">{name}</div>
				<div className="bogo-settings-list__desc">{desc}</div>
			</div>
			{children}
		</div>
	);
}

export default SettingsRow;
