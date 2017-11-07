<?php global $restaurant_listing; ?>

<a href="<?php the_permalink(); ?>">
	<?php if ( get_option( 'restaurant_listings_enable_types' ) ) { ?>
		<?php $types = get_the_restaurant_types(); ?>
		<?php if ( ! empty( $types ) ) : foreach ( $types as $type ) : ?>

			<div class="restaurant-type <?php echo esc_attr( sanitize_title( $type->slug ) ); ?>"><?php echo esc_html( $type->name ); ?></div>

		<?php endforeach; endif; ?>
	<?php } ?>

	<?php if ( $logo = get_the_restaurant_logo() ) : ?>
		<img src="<?php echo esc_attr( $logo ); ?>" alt="<?php the_restaurant_name(); ?>" title="<?php the_restaurant_name(); ?> - <?php the_restaurant_tagline(); ?>" />
	<?php endif; ?>

    <?php echo get_the_restaurant_price_range() ?>

	<div class="restaurant_summary_content">

		<h1><?php the_restaurant_title(); ?></h1>

		<p class="meta"><?php the_restaurant_location( false ); ?> &mdash; <?php the_restaurant_publish_date(); ?></p>

	</div>
</a>
