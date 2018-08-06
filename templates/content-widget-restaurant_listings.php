<?php
/**
 * Single restaurant listing widget content.
 *
 * This template can be overridden by copying it to yourtheme/restaurant_listings/content-widget-restaurant_listings.php.
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
<li <?php restaurant_listings_class(); ?>>
	<a href="<?php the_restaurant_permalink(); ?>">
		<div class="name">
			<h3><?php the_restaurant_title(); ?></h3>
		</div>
		<ul class="meta">
			<li class="location"><?php the_restaurant_location( false ); ?></li>
			<li class="restaurant"><?php the_restaurant_name(); ?></li>
			<?php if ( get_option( 'restaurant_listings_enable_types' ) ) { ?>
				<?php $types = get_the_restaurant_types(); ?>
				<?php if ( ! empty( $types ) ) : foreach ( $types as $type ) : ?>
					<li class="restaurant-type <?php echo esc_attr( sanitize_title( $type->slug ) ); ?>"><?php echo esc_html( $type->name ); ?></li>
				<?php endforeach; endif; ?>
			<?php } ?>
		</ul>
	</a>
</li>
