<?php
/**
 * Restaurants overview
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

global $post;
?>
<div id="restaurants-overview" class="restaurants-overview">
    <div class="overview-content-col">
        <div class="rest-overview-group">
            <h2><?php _e('Phone Number', 'wp-restaurant-listings') ?></h2>
            <?php the_restaurant_phone() ?>
        </div>
        <div class="rest-overview-group">
            <h2><?php _e('Cuisines', 'wp-restaurant-listings' ) ?></h2>
            <?php the_restaurant_category() ?>

        </div>
        <div class="rest-overview-group">
            <h2><?php _e('Price Range', 'wp-restaurant-listings' ) ?></h2>
            <?php the_restaurant_price_range() ?>
        </div>
    </div>
    <div class="overview-content-col">
        <div class="rest-overview-group restaurant-opening-hours">
            <h2><?php _e('Opening Hours', 'wp-restaurant-listings' ) ?></h2>
            <?php the_restaurant_opening_hours() ?>
        </div>
    </div>
    <div class="overview-content-col">
        <div class="rest-overview-group">
            <h2><?php _e( 'Address', 'wp-restaurant-listings' ) ?></h2>
            <?php the_restaurant_location(false) ?>
            <div class="rest-map-canvas">
                <a href="<?php echo get_the_restaurant_direction_link(); ?>" target="_blank">
                <img src="<?php echo RESTAURANT_LISTING_PLUGIN_URL . '/assets/images/map-small.gif'?>" alt="">
                <?php _e('Get Direction', 'wp-restaurant-listings') ?>
                </a>
            </div>
        </div>
    </div>
</div>