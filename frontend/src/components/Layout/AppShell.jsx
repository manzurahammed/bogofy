import React from 'react';
import PropTypes from 'prop-types';
import Sidebar from './Sidebar';
import Topbar from './Header';
import { Notification } from '../Shared/Notification';

function AppShell( { children, crumb = ['Bogo'], actions = null } ) {
	return (
		<div className="bogo-admin">
			<div className="bogo-app">
				<Sidebar/>
				<main className="bogo-main">
					<Topbar crumb={crumb} actions={actions}/>
					<div className="bogo-content">{children}</div>
				</main>
			</div>
			<Notification/>
		</div>
	);
}

AppShell.propTypes = {
	children: PropTypes.node.isRequired,
	crumb: PropTypes.arrayOf( PropTypes.string ),
	actions: PropTypes.node,
};

export default AppShell;
