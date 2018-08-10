<?php
/**
 * In restaurant listing creation flow, this template shows above the restaurant creation form.
 *
 * This template can be overridden by copying it to yourtheme/restaurant_listings/account-signin.php.
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
<?php if ( is_user_logged_in() ) : ?>

	<fieldset>
		<label><?php _e( 'Your account', 'wp-restaurant-listings' ); ?></label>
		<div class="field account-sign-in">
			<?php
				$user = wp_get_current_user();
				printf( __( 'You are currently signed in as <strong>%s</strong>.', 'wp-restaurant-listings' ), $user->user_login );
			?>

			<a class="button" href="<?php echo apply_filters( 'submit_restaurant_form_logout_url', wp_logout_url( get_permalink() ) ); ?>"><?php _e( 'Sign out', 'wp-restaurant-listings' ); ?></a>
		</div>
	</fieldset>

<?php else :
	$account_required            = restaurant_listings_user_requires_account();
	$registration_enabled        = restaurant_listings_enable_registration();
	$registration_fields         = wprl_get_registration_fields();
	$use_standard_password_email = wprl_use_standard_password_setup_email();
	?>
	<fieldset>
		<label><?php _e( 'Have an account?', 'wp-restaurant-listings' ); ?></label>
		<div class="field account-sign-in">
			<a class="button" href="<?php echo apply_filters( 'submit_restaurant_form_login_url', wp_login_url( get_permalink() ) ); ?>"><?php _e( 'Sign in', 'wp-restaurant-listings' ); ?></a>

			<?php if ( $registration_enabled ) : ?>

				<?php printf( __( 'If you don&rsquo;t have an account you can %screate one below by entering your email address/username.', 'wp-restaurant-listings' ), $account_required ? '' : __( 'optionally', 'wp-restaurant-listings' ) . ' ' ); ?>
				<?php if ( $use_standard_password_email ) : ?>
					<?php printf( __( 'Your account details will be confirmed via email.', 'wp-restaurant-listings' ) ); ?>
				<?php endif; ?>

			<?php elseif ( $account_required ) : ?>

				<?php echo apply_filters( 'submit_restaurant_form_login_required_message',  __('You must sign in to create a new listings.', 'wp-restaurant-listings' ) ); ?>

			<?php endif; ?>
		</div>
	</fieldset>
	<?php
	if ( ! empty( $registration_fields ) ) {
		foreach ( $registration_fields as $key => $field ) {
			?>
			<fieldset class="fieldset-<?php echo esc_attr( $key ); ?>">
				<label
					for="<?php echo esc_attr( $key ); ?>"><?php echo $field[ 'label' ] . apply_filters( 'submit_restaurant_form_required_label', $field[ 'required' ] ? '' : ' <small>' . __( '(optional)', 'wp-restaurant-listings' ) . '</small>', $field ); ?></label>
				<div class="field <?php echo $field[ 'required' ] ? 'required-field' : ''; ?>">
					<?php get_restaurant_listings_template( 'form-fields/' . $field[ 'type' ] . '-field.php', array( 'key'   => $key, 'field' => $field ) ); ?>
				</div>
			</fieldset>
			<?php
		}
		do_action( 'restaurant_listings_register_form' );
	}
	?>
<?php endif; ?>
