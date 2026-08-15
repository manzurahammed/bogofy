import React from 'react';
import { __ } from '@wordpress/i18n';
import {
	HomeIcon, TagIcon, WandIcon,
	SettingsIcon, HelpIcon,
} from '../Icons';

const logoUrl = `${window.bogoAdmin?.pluginUrl || ''}assets/images/bogo-logo.svg`;

const navigate = ( tab, extra = '' ) => {
	window.location.href = `admin.php?page=bogofy&tab=${tab}${extra}`;
};

function SbItem( { label, Icon, active, onClick } ) {
	return (
		<button
			className={`bogo-sidebar__item${active ? ' bogo-sidebar__item--active' : ''}`}
			onClick={onClick}
		>
			<Icon size={16}/>
			<span>{label}</span>
		</button>
	);
}

function Sidebar() {
	const params     = new URLSearchParams( window.location.search );
	const currentTab = params.get( 'tab' ) || 'dashboard';
	const isCreating = params.get( 'action' ) === 'create';
	
	return (
		<aside className="bogo-sidebar">
			<div className="bogo-sidebar__brand">
				<div className="bogo-sidebar__mark">
					<img className="bogo-sidebar__logo" src={logoUrl} width={30} height={30} alt=""/>
				</div>
				<div className="bogo-col">
					<div className="bogo-sidebar__name">Bogofy</div>
				</div>
			</div>
			
			<SbItem label={__( 'Dashboard', 'bogofy' )} Icon={HomeIcon} active={currentTab === 'dashboard'}
			        onClick={() => navigate( 'dashboard' )}/>
			<SbItem label={__( 'Bogofy Rules', 'bogofy' )} Icon={TagIcon} active={currentTab === 'rules' && !isCreating}
			        onClick={() => navigate( 'rules' )}/>
			<SbItem label={__( 'New rule', 'bogofy' )} Icon={WandIcon} active={isCreating}
			        onClick={() => navigate( 'rules', '&action=create' )}/>

			<div className="bogo-sidebar__section">{__( 'System', 'bogofy' )}</div>
			<SbItem label={__( 'Settings', 'bogofy' )} Icon={SettingsIcon} active={currentTab === 'settings'}
			        onClick={() => navigate( 'settings' )}/>
			<SbItem label={__( 'Help & docs', 'bogofy' )} Icon={HelpIcon} active={currentTab === 'help'}
			        onClick={() => navigate( 'help' )}/>
		</aside>
	);
}

export default Sidebar;
