import React from 'react';
import PropTypes from 'prop-types';
import clsx from 'clsx';

export function Toggle({ checked, onChange, disabled = false, label = '' }) {
  return (
    <label className="bogo-inline-flex bogo-items-center bogo-cursor-pointer">
      <button
        type="button"
        role="switch"
        aria-checked={checked}
        disabled={disabled}
        onClick={() => onChange(!checked)}
        className={clsx(
          'bogo-relative bogo-inline-flex bogo-h-6 bogo-w-11 bogo-flex-shrink-0 bogo-rounded-full bogo-border-2 bogo-border-transparent bogo-transition-colors bogo-duration-200 bogo-ease-in-out focus:bogo-outline-none focus:bogo-ring-2 focus:bogo-ring-primary-500 focus:bogo-ring-offset-2',
          {
            'bogo-bg-primary-600': checked,
            'bogo-bg-gray-200': !checked,
            'bogo-opacity-50 bogo-cursor-not-allowed': disabled,
          }
        )}
      >
        <span
          className={clsx(
            'bogo-pointer-events-none bogo-inline-block bogo-h-5 bogo-w-5 bogo-transform bogo-rounded-full bogo-bg-white bogo-shadow bogo-ring-0 bogo-transition bogo-duration-200 bogo-ease-in-out',
            {
              'bogo-translate-x-5': checked,
              'bogo-translate-x-0': !checked,
            }
          )}
        />
      </button>
      {label && (
        <span className="bogo-ml-3 bogo-text-sm bogo-text-gray-700">{label}</span>
      )}
    </label>
  );
}

Toggle.propTypes = {
  checked: PropTypes.bool.isRequired,
  onChange: PropTypes.func.isRequired,
  disabled: PropTypes.bool,
  label: PropTypes.string,
};

export default Toggle;
