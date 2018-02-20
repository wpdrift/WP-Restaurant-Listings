<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Restaurant hours widget.
 *
 * @package RestaurantListings
 * @since 1.0.0
 */
class WP_Restaurant_Listings_Restaurant_Hours extends WP_Restaurant_Listings_Widget {

    public function __construct() {
        $this->widget_cssclass    = 'restaurant_listings widget_restaurant_hours';
        $this->widget_description = __( 'Display the restaurant hours of the listings.', 'wp-restaurant-listings' );
        $this->widget_id          = 'widget_restaurant_hours';
        $this->widget_name        = __( 'Restaurant Hours', 'wp-restaurant-listings' );
        $this->settings           = array(
            'title' => array(
                'type'  => 'text',
                'std'   => __( 'Restaurant Hours', 'wp-restaurant-listings' ),
                'label' => __( 'Title:', 'wp-restaurant-listings' )
            )
        );

        parent::__construct();
    }

    function widget( $args, $instance ) {

        if ( !is_singular('restaurant_listings' ) ) {
            return;
        }

        extract( $args );

        $title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
        $icon = isset( $instance[ 'icon' ] ) ? $instance[ 'icon' ] : null;

        if ( $icon ) {
            if ( strpos( $icon, 'ion-' ) !== false ) {
                $before_title = sprintf( $before_title, $icon );
            } else {
                $before_title = sprintf( $before_title, 'ion-' . $icon );
            }
        }

        ob_start();

        echo $before_widget;

        if ( $title ) {
            echo $before_title . $title . $after_title;
        }

        the_restaurant_opening_hours();

        echo $after_widget;

        $content = ob_get_clean();

        echo apply_filters( $this->widget_id, $content );
    }
}


register_widget( 'WP_Restaurant_Listings_Restaurant_Hours' );
