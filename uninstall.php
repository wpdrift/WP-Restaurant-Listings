<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

wp_clear_scheduled_hook( 'restaurant_listings_delete_old_previews' );

wp_trash_post( get_option( 'restaurant_listings_submit_restaurant_form_page_id' ) );
wp_trash_post( get_option( 'restaurant_listings_restaurant_dashboard_page_id' ) );
wp_trash_post( get_option( 'restaurant_listings_restaurants_page_id' ) );

$options = array(
	'wp_restaurant_listings_version',
	'restaurant_listings_per_page',
	'restaurant_listings_enable_categories',
	'restaurant_listings_enable_default_category_multiselect',
	'restaurant_listings_category_filter_type',
	'restaurant_listings_user_requires_account',
	'restaurant_listings_enable_registration',
	'restaurant_listings_registration_role',
	'restaurant_listings_submission_requires_approval',
	'restaurant_listings_user_can_edit_pending_submissions',
	'restaurant_listings_submit_restaurant_form_page_id',
	'restaurant_listings_restaurant_dashboard_page_id',
	'restaurant_listings_restaurants_page_id',
	'restaurant_listings_installed_terms',
	'restaurant_listings_submit_page_slug',
	'restaurant_listings_restaurant_dashboard_page_slug',
	'restaurant_listings_google_maps_api_key',
);

foreach ( $options as $option ) {
	delete_option( $option );
}
