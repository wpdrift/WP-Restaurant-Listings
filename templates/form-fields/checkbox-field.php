<?php
/**
 * Shows the `checkbox` form field on restaurant listing forms.
 *
 * This template can be overridden by copying it to yourtheme/restaurant_listings/form-fields/checkbox-field.php.
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
<input type="checkbox" class="input-checkbox" name="<?php echo esc_attr( isset( $field['name'] ) ? $field['name'] : $key ); ?>" id="<?php echo esc_attr( $key ); ?>" <?php checked( ! empty( $field['value'] ), true ); ?> value="1" <?php if ( ! empty( $field['required'] ) ) echo 'required'; ?> />
<?php if ( ! empty( $field['description'] ) ) : ?><small class="description"><?php echo $field['description']; ?></small><?php endif; ?>
