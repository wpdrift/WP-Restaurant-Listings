<?php
/**
 * Single view Restaurant information box
 *
 * Hooked into single_restaurant_listings_start priority 30
 *
 * This template can be overridden by copying it to yourtheme/restaurant_listings/content-single-restaurant_listings-restaurant.php.
 *
 * @see         https://wpdrift.com/document/template-overrides/
 * @author      WPdrift
 * @package     WP Restaurant Listings
 * @category    Template
 * @since       1.0.0
 * @version     1.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! get_the_restaurant_name() ) {
	return;
}
?>
<div class="restaurant" itemscope itemtype="http://data-vocabulary.org/Organization">
	<?php the_restaurant_logo(); ?>

	<p class="name">
		<?php if ( $website = get_the_restaurant_website() ) : ?>
			<a class="website" href="<?php echo esc_url( $website ); ?>" itemprop="url" target="_blank" rel="nofollow"><?php _e( 'Website', 'wp-restaurant-listings' ); ?></a>
		<?php endif; ?>
		<?php the_restaurant_twitter(); ?>
		<?php the_restaurant_name( '<strong itemprop="name">', '</strong>' ); ?>
	</p>
	<?php the_restaurant_tagline( '<p class="tagline">', '</p>' ); ?>
	<?php the_restaurant_video(); ?>
</div>
