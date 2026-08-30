import React from 'react';
import Dashboard from './pages/Dashboard';
import RulesPage from './pages/RulesPage';
import CreateRulePage from './pages/CreateRulePage';
import EditRulePage from './pages/EditRulePage';
import SettingsPage from './pages/SettingsPage';
import HelpPage from './pages/HelpPage';
import { NotificationProvider } from './hooks/useNotification';

function App() {
	const params = new URLSearchParams( window.location.search );
	const tab    = params.get( 'tab' ) || 'dashboard';
	const action = params.get( 'action' );
	const ruleId = params.get( 'rule_id' );
	
	const renderContent = () => {
		if ( action === 'create' ) {
			return <CreateRulePage/>;
		}
		
		if ( action === 'edit' && ruleId ) {
			return <EditRulePage ruleId={parseInt( ruleId, 10 )}/>;
		}
		
		switch ( tab ) {
			case 'rules':
				return <RulesPage/>;
			case 'settings':
				return <SettingsPage/>;
			case 'help':
				return <HelpPage/>;
			case 'dashboard':
			default:
				return <Dashboard/>;
		}
	};
	
	return (
		<NotificationProvider>
			{renderContent()}
		</NotificationProvider>
	);
}

export default App;
