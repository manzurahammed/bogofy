import React from 'react';
import clsx from 'clsx';

const menuItems = [
  {
    id: 'dashboard',
    label: 'Dashboard',
    icon: (
      <svg className="bogo-w-5 bogo-h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
      </svg>
    ),
  },
  {
    id: 'rules',
    label: 'BOGO Rules',
    icon: (
      <svg className="bogo-w-5 bogo-h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z" />
      </svg>
    ),
  },
  {
    id: 'settings',
    label: 'Settings',
    icon: (
      <svg className="bogo-w-5 bogo-h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
      </svg>
    ),
  },
];

function Sidebar() {
  const params = new URLSearchParams(window.location.search);
  const currentTab = params.get('tab') || 'dashboard';

  const navigateTo = (tab) => {
    window.location.href = `admin.php?page=buy-one-get-one&tab=${tab}`;
  };

  return (
    <aside className="bogo-w-64 bogo-bg-white bogo-border-r bogo-border-gray-200 bogo-flex bogo-flex-col">
      <div className="bogo-p-6 bogo-border-b bogo-border-gray-200">
        <h1 className="bogo-text-xl bogo-font-bold bogo-text-gray-900">
          Buy One Get One
        </h1>
        <p className="bogo-text-sm bogo-text-gray-500 bogo-mt-1">
          BOGO Deals Manager
        </p>
      </div>

      <nav className="bogo-flex-1 bogo-p-4">
        <ul className="bogo-space-y-1">
          {menuItems.map((item) => (
            <li key={item.id}>
              <button
                onClick={() => navigateTo(item.id)}
                className={clsx(
                  'bogo-w-full bogo-flex bogo-items-center bogo-gap-3 bogo-px-3 bogo-py-2 bogo-rounded-md bogo-text-sm bogo-font-medium bogo-transition-colors',
                  currentTab === item.id
                    ? 'bogo-bg-primary-50 bogo-text-primary-700'
                    : 'bogo-text-gray-700 hover:bogo-bg-gray-100'
                )}
              >
                {item.icon}
                {item.label}
              </button>
            </li>
          ))}
        </ul>
      </nav>

      <div className="bogo-p-4 bogo-border-t bogo-border-gray-200">
        <p className="bogo-text-xs bogo-text-gray-500 bogo-text-center">
          Version 1.0.0
        </p>
      </div>
    </aside>
  );
}

export default Sidebar;
