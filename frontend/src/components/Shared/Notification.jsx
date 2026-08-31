import React from "react";
import clsx from "clsx";
import { useNotification } from "../../hooks/useNotification";

const icons = {
  success: (
    <svg
      className="bogo-w-5 bogo-h-5 bogo-text-success-500"
      fill="currentColor"
      viewBox="0 0 20 20"
    >
      <path
        fillRule="evenodd"
        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
        clipRule="evenodd"
      />
    </svg>
  ),
  error: (
    <svg
      className="bogo-w-5 bogo-h-5 bogo-text-danger-500"
      fill="currentColor"
      viewBox="0 0 20 20"
    >
      <path
        fillRule="evenodd"
        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
        clipRule="evenodd"
      />
    </svg>
  ),
  warning: (
    <svg
      className="bogo-w-5 bogo-h-5 bogo-text-warning-500"
      fill="currentColor"
      viewBox="0 0 20 20"
    >
      <path
        fillRule="evenodd"
        d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
        clipRule="evenodd"
      />
    </svg>
  ),
  info: (
    <svg
      className="bogo-w-5 bogo-h-5 bogo-text-primary-500"
      fill="currentColor"
      viewBox="0 0 20 20"
    >
      <path
        fillRule="evenodd"
        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
        clipRule="evenodd"
      />
    </svg>
  ),
};

export function Notification() {
  const { notifications, removeNotification } = useNotification();

  if (notifications.length === 0) return null;

  return (
    <div className="bogo-fixed bogo-top-4 bogo-right-4 bogo-z-50 bogo-space-y-2">
      {notifications.map((notification) => (
        <div
          key={notification.id}
          className={clsx(
            "bogo-flex bogo-items-start bogo-gap-3 bogo-p-4 bogo-rounded-lg bogo-shadow-lg bogo-max-w-sm bogo-animate-slide-in",
            {
              "bogo-bg-success-50 bogo-border bogo-border-success-500":
                notification.type === "success",
              "bogo-bg-danger-50 bogo-border bogo-border-danger-500":
                notification.type === "error",
              "bogo-bg-warning-50 bogo-border bogo-border-warning-500":
                notification.type === "warning",
              "bogo-bg-primary-50 bogo-border bogo-border-primary-500":
                notification.type === "info",
            },
          )}
        >
          <div className="bogo-flex-shrink-0">{icons[notification.type]}</div>
          <div className="bogo-flex-1">
            <p className="bogo-text-sm bogo-font-medium bogo-text-gray-900">
              {notification.message}
            </p>
          </div>
          <button
            onClick={() => removeNotification(notification.id)}
            className="bogo-flex-shrink-0 bogo-text-gray-400 hover:bogo-text-gray-600"
          >
            <svg
              className="bogo-w-4 bogo-h-4"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path
                fillRule="evenodd"
                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                clipRule="evenodd"
              />
            </svg>
          </button>
        </div>
      ))}
    </div>
  );
}

export default Notification;
