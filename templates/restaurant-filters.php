<?php
/**
 * Filters in `[restaurants]` shortcode.
 *
 * This template can be overridden by copying it to yourtheme/restaurant_listings/restaurant-filters.php.
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

wp_enqueue_script( 'wp-restaurant-listings-ajax-filters' );

do_action( 'restaurant_listings_restaurant_filters_before', $atts );
?>

<form class="restaurant_filters">
	<?php do_action( 'restaurant_listings_restaurant_filters_start', $atts ); ?>

	<div class="search_restaurants">
		<?php do_action( 'restaurant_listings_restaurant_filters_search_restaurants_start', $atts ); ?>

		<div class="search_keywords">
			<label for="search_keywords"><?php _e( 'Keywords', 'wp-restaurant-listings' ); ?></label>
			<input type="text" name="search_keywords" id="search_keywords" placeholder="<?php esc_attr_e( 'Search for restaurants or cuisines...', 'wp-restaurant-listings' ); ?>" value="<?php echo esc_attr( $keywords ); ?>" />
		</div>

		<div class="search_location">
			<label for="search_location"><?php _e( 'Location', 'wp-restaurant-listings' ); ?></label>
			<input type="text" name="search_location" id="search_location" placeholder="<?php esc_attr_e( 'Location', 'wp-restaurant-listings' ); ?>" value="<?php echo esc_attr( $location ); ?>" />
		</div>

		<?php if ( $categories ) : ?>
			<?php foreach ( $categories as $category ) : ?>
				<input type="hidden" name="search_categories[]" value="<?php echo sanitize_title( $category ); ?>" />
			<?php endforeach; ?>
		<?php elseif ( $show_categories && ! is_tax( 'restaurant_listings_category' ) && get_terms( 'restaurant_listings_category' ) ) : ?>
			<div class="search_categories">
				<label for="search_categories"><?php _e( 'Category', 'wp-restaurant-listings' ); ?></label>
				<?php if ( $show_category_multiselect ) : ?>
					<?php restaurant_listings_dropdown_categories( array( 'taxonomy' => 'restaurant_listings_category', 'hierarchical' => 1, 'name' => 'search_categories', 'orderby' => 'name', 'selected' => $selected_category, 'hide_empty' => false ) ); ?>
				<?php else : ?>
					<?php restaurant_listings_dropdown_categories( array( 'taxonomy' => 'restaurant_listings_category', 'hierarchical' => 1, 'show_option_all' => __( 'Any category', 'wp-restaurant-listings' ), 'name' => 'search_categories', 'orderby' => 'name', 'selected' => $selected_category, 'multiple' => false ) ); ?>
				<?php endif; ?>
			</div>
		<?php endif; ?>

        <div class="price_range_filter">
            <h4><?php _e('Price Range', 'wp-restaurant-listings' ) ?></h4>
            <ul class="price_range_list">
               <?php for ( $i = 1; $i < 6; $i++): ?>
                <li data-price_range="<?php echo $i; ?>"><label class="radio-check"><span class="filter-label"><?php echo str_repeat( $currency, $i) ?></span></label></li>
              <?php endfor; ?>
            </ul>
            <input type="hidden" name="search_price_range">
        </div>

		<?php do_action( 'restaurant_listings_restaurant_filters_search_restaurants_end', $atts ); ?>
	</div>

	<?php do_action( 'restaurant_listings_restaurant_filters_end', $atts ); ?>
</form>

<?php do_action( 'restaurant_listings_restaurant_filters_after', $atts ); ?>

<noscript><?php _e( 'Your browser does not support JavaScript, or it is disabled. JavaScript must be enabled in order to view listings.', 'wp-restaurant-listings' ); ?></noscript>
