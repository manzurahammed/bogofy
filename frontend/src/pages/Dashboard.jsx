import React from 'react';
import { useStats } from '../hooks/useSettings';
import { PageLoader } from '../components/Shared/Loader';

const statCards = [
  {
    key: 'active_rules',
    label: 'Active Rules',
    icon: (
      <svg className="bogo-w-6 bogo-h-6 bogo-text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
    ),
    color: 'success',
  },
  {
    key: 'total_rules',
    label: 'Total Rules',
    icon: (
      <svg className="bogo-w-6 bogo-h-6 bogo-text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z" />
      </svg>
    ),
    color: 'primary',
  },
  {
    key: 'bogo_orders',
    label: 'Orders with BOGO',
    icon: (
      <svg className="bogo-w-6 bogo-h-6 bogo-text-warning-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
      </svg>
    ),
    color: 'warning',
  },
  {
    key: 'total_discount',
    label: 'Total Discounts Given',
    icon: (
      <svg className="bogo-w-6 bogo-h-6 bogo-text-danger-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
    ),
    color: 'danger',
    format: 'currency',
  },
];

function Dashboard() {
  const { data: stats, isLoading, error } = useStats();

  if (isLoading) {
    return <PageLoader />;
  }

  if (error) {
    return (
      <div className="bogo-text-center bogo-py-12">
        <p className="bogo-text-danger-500">Failed to load stats. Please try again.</p>
      </div>
    );
  }

  const formatValue = (key, value, format) => {
    if (format === 'currency') {
      return `$${value.toFixed(2)}`;
    }
    return value;
  };

  return (
    <div className="bogo-space-y-6">
      {/* Stats Grid */}
      <div className="bogo-grid bogo-grid-cols-1 md:bogo-grid-cols-2 lg:bogo-grid-cols-4 bogo-gap-6">
        {statCards.map((card) => (
          <div key={card.key} className="bogo-card bogo-p-6">
            <div className="bogo-flex bogo-items-center bogo-justify-between">
              <div>
                <p className="bogo-text-sm bogo-font-medium bogo-text-gray-500">
                  {card.label}
                </p>
                <p className="bogo-text-3xl bogo-font-bold bogo-text-gray-900 bogo-mt-1">
                  {formatValue(card.key, stats?.[card.key] || 0, card.format)}
                </p>
              </div>
              <div className="bogo-p-3 bogo-bg-gray-50 bogo-rounded-full">
                {card.icon}
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* Quick Actions */}
      <div className="bogo-card bogo-p-6">
        <h3 className="bogo-text-lg bogo-font-semibold bogo-text-gray-900 bogo-mb-4">
          Quick Actions
        </h3>
        <div className="bogo-flex bogo-flex-wrap bogo-gap-4">
          <button
            onClick={() => {
              window.location.href = 'admin.php?page=buy-one-get-one&tab=rules&action=create';
            }}
            className="bogo-btn bogo-btn-primary"
          >
            <svg className="bogo-w-4 bogo-h-4 bogo-mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
            </svg>
            Create New Rule
          </button>
          <button
            onClick={() => {
              window.location.href = 'admin.php?page=buy-one-get-one&tab=rules';
            }}
            className="bogo-btn bogo-btn-secondary"
          >
            <svg className="bogo-w-4 bogo-h-4 bogo-mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 10h16M4 14h16M4 18h16" />
            </svg>
            View All Rules
          </button>
          <button
            onClick={() => {
              window.location.href = 'admin.php?page=buy-one-get-one&tab=settings';
            }}
            className="bogo-btn bogo-btn-secondary"
          >
            <svg className="bogo-w-4 bogo-h-4 bogo-mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            Settings
          </button>
        </div>
      </div>

      {/* Help Section */}
      <div className="bogo-card bogo-p-6">
        <h3 className="bogo-text-lg bogo-font-semibold bogo-text-gray-900 bogo-mb-4">
          Getting Started
        </h3>
        <div className="bogo-prose bogo-text-sm bogo-text-gray-600">
          <p>Welcome to Buy One Get One! Here are some tips to get started:</p>
          <ul className="bogo-mt-2 bogo-space-y-2">
            <li>
              <strong>Create your first rule</strong> - Click &quot;Create New Rule&quot; to set up a BOGO deal.
            </li>
            <li>
              <strong>Choose a rule type</strong> - Select from Buy X Get X, Buy X Get Y, or category-based rules.
            </li>
            <li>
              <strong>Schedule your deals</strong> - Set start and end dates for time-limited promotions.
            </li>
            <li>
              <strong>Configure settings</strong> - Customize labels and messages in the Settings page.
            </li>
          </ul>
        </div>
      </div>
    </div>
  );
}

export default Dashboard;
