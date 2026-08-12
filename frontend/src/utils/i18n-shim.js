/**
 * Lightweight i18n shim.
 *
 * Proxies to WordPress's runtime i18n (window.wp.i18n), which is loaded via the
 * `wp-i18n` script dependency and populated by wp_set_script_translations().
 * `@wordpress/i18n` is aliased to this file in vite.config.js so components can
 * use the idiomatic `import { __ } from '@wordpress/i18n'` without bundling a
 * second copy of the library.
 *
 * Falls back to identity behaviour if wp.i18n is unavailable so the UI still
 * renders (untranslated) in any environment.
 */
const wpI18n =
	( typeof window !== 'undefined' && window.wp && window.wp.i18n ) || null;

export const __ = wpI18n ? wpI18n.__ : ( text ) => text;
export const _x = wpI18n ? wpI18n._x : ( text ) => text;
export const _n = wpI18n
	? wpI18n._n
	: ( single, plural, number ) => ( 1 === number ? single : plural );
export const _nx = wpI18n
	? wpI18n._nx
	: ( single, plural, number ) => ( 1 === number ? single : plural );
export const sprintf = wpI18n ? wpI18n.sprintf : ( format ) => format;
export const isRTL = wpI18n ? wpI18n.isRTL : () => false;
