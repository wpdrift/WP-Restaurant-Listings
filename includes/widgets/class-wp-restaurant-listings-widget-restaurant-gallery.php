<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Restaurant photo gallery widget.
 *
 * @package RestaurantListings
 */
class WP_Restaurant_Listings_Widget_Restaurant_Gallery extends WP_Restaurant_Listings_Widget {

    public function __construct() {
        $this->widget_cssclass    = 'restaurant_listings widget_restaurant_gallery';
        $this->widget_description = __( 'Display the restaurant photo gallery.', 'wp-restaurant-listings' );
        $this->widget_id          = 'widget_restaurant_gallery';
        $this->widget_name        = __( 'Restaurant Photo Gallery', 'wp-restaurant-listings' );
        $this->settings           = array(
            'title' => array(
                'type'  => 'text',
                'std'   => 'Photo Gallery',
                'label' => __( 'Title:', 'wp-restaurant-listings' )
            )
        );

        parent::__construct();
    }

    function widget( $args, $instance ) {
        global $post;

        if ( !is_singular('restaurant_listings' ) ) {
            return;
        }

        extract( $args );

        $title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
        $restaurant_image_gallery = get_post_meta( $post->ID, '_restaurant_image_gallery', true );
        $attachments         = array_filter( explode( ',', $restaurant_image_gallery ) );

        ob_start();

        echo $before_widget;

        if ( $title ) echo $before_title . sprintf( '<a  class="image-gallery-link">%s</a>', $title ) . $after_title;

        ?>
        <ul class="restaurant-gallery-images">
            <?php if ( empty( $attachments ) ) : ?>
                <li class="gallery-no-images">
                    <?php _e( 'No images found.', 'wp-restaurant-listings' ); ?>
                </li>
            <?php else : ?>
                <?php foreach ( $attachments as $attachment_id ): ?>
                <?php $attachment = wp_get_attachment_image_src( $attachment_id, 'thumbnail' ); ?>
                    <?php $full = wp_get_attachment_image_src( $attachment_id, 'full' ); ?>
                    <?php
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
                        <a href="<?php echo esc_url( $full[ 0] ); ?>" class="restaurant-gallery__item-trigger" <?php echo $attributes ?>>
                        <?php echo wp_get_attachment_image( $attachment_id, 'thumbnail', false, $attributes ) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>

        </ul>
        <?php

        get_restaurant_listings_template_part( 'restaurant-gallery', 'photoswipe' );

        echo $after_widget;

        $content = ob_get_clean();

        echo apply_filters( $this->widget_id, $content );
    }
}


//register_widget('WP_Restaurant_Listings_Widget_Restaurant_Gallery' );
