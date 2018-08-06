<?php
/**
 * Shows `select` form fields in a list from a list on restaurant listing hours.
 *
 * This template can be overridden by copying it to yourtheme/restaurant_listings/form-fields/business-hours-field.php.
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

wp_enqueue_script( 'jquey-timepicker' );
wp_enqueue_style( 'jquery-timepicker' );

global $wp_locale;

if ( is_admin() ) {
	global $field;
}

$days = restaurant_listings_get_days_of_week();
?>

<table>
	<tr>
		<th width="40%">&nbsp;</th>
		<th align="left"><?php _e( 'Open', 'wp-restaurant-listings' ); ?></th>
		<th align="left"><?php _e( 'Close', 'wp-restaurant-listings' ); ?></th>
	</tr>

	<?php foreach ( $days as $key => $i ) : ?>
		<tr>
			<td align="left"><?php echo $wp_locale->get_weekday( $i ); ?></td>
			<td align="left" class="business-hour"><input type="text" class="timepicker" name="restaurant_hours[<?php echo $i;
			?>][start]" value="<?php echo isset( $field[ 'value' ][ $i ] ) && isset( $field[ 'value' ][ $i ][ 'start' ] ) ? $field[ 'value' ][ $i ][ 'start' ] : ''; ?>" class="regular-text" /></td>
			<td align="left" class="business-hour"><input type="text" class="timepicker" name="restaurant_hours[<?php echo $i;
			?>][end]" value="<?php echo isset( $field[ 'value' ][ $i ] ) && isset( $field[ 'value' ][ $i ][ 'end' ] )
			?$field[ 'value' ][ $i ][ 'end' ] : ''; ?>" class="regular-text" /></td>
		</tr>
	<?php endforeach; ?>
</table>
