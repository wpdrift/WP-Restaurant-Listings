<?php
/**
 * Shows the `radio` form field on restaurant listing forms.
 *
 * This template can be overridden by copying it to yourtheme/restaurant_listings/form-fields/radio-field.php.
 *
 * Radio Field. Example definition:
 *
 * 'test_radio' => array(
 * 		'label'    => __( 'Test Radio', 'wp-restaurant-listings' ),
 * 		'type'     => 'radio',
 * 		'required' => false,
 * 		'default'  => 'option2',
 * 		'priority' => 1,
 * 		'options'  => array(
 * 			'option1' => 'This is option 1',
 * 		 	'option2' => 'This is option 2'
 * 		)
 * 	)
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

$field['default'] = empty( $field['default'] ) ? current( array_keys( $field['options'] ) ) : $field['default'];
$default          = ! empty( $field['value'] ) ? $field['value'] : $field['default'];

foreach ( $field['options'] as $option_key => $value ) : ?>

	<label><input type="radio" name="<?php echo esc_attr( isset( $field['name'] ) ? $field['name'] : $key ); ?>" value="<?php echo esc_attr( $option_key ); ?>" <?php checked( $default, $option_key ); ?> /> <?php echo esc_html( $value ); ?></label><br/>

<?php endforeach; ?>
<?php if ( ! empty( $field['description'] ) ) : ?><small class="description"><?php echo $field['description']; ?></small><?php endif; ?>
