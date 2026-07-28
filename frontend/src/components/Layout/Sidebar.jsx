import React from 'react';
import {
  HomeIcon, TagIcon, WandIcon,
  SettingsIcon, HelpIcon,
} from '../Icons';

const navigate = (tab, extra = '') => {
  window.location.href = `admin.php?page=buy-one-get-one&tab=${tab}${extra}`;
};

function SbItem({ label, Icon, active, onClick }) {
  return (
    <button
      className={`bogo-sidebar__item${active ? ' bogo-sidebar__item--active' : ''}`}
      onClick={onClick}
    >
      <Icon size={16} />
      <span>{label}</span>
    </button>
  );
}

function Sidebar() {
  const params = new URLSearchParams(window.location.search);
  const currentTab = params.get('tab') || 'dashboard';
  const isCreating = params.get('action') === 'create';

  return (
    <aside className="bogo-sidebar">
      <div className="bogo-sidebar__brand">
        <div className="bogo-sidebar__mark">B</div>
        <div className="bogo-col">
          <div className="bogo-sidebar__name">Bogo</div>
          <div className="bogo-sidebar__tagline">v1.0</div>
        </div>
      </div>

      <SbItem label="Dashboard" Icon={HomeIcon} active={currentTab === 'dashboard'} onClick={() => navigate('dashboard')} />
      <SbItem label="BOGO Rules" Icon={TagIcon} active={currentTab === 'rules' && !isCreating} onClick={() => navigate('rules')} />
      <SbItem label="New rule" Icon={WandIcon} active={isCreating} onClick={() => navigate('rules', '&action=create')} />

      <div className="bogo-sidebar__section">System</div>
      <SbItem label="Settings" Icon={SettingsIcon} active={currentTab === 'settings'} onClick={() => navigate('settings')} />
      <SbItem label="Help &amp; docs" Icon={HelpIcon} active={currentTab === 'help'} onClick={() => navigate('help')} />
    </aside>
  );
}

export default Sidebar;
