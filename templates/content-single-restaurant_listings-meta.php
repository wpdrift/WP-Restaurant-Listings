<?php
/**
 * Single view restaurant meta box
 *
 * Hooked into single_restaurant_listings_start priority 20
 *
 * This template can be overridden by copying it to yourtheme/restaurant_listings/content-single-restaurant_listings-meta.php.
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

global $post;

do_action( 'single_restaurant_listings_meta_before' ); ?>

<ul class="restaurant-listings-meta meta">
	<?php do_action( 'single_restaurant_listings_meta_start' ); ?>

	<?php if ( get_option( 'restaurant_listings_enable_types' ) ) { ?>
		<?php $types = get_the_restaurant_types(); ?>
		<?php if ( ! empty( $types ) ) : foreach ( $types as $type ) : ?>

			<li class="restaurant-type <?php echo esc_attr( sanitize_title( $type->slug ) ); ?>" itemprop="employmentType"><?php echo esc_html( $type->name ); ?></li>

		<?php endforeach; endif; ?>
	<?php } ?>

	<li class="location" itemprop="restaurantLocation"><?php the_restaurant_location(); ?></li>

	<?php do_action( 'single_restaurant_listings_meta_end' ); ?>
</ul>

<!-- Place somewhere in the <body> of your page -->
    <ul class="slides restaurant-gallery-images">
<?php
        $restaurant_image_gallery = get_post_meta( $post->ID, '_restaurant_image_gallery', true );
        $attachments         = array_filter( explode( ',', $restaurant_image_gallery ) );
        foreach ( $attachments as $attachment_id ):
            $full = wp_get_attachment_image_src( $attachment_id, 'full' );
            $attributes = array(
                'title'                   => get_post_field( 'post_title', $attachment_id ),
                'data-caption'            => get_post_field( 'post_excerpt', $attachment_id ),
                'data-src'                => $full[0],
                'data-large_image'        => $full[0],
                'data-large_image_width'  => $full[1],
                'data-large_image_height' => $full[2],
            );
?>
    <li class="gallery-preview-image">
        <a href="<?php echo esc_url( $full[ 0] ); ?>" class="restaurant-gallery__item-trigger">
        <?php echo wp_get_attachment_image( $attachment_id, 'thumbnail', false, $attributes ) ?>
        </a>
    </li>
<?php
        endforeach;
?>
        <!-- items mirrored twice, total of 12 -->
    </ul>

<?php do_action( 'single_restaurant_listings_meta_after' ); ?>
