<?php
/**
 * Filter in `[restaurants]` shortcode for restaurant types.
 *
 * This template can be overridden by copying it to yourtheme/restaurant_listings/restaurant-filter-restaurant-types.php.
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
?>
<?php if ( ! is_tax( 'restaurant_listings_type' ) && empty( $restaurant_types ) ) : ?>
	<ul class="restaurant_types">
		<?php foreach ( get_restaurant_listings_types() as $type ) : ?>
			<li><label for="restaurant_type_<?php echo $type->slug; ?>" class="<?php echo sanitize_title( $type->name ); ?>"><input type="checkbox" name="filter_restaurant_type[]" value="<?php echo $type->slug; ?>" <?php checked( in_array( $type->slug, $selected_restaurant_types ), true ); ?> id="restaurant_type_<?php echo $type->slug; ?>" /> <?php echo $type->name; ?></label></li>
		<?php endforeach; ?>
	</ul>
	<input type="hidden" name="filter_restaurant_type[]" value="" />
<?php elseif ( $restaurant_types ) : ?>
	<?php foreach ( $restaurant_types as $restaurant_type ) : ?>
		<input type="hidden" name="filter_restaurant_type[]" value="<?php echo sanitize_title( $restaurant_type ); ?>" />
	<?php endforeach; ?>
<?php endif; ?>
