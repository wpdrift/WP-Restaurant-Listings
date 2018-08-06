<?php
/**
 * Restaurant listings in the loop.
 *
 * @since 1.0.0
 * @version 1.27.0
 *
 * @package RestaurantListings
 * @category Template
 * @author Automattic
 */

global $post; ?>

<li <?php restaurant_listings_class(); ?> data-longitude="<?php echo esc_attr( $post->geolocation_lat ); ?>" data-latitude="<?php echo esc_attr( $post->geolocation_long ); ?>">
    <div class="restaurant-result">
        <div class="first-row">
            <div class="main-area">
                <div class="main-area-inner">
                    <div class="restaurant-avatar">
                        <?php the_restaurant_logo() ?>
                    </div>
                    <div class="restaurant-story">
                        <a class="restaurant-name" href="<?php the_permalink() ?>">
                            <span><?php the_restaurant_title() ?></span>
                        </a>
                        <?php the_restaurant_rating() ?>
                        <?php the_restaurant_price_range() ?>
                        <?php the_restaurant_category() ?>
                    </div>
                </div>
            </div>
            <div class="secondary-area">
                <span class="neighborhood-street">
                <?php the_restaurant_street() ?>
                </span>
                <address>
                    <?php the_restaurant_location() ?>
                </address>
            </div>
        </div>

        <div class="second-row">
            <?php the_restaurant_latest_story() ?>
        </div>
    </div>
</li>
