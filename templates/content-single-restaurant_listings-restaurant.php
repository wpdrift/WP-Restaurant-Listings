<?php
/**
 * Single view Restaurant information box
 *
 * Hooked into single_restaurant_listings_start priority 30
 *
 * @since 1.0.0
 */

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
