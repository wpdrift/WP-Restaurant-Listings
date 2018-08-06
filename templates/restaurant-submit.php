<?php
/**
 * Restaurant Submission Form
 *
 * This template can be overridden by copying it to yourtheme/restaurant_listings/restaurant-submit.php.
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

global $restaurant_listings;
?>
<form action="<?php echo esc_url( $action ); ?>" method="post" id="submit-restaurant-form" class="restaurant-listings-form" enctype="multipart/form-data">

	<?php
	if ( isset( $resume_edit ) && $resume_edit ) {
		printf( '<p><strong>' . __( "You are editing an existing restaurant. %s", 'wp-restaurant-listings' ) . '</strong></p>', '<a href="?new=1&key=' . $resume_edit . '">' . __( 'Create A New Restaurant', 'wp-restaurant-listings' ) . '</a>' );
	}
	?>

	<?php do_action( 'submit_restaurant_form_start' ); ?>

	<?php if ( apply_filters( 'submit_restaurant_form_show_signin', true ) ) : ?>

		<?php get_restaurant_listings_template( 'account-signin.php' ); ?>

	<?php endif; ?>

	<?php if ( restaurant_listings_user_can_post_restaurant() || restaurant_listings_user_can_edit_restaurant( $restaurant_id ) ) : ?>

		<!-- Restaurant Information Fields -->
		<?php do_action( 'submit_restaurant_form_restaurant_fields_start' ); ?>

		<?php foreach ( $basic_fields as $key => $field ) : ?>
			<fieldset class="fieldset-<?php echo esc_attr( $key ); ?>">
				<label for="<?php echo esc_attr( $key ); ?>"><?php echo $field['label'] . apply_filters( 'submit_restaurant_form_required_label', $field['required'] ? '' : ' <small>' . __( '(optional)', 'wp-restaurant-listings' ) . '</small>', $field ); ?></label>
				<div class="field <?php echo $field['required'] ? 'required-field' : ''; ?>">
					<?php get_restaurant_listings_template( 'form-fields/' . $field['type'] . '-field.php', array( 'key' => $key, 'field' => $field ) ); ?>
				</div>
			</fieldset>
		<?php endforeach; ?>

		<?php do_action( 'submit_restaurant_form_restaurant_fields_end' ); ?>

		<!-- Restaurant Information Fields -->
		<?php if ( $extra_fields ) : ?>
			<h2><?php _e( 'Restaurant Details', 'wp-restaurant-listings' ); ?></h2>

			<?php do_action( 'submit_restaurant_form_restaurant_fields_start' ); ?>

			<?php foreach ( $extra_fields as $key => $field ) : ?>
				<fieldset class="fieldset-<?php echo esc_attr( $key ); ?>">
					<label for="<?php echo esc_attr( $key ); ?>"><?php echo $field['label'] . apply_filters( 'submit_restaurant_form_required_label', $field['required'] ? '' : ' <small>' . __( '(optional)', 'wp-restaurant-listings' ) . '</small>', $field ); ?></label>
					<div class="field <?php echo $field['required'] ? 'required-field' : ''; ?>">
						<?php get_restaurant_listings_template( 'form-fields/' . $field['type'] . '-field.php', array( 'key' => $key, 'field' => $field ) ); ?>
					</div>
				</fieldset>
			<?php endforeach; ?>

			<?php do_action( 'submit_restaurant_form_restaurant_fields_end' ); ?>
		<?php endif; ?>

		<?php do_action( 'submit_restaurant_form_end' ); ?>

		<p>
			<input type="hidden" name="restaurant_listings_form" value="<?php echo $form; ?>" />
			<input type="hidden" name="restaurant_id" value="<?php echo esc_attr( $restaurant_id ); ?>" />
			<input type="hidden" name="step" value="<?php echo esc_attr( $step ); ?>" />
			<input type="submit" name="submit_restaurant" class="button" value="<?php echo esc_attr( $submit_button_text ); ?>" />
		</p>

	<?php else : ?>

		<?php do_action( 'submit_restaurant_form_disabled' ); ?>

	<?php endif; ?>
</form>
