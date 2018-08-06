<?php
/**
 * Shows `checkbox` form fields in a list from a list on restaurant listing forms.
 *
 * This template can be overridden by copying it to yourtheme/restaurant_listings/form-fields/term-checklist-field.php.
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
<ul class="restaurant-listings-term-checklist restaurant-listings-term-checklist-<?php echo $key ?>">
<?php
	require_once( ABSPATH . '/wp-admin/includes/template.php' );

	if ( empty( $field['default'] ) ) {
		$field['default'] = '';
	}

	$args = array(
		'descendants_and_self'  => 0,
		'selected_cats'         => isset( $field['value'] ) ? $field['value'] : ( is_array( $field['default'] ) ? $field['default'] : array( $field['default'] ) ),
		'popular_cats'          => false,
		'taxonomy'              => $field['taxonomy'],
		'checked_ontop'         => false
	);

	// $field['post_id'] needs to be passed via the args so we can get the existing terms
	ob_start();
	wp_terms_checklist( 0, $args );
	$checklist = ob_get_clean();
	echo str_replace( "disabled='disabled'", '', $checklist );
?>
</ul>
<?php if ( ! empty( $field['description'] ) ) : ?><small class="description"><?php echo $field['description']; ?></small><?php endif; ?>
