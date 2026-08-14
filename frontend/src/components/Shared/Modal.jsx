import React, { useEffect } from 'react';
import PropTypes from 'prop-types';
import clsx from 'clsx';
import { __ } from '@wordpress/i18n';

export function Modal({ isOpen, onClose, title, children, size = 'md' }) {
  useEffect(() => {
    const handleEsc = (e) => {
      if (e.key === 'Escape') {
        onClose();
      }
    };

    if (isOpen) {
      document.addEventListener('keydown', handleEsc);
      document.body.style.overflow = 'hidden';
    }

    return () => {
      document.removeEventListener('keydown', handleEsc);
      document.body.style.overflow = 'unset';
    };
  }, [isOpen, onClose]);

  if (!isOpen) return null;

  const sizeClasses = {
    sm: 'bogo-max-w-md',
    md: 'bogo-max-w-lg',
    lg: 'bogo-max-w-2xl',
    xl: 'bogo-max-w-4xl',
  };

  return (
    <div className="bogo-fixed bogo-inset-0 bogo-z-50 bogo-overflow-y-auto">
      <div className="bogo-flex bogo-items-center bogo-justify-center bogo-min-h-screen bogo-px-4 bogo-pt-4 bogo-pb-20 bogo-text-center">
        {/* Backdrop */}
        <div
          className="bogo-fixed bogo-inset-0 bogo-bg-gray-500 bogo-bg-opacity-75 bogo-transition-opacity"
          onClick={onClose}
        />

        {/* Modal panel */}
        <div
          className={clsx(
            'bogo-relative bogo-inline-block bogo-align-bottom bogo-bg-white bogo-rounded-lg bogo-text-left bogo-overflow-hidden bogo-shadow-xl bogo-transform bogo-transition-all bogo-w-full',
            sizeClasses[size]
          )}
        >
          {/* Header */}
          <div className="bogo-flex bogo-items-center bogo-justify-between bogo-px-6 bogo-py-4 bogo-border-b bogo-border-gray-200">
            <h3 className="bogo-text-lg bogo-font-semibold bogo-text-gray-900">
              {title}
            </h3>
            <button
              onClick={onClose}
              className="bogo-text-gray-400 hover:bogo-text-gray-600 focus:bogo-outline-none"
            >
              <svg className="bogo-w-6 bogo-h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          {/* Content */}
          <div className="bogo-px-6 bogo-py-4">{children}</div>
        </div>
      </div>
    </div>
  );
}

Modal.propTypes = {
  isOpen: PropTypes.bool.isRequired,
  onClose: PropTypes.func.isRequired,
  title: PropTypes.string.isRequired,
  children: PropTypes.node.isRequired,
  size: PropTypes.oneOf(['sm', 'md', 'lg', 'xl']),
};

export function ConfirmModal({ isOpen, onClose, onConfirm, title, message, confirmText = __('Confirm', 'bogofy'), cancelText = __('Cancel', 'bogofy'), variant = 'danger' }) {
  return (
    <Modal isOpen={isOpen} onClose={onClose} title={title} size="sm">
      <div className="bogo-space-y-4">
        <p className="bogo-text-sm bogo-text-gray-600">{message}</p>
        <div className="bogo-flex bogo-justify-end bogo-gap-3">
          <button onClick={onClose} className="bogo-btn bogo-btn-secondary">
            {cancelText}
          </button>
          <button
            onClick={onConfirm}
            className={clsx('bogo-btn', {
              'bogo-btn-danger': variant === 'danger',
              'bogo-btn-primary': variant === 'primary',
            })}
          >
            {confirmText}
          </button>
        </div>
      </div>
    </Modal>
  );
}

ConfirmModal.propTypes = {
  isOpen: PropTypes.bool.isRequired,
  onClose: PropTypes.func.isRequired,
  onConfirm: PropTypes.func.isRequired,
  title: PropTypes.string.isRequired,
  message: PropTypes.string.isRequired,
  confirmText: PropTypes.string,
  cancelText: PropTypes.string,
  variant: PropTypes.oneOf(['danger', 'primary']),
};

export default Modal;
