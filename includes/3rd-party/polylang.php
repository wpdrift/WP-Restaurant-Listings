<?php
/**
 * Only load these if Polylang plugin is installed and active.
 */

/**
 * Load routines only if Polylang is loaded.
 *
 * @since 1.26.0
 */
function polylang_wprl_init() {
	add_filter( 'wprl_lang', 'polylang_wprl_get_restaurant_listings_lang' );
	add_filter( 'wprl_page_id', 'polylang_wprl_page_id' );
}
add_action( 'pll_init', 'polylang_wprl_init' );

/**
 * Returns Polylang's current language.
 *
 * @since 1.26.0
 *
 * @param string $lang
 * @return string
 */
function polylang_wprl_get_restaurant_listings_lang( $lang ) {
	if ( function_exists( 'pll_current_language' )
	     && function_exists( 'pll_is_translated_post_type' )
	     && pll_is_translated_post_type( 'restaurant_listings' ) ) {
		return pll_current_language();
	}
	return $lang;
}

/**
 * Returns the page ID for the current language.
 *
 * @since 1.26.0
 *
 * @param int $page_id
 * @return int
 */
function polylang_wprl_page_id( $page_id ) {
	if ( function_exists( 'pll_get_post' ) ) {
		$page_id = pll_get_post( $page_id );
	}
	return absint( $page_id );
}

