<?php
/**
 * Single restaurant listing.
 *
 * This template can be overridden by copying it to yourtheme/restaurant_listings/content-single-restaurant_listings.php.
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

global $post;
?>
<div class="single_restaurant_listing" itemscope itemtype="http://schema.org/RestaurantPosting">
	<meta itemprop="title" content="<?php echo esc_attr( get_the_restaurant_title( $post ) ); ?>" />

    <?php
        /**
         * single_restaurant_listings_start hook
         *
         * @hooked restaurant_listings_meta_display - 20
         * @hooked restaurant_listings_restaurant_display - 30
         */
        do_action( 'single_restaurant_listings_start' );
    ?>

    <div class="restaurant_description" itemprop="description">
        <?php echo apply_filters( 'the_restaurant_description', get_the_content() ); ?>
    </div>

    <?php
        /**
         * single_restaurant_listings_end hook
         */
        do_action( 'single_restaurant_listings_end' );
    ?>

</div>
