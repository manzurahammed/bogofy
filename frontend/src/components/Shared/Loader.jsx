import React from 'react';
import PropTypes from 'prop-types';
import clsx from 'clsx';

export function Loader({ size = 'md', className = '' }) {
  const sizeClasses = {
    sm: 'bogo-w-4 bogo-h-4',
    md: 'bogo-w-8 bogo-h-8',
    lg: 'bogo-w-12 bogo-h-12',
  };

  return (
    <div className={clsx('bogo-flex bogo-items-center bogo-justify-center', className)}>
      <svg
        className={clsx('bogo-animate-spin bogo-text-primary-600', sizeClasses[size])}
        fill="none"
        viewBox="0 0 24 24"
      >
        <circle
          className="bogo-opacity-25"
          cx="12"
          cy="12"
          r="10"
          stroke="currentColor"
          strokeWidth="4"
        />
        <path
          className="bogo-opacity-75"
          fill="currentColor"
          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
        />
      </svg>
    </div>
  );
}

Loader.propTypes = {
  size: PropTypes.oneOf(['sm', 'md', 'lg']),
  className: PropTypes.string,
};

export function PageLoader() {
  return (
    <div className="bogo-flex bogo-items-center bogo-justify-center bogo-min-h-96">
      <Loader size="lg" />
    </div>
  );
}

export function TableSkeleton({ rows = 5, cols = 4 }) {
  return (
    <div className="bogo-animate-pulse">
      <div className="bogo-space-y-3">
        {[...Array(rows)].map((_, i) => (
          <div key={i} className="bogo-flex bogo-gap-4">
            {[...Array(cols)].map((_, j) => (
              <div
                key={j}
                className="bogo-h-4 bogo-bg-gray-200 bogo-rounded bogo-flex-1"
              />
            ))}
          </div>
        ))}
      </div>
    </div>
  );
}

TableSkeleton.propTypes = {
  rows: PropTypes.number,
  cols: PropTypes.number,
};

export default Loader;
