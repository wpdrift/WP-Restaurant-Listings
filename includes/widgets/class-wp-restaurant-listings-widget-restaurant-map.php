<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Restaurant map widget.
 *
 * @package RestaurantListings
 * @since 1.0.0
 */
class WP_Restaurant_Listings_Widget_Restaurant_Map extends WP_Restaurant_Listings_Widget {

    public function __construct() {
        $this->widget_cssclass    = 'restaurant_listings widget_restaurant_map';
        $this->widget_description = __( 'Display the listings location and contact details.', 'wp-restaurant-listings' );
        $this->widget_id          = 'widget_restaurant_map';
        $this->widget_name        = __( 'Restaurant Map & Contact Details', 'wp-restaurant-listings' );
        $this->settings           = array(
            'map' => array(
                'type'  => 'checkbox',
                'std'   => 1,
                'label' => __( 'Display Map', 'wp-restaurant-listings' )
            ),
            'address' => array(
                'type'  => 'checkbox',
                'std'   => 1,
                'label' => __( 'Display Address', 'wp-restaurant-listings' )
            ),
            'phone' => array(
                'type'  => 'checkbox',
                'std'   => 1,
                'label' => __( 'Display Phone Number', 'wp-restaurant-listings' )
            ),
            'email' => array(
                'type'  => 'checkbox',
                'std'   => 1,
                'label' => __( 'Display Email', 'wp-restaurant-listings' )
            ),
            'web' => array(
                'type'  => 'checkbox',
                'std'   => 1,
                'label' => __( 'Display Website', 'wp-restaurant-listings' )
            ),
            'directions' => array(
                'type'  => 'checkbox',
                'std'   => 1,
                'label' => __( 'Display "Get Directions"', 'wp-restaurant-listings' )
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

        $lat    = get_post_meta( $post->ID, 'geolocation_lat', true );
        $long   = get_post_meta( $post->ID, 'geolocation_long', true );

        // Bail out if latitude or longitude is not found
        if ( !$lat || !$long ) {
            return;
        }

        wp_enqueue_style('mapbox-gl' );
        wp_enqueue_script( 'mapbox-gl' );
        wp_enqueue_style('mapbox-gl-geocoder');
        wp_enqueue_script( 'mapbox-gl-geocoder');

        echo $before_widget;

        ?>

        <div class="map-widget-sections">

            <?php if ( !empty( $instance['map'] ) && !empty( $lat ) && !empty( $long )  ) : ?>
                <div class="map-widget-section map-widget-section--split">
                    <div id="restaurant-map"></div>
                </div>

                <script>

                   jQuery(document).on( 'ready', function() {
                       mapboxgl.accessToken = 'pk.eyJ1IjoieWFwYXJlc2h5YSIsImEiOiJjajczMXpyMHUwM2toMnFxb2tia2loMmt4In0.pnKEX20sssxm5cY3_RwwoA';
                       // This adds the map to your page
                       var map = new mapboxgl.Map({
                           container: 'restaurant-map',
                           style: 'mapbox://styles/mapbox/streets-v9',
                           zoom: 12,
                           center: [<?php echo $long ?>, <?php echo $lat ?>]
                       });

                       var el = document.createElement('div'); // Create an img element for the marker;
                       el.className = 'marker';
                       // Add markers to the map at all points
                       new mapboxgl.Marker(el, {offset: [-28, -46]})
                           .setLngLat([<?php echo $long ?>, <?php echo $lat ?>])
                           .addTo(map);

                   });

                </script>

            <?php endif; ?>


                <div class="map-widget-section map-widget-section--split">
                    <?php

                    if ( ! empty( $instance['address'] ) ) :
                        $this->the_restaurant_location();
                    endif;

                    if ( ! empty( $instance['phone'] ) ) :
                        the_restaurant_phone();
                    endif;

                    if ( ! empty( $instance['email'] ) ) :
                        the_restaurant_email();
                    endif;

                    if ( ! empty( $instance['web'] ) ) :
                        the_restaurant_url();
                    endif;

                    if ( ! empty( $instance['directions'] ) ) :
                        the_restaurant_directions();
                    endif;

                    ?>
                </div>
        </div>



        <?php
        echo $after_widget;

        $content = ob_get_clean();
        echo apply_filters( $this->widget_id, $content );
    }

    /**
     * The restaurant location to show in map widget
     */
    public function the_restaurant_location() {
        ?>

        <div class="restaurant_listings-author-location">
            <?php the_restaurant_location() ?>
        </div>

        <?php
    }
}

register_widget('WP_Restaurant_Listings_Widget_Restaurant_Map' );
