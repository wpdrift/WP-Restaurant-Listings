<?php
/**
 * Plugin Name: WP Restaurant Listings
 * Plugin URI: http://restaurants.wpdrift.com/
 * Description: Manage restaurant listings from the WordPress admin panel, and allow users to post restaurants directly to your site.
 * Version: 1.0.2
 * Author: WPdrift
 * Author URI: https://wpdrift.com/
 *
 * Text Domain: wp-restaurant-listings
 * Domain Path: /languages/
 *
 * @package RestaurantListings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define WPRL_PLUGIN_FILE.
if ( ! defined( 'WPRL_PLUGIN_FILE' ) ) {
	define( 'WPRL_PLUGIN_FILE', __FILE__ );
	define( 'RESTAURANT_LISTING_PLUGIN_DIR', untrailingslashit( plugin_dir_path( WPRL_PLUGIN_FILE ) ) );
	define( 'RESTAURANT_LISTING_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( WPRL_PLUGIN_FILE ) ), basename( WPRL_PLUGIN_FILE ) ) ) );
}


// Include the main WP_Restaurant_Listings class.
if ( ! class_exists( 'WP_Restaurant_Listings' ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-wp-restaurant-listings.php';
}

/**
 * Main instance of WP_Restaurant_Listings.
 *
 * Returns the main instance of WPRL to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return WP_Restaurant_Listings
 */
function wprl() {
	return WP_Restaurant_Listings::instance();
}

// Global for backwards compatibility.
$GLOBALS['restaurant_listings'] = wprl();
