<?php
/**
 * Deprecated functions. Do not use these.
 */

if ( ! function_exists( 'order_featured_restaurant_listing' ) ) :
/**
* Was used for sorting.
*
* @deprecated 1.22.4
* @param array $args
* @return array
*/
function order_featured_restaurant_listing( $args ) {
	global $wpdb;
	$args['orderby'] = "$wpdb->posts.menu_order ASC, $wpdb->posts.post_date DESC";
	return $args;
}
endif;



if ( ! function_exists( 'the_restaurant_type' ) ) :
/**
 * Displays the restaurant type for the listings.
 *
 * @since 1.0.0
 * @deprecated 1.27.0 Use `the_restaurant_types()` instead.
 *
 * @param int|WP_Post $post
 * @return string
 */
function the_restaurant_type( $post = null ) {
	_deprecated_function( __FUNCTION__, '1.27.0', 'the_restaurant_types' );

	if ( ! get_option( 'restaurant_listings_enable_types' ) ) {
		return '';
	}
	if ( $restaurant_type = get_the_restaurant_type( $post ) ) {
		echo $restaurant_type->name;
	}
}
endif;

if ( ! function_exists( 'get_the_restaurant_type' ) ) :
/**
 * Gets the restaurant type for the listings.
 *
 * @since 1.0.0
 * @deprecated 1.27.0 Use `get_the_restaurant_types()` instead.
 *
 * @param int|WP_Post $post (default: null)
 * @return string|bool|null
 */
function get_the_restaurant_type( $post = null ) {
	_deprecated_function( __FUNCTION__, '1.27.0', 'get_the_restaurant_types' );

	$post = get_post( $post );
	if ( $post->post_type !== 'restaurant_listings' ) {
		return;
	}

	$types = wp_get_post_terms( $post->ID, 'restaurant_listings_type' );

	if ( $types ) {
		$type = current( $types );
	} else {
		$type = false;
	}

	return apply_filters( 'the_restaurant_type', $type, $post );
}
endif;

if ( ! function_exists( 'wprl_get_permalink_structure' ) ) :
/**
 * Retrieves permalink settings. Moved to `WP_Restaurant_Listings_Post_Types` class in 1.27.1.
 *
 * @since 1.0.0
 * @deprecated 1.27.1
 * @return array
 */
function wprl_get_permalink_structure() {
	return WP_Restaurant_Listings_Post_Types::get_permalink_structure();
}
endif;
