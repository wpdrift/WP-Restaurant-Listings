<?php
/**
 * Restaurant dashboard shortcode content if user is not logged in.
 *
 * This template can be overridden by copying it to yourtheme/restaurant_listings/restaurant-dashboard-login.php.
 *
 * @see         https://wpdrift.com/document/template-overrides/
 * @author      WPdrift
 * @package     WP Restaurant Listings
 * @category    Template
 * @version     1.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div id="restaurant-listings-restaurant-dashboard">

	<p class="account-sign-in"><?php _e( 'You need to be signed in to manage your listings.', 'wp-restaurant-listings' ); ?> <a class="button" href="<?php echo apply_filters( 'restaurant_listings_restaurant_dashboard_login_url', wp_login_url( get_permalink() ) ); ?>"><?php _e( 'Sign in', 'wp-restaurant-listings' ); ?></a></p>

</div>
