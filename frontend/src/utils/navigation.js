/**
 * Admin navigation helpers for the Bogofy admin pages.
 */

/**
 * Build a URL to a Bogofy admin screen.
 *
 * @param {string} query - Extra query string (without leading `&`), e.g. `tab=rules`.
 * @returns {string}
 */
export function adminUrl( query = '' ) {
	return `admin.php?page=bogofy${ query ? `&${query}` : '' }`;
}

/**
 * Navigate the browser to a Bogofy admin screen.
 *
 * @param {string} query - Extra query string (without leading `&`).
 * @returns {void}
 */
export function goToAdmin( query = '' ) {
	window.location.href = adminUrl( query );
}
