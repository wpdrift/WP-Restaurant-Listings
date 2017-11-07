<?php global $post; ?>
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
