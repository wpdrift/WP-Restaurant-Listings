<?php
/**
 * Notice when no restaurants were found in `[restaurants]` shortcode.
 *
 * This template can be overridden by copying it to yourtheme/restaurant_listings/content-no-restaurants-found.php.
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
<?php if ( defined( 'DOING_AJAX' ) ) : ?>
	<li class="no_restaurant_listings_found"><?php _e( 'There are no listings matching your search.', 'wp-restaurant-listings' ); ?></li>
<?php else : ?>
	<p class="no_restaurant_listings_found"><?php _e( 'There are currently no restaurants.', 'wp-restaurant-listings' ); ?></p>
<?php endif; ?>
