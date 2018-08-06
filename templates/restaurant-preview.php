<?php
/**
 * Restaurant listing preview when submitting restaurant listings.
 *
 * This template can be overridden by copying it to yourtheme/restaurant_listings/restaurant-preview.php.
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
<form method="post" id="restaurant_preview" action="<?php echo esc_url( $form->get_action() ); ?>">
	<div class="restaurant_listings_preview_title">
		<input type="submit" name="continue" id="restaurant_preview_submit_button" class="button restaurant-listings-button-submit-listings" value="<?php echo apply_filters( 'submit_restaurant_step_preview_submit_text', __( 'Submit Listings', 'wp-restaurant-listings' ) ); ?>" />
		<input type="submit" name="edit_restaurant" class="button restaurant-listings-button-edit-listings" value="<?php _e( 'Edit listings', 'wp-restaurant-listings' ); ?>" />
		<h2><?php _e( 'Preview', 'wp-restaurant-listings' ); ?></h2>
	</div>
	<div class="restaurant_listings_preview single_restaurant_listing">
		<h1><?php the_restaurant_title(); ?></h1>

		<?php get_restaurant_listings_template_part( 'content-single', 'restaurant_listings' ); ?>

		<input type="hidden" name="restaurant_id" value="<?php echo esc_attr( $form->get_restaurant_id() ); ?>" />
		<input type="hidden" name="step" value="<?php echo esc_attr( $form->get_step() ); ?>" />
		<input type="hidden" name="restaurant_listings_form" value="<?php echo $form->get_form_name(); ?>" />
	</div>
</form>
