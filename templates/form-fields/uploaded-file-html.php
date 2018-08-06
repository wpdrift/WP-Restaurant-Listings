<?php
/**
 * Shows info for an uploaded file on restaurant listing forms.
 *
 * This template can be overridden by copying it to yourtheme/restaurant_listings/form-fields/uploaded-file-html.php.
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
<div class="restaurant-listings-uploaded-file">
	<?php
	if ( is_numeric( $value ) ) {
		$image_src = wp_get_attachment_image_src( absint( $value ) );
		$image_src = $image_src ? $image_src[0] : '';
	} else {
		$image_src = $value;
	}
	$extension = ! empty( $extension ) ? $extension : substr( strrchr( $image_src, '.' ), 1 );

	if ( 3 !== strlen( $extension ) || in_array( $extension, array( 'jpg', 'gif', 'png', 'jpeg', 'jpe' ) ) ) : ?>
		<span class="restaurant-listings-uploaded-file-preview"><img src="<?php echo esc_url( $image_src ); ?>" /> <a class="restaurant-listings-remove-uploaded-file" href="#">[<?php _e( 'remove', 'wp-restaurant-listings' ); ?>]</a></span>
	<?php else : ?>
		<span class="restaurant-listings-uploaded-file-name"><code><?php echo esc_html( basename( $image_src ) ); ?></code> <a class="restaurant-listings-remove-uploaded-file" href="#">[<?php _e( 'remove', 'wp-restaurant-listings' ); ?>]</a></span>
	<?php endif; ?>

	<input type="hidden" class="input-text" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" />
</div>
