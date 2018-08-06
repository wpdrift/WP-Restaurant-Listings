<?php
/**
 * Shows term `select` form field on restaurant listing forms.
 *
 * This template can be overridden by copying it to yourtheme/restaurant_listings/form-fields/term-select-field.php.
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

// Get selected value
if ( isset( $field['value'] ) ) {
	$selected = $field['value'];
} elseif ( is_int( $field['default'] ) ) {
	$selected = $field['default'];
} elseif ( ! empty( $field['default'] ) && ( $term = get_term_by( 'slug', $field['default'], $field['taxonomy'] ) ) ) {
	$selected = $term->term_id;
} else {
	$selected = '';
}

// Select only supports 1 value
if ( is_array( $selected ) ) {
	$selected = current( $selected );
}

wp_dropdown_categories( apply_filters( 'restaurant_listings_term_select_field_wp_dropdown_categories_args', array(
	'taxonomy'         => $field['taxonomy'],
	'hierarchical'     => 1,
	'show_option_all'  => false,
	'show_option_none' => $field['required'] ? '' : '-',
	'name'             => isset( $field['name'] ) ? $field['name'] : $key,
	'orderby'          => 'name',
	'selected'         => $selected,
	'hide_empty'       => false
), $key, $field ) );
if ( ! empty( $field['description'] ) ) : ?><small class="description"><?php echo $field['description']; ?></small><?php endif; ?>
