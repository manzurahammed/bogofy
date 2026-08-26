/**
 * API wrapper with WordPress nonce authentication.
 */

const API_BASE = window.bogoAdmin?.apiUrl || '/wp-json/bogofy/v1';
const NONCE    = window.bogoAdmin?.nonce || '';

/**
 * Make API request.
 *
 * @param {string} endpoint - API endpoint.
 * @param {Object} options - Fetch options.
 * @returns {Promise<any>}
 */
async function apiRequest( endpoint, options = {} ) {
	const url = `${API_BASE}${endpoint}`;
	
	const headers = {
		'Content-Type': 'application/json',
		'X-WP-Nonce': NONCE,
		...options.headers,
	};
	
	const config = {
		...options,
		headers,
	};
	
	const response = await fetch( url, config );
	
	// Handle 204 No Content
	if ( response.status === 204 ) {
		return null;
	}
	
	const data = await response.json();
	
	if ( ! response.ok ) {
		const error  = new Error( data.message || 'API request failed' );
		error.code   = data.code;
		error.status = response.status;
		throw error;
	}
	
	return data;
}

/**
 * GET request.
 *
 * @param {string} endpoint - API endpoint.
 * @param {Object} params - Query parameters.
 * @returns {Promise<any>}
 */
export async function get( endpoint, params = {} ) {
	const queryString = new URLSearchParams( params ).toString();
	const url         = queryString ? `${endpoint}?${queryString}` : endpoint;
	return apiRequest( url, { method: 'GET' } );
}

/**
 * POST request.
 *
 * @param {string} endpoint - API endpoint.
 * @param {Object} data - Request body.
 * @returns {Promise<any>}
 */
export async function post( endpoint, data = {} ) {
	return apiRequest( endpoint, {
		method: 'POST',
		body: JSON.stringify( data ),
	} );
}

/**
 * PUT request.
 *
 * @param {string} endpoint - API endpoint.
 * @param {Object} data - Request body.
 * @returns {Promise<any>}
 */
export async function put( endpoint, data = {} ) {
	return apiRequest( endpoint, {
		method: 'PUT',
		body: JSON.stringify( data ),
	} );
}

/**
 * PATCH request.
 *
 * @param {string} endpoint - API endpoint.
 * @param {Object} data - Request body.
 * @returns {Promise<any>}
 */
export async function patch( endpoint, data = {} ) {
	return apiRequest( endpoint, {
		method: 'PATCH',
		body: JSON.stringify( data ),
	} );
}

/**
 * DELETE request.
 *
 * @param {string} endpoint - API endpoint.
 * @returns {Promise<any>}
 */
export async function del( endpoint ) {
	return apiRequest( endpoint, { method: 'DELETE' } );
}

export default {
	get,
	post,
	put,
	patch,
	del,
};
