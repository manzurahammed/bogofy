import React from 'react';

function Header() {
  const params = new URLSearchParams(window.location.search);
  const currentTab = params.get('tab') || 'dashboard';
  const action = params.get('action');

  const getTitle = () => {
    if (action === 'create') return 'Create New Rule';
    if (action === 'edit') return 'Edit Rule';

    switch (currentTab) {
      case 'rules':
        return 'BOGO Rules';
      case 'settings':
        return 'Settings';
      case 'dashboard':
      default:
        return 'Dashboard';
    }
  };

  const handleCreateRule = () => {
    window.location.href = 'admin.php?page=buy-one-get-one&tab=rules&action=create';
  };

  return (
    <header className="bogo-bg-white bogo-border-b bogo-border-gray-200 bogo-px-6 bogo-py-4">
      <div className="bogo-flex bogo-items-center bogo-justify-between">
        <h2 className="bogo-text-2xl bogo-font-semibold bogo-text-gray-900">
          {getTitle()}
        </h2>
        {currentTab === 'rules' && !action && (
          <button
            onClick={handleCreateRule}
            className="bogo-btn bogo-btn-primary"
          >
            <svg
              className="bogo-w-4 bogo-h-4 bogo-mr-2"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M12 4v16m8-8H4"
              />
            </svg>
            Create Rule
          </button>
        )}
      </div>
    </header>
  );
}

export default Header;
