import React, { createContext, useContext, useState, useCallback } from 'react';
import PropTypes from 'prop-types';

const NotificationContext = createContext( null );

/**
 * Notification provider component.
 */
export function NotificationProvider( { children } ) {
	const [notifications, setNotifications] = useState( [] );
	
	const addNotification = useCallback( ( notification ) => {
		const id              = Date.now();
		const newNotification = {
			id,
			type: 'info',
			duration: 5000,
			...notification,
		};
		
		setNotifications( ( prev ) => [...prev, newNotification] );
		
		// Auto-remove notification
		if ( newNotification.duration > 0 ) {
			setTimeout( () => {
				removeNotification( id );
			}, newNotification.duration );
		}
		
		return id;
	}, [] );
	
	const removeNotification = useCallback( ( id ) => {
		setNotifications( ( prev ) => prev.filter( ( n ) => n.id !== id ) );
	}, [] );
	
	const success = useCallback(
		( message, options = {} ) => {
			return addNotification( { ...options, message, type: 'success' } );
		},
		[addNotification]
	);
	
	const error = useCallback(
		( message, options = {} ) => {
			return addNotification( { ...options, message, type: 'error' } );
		},
		[addNotification]
	);
	
	const warning = useCallback(
		( message, options = {} ) => {
			return addNotification( { ...options, message, type: 'warning' } );
		},
		[addNotification]
	);
	
	const info = useCallback(
		( message, options = {} ) => {
			return addNotification( { ...options, message, type: 'info' } );
		},
		[addNotification]
	);
	
	const value = {
		notifications,
		addNotification,
		removeNotification,
		success,
		error,
		warning,
		info,
	};
	
	return (
		<NotificationContext.Provider value={value}>
			{children}
		</NotificationContext.Provider>
	);
}

NotificationProvider.propTypes = {
	children: PropTypes.node.isRequired,
};

/**
 * Hook for using notifications.
 *
 * @returns {Object}
 */
export function useNotification() {
	const context = useContext( NotificationContext );
	
	if ( !context ) {
		throw new Error( 'useNotification must be used within NotificationProvider' );
	}
	
	return context;
}

export default useNotification;
