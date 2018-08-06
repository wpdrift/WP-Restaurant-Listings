<?php
/**
 * Restaurant dashboard shortcode content.
 *
 * This template can be overridden by copying it to yourtheme/restaurant_listings/restaurant-dashboard.php.
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
<div id="restaurant-listings-restaurant-dashboard">
	<p><?php _e( 'Your listings are shown in the table below.', 'wp-restaurant-listings' ); ?></p>
	<table class="restaurant-listings-restaurants">
		<thead>
			<tr>
				<?php foreach ( $restaurant_dashboard_columns as $key => $column ) : ?>
					<th class="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $column ); ?></th>
				<?php endforeach; ?>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! $restaurants ) : ?>
				<tr>
					<td colspan="6"><?php _e( 'You do not have any active listings.', 'wp-restaurant-listings' ); ?></td>
				</tr>
			<?php else : ?>
				<?php foreach ( $restaurants as $restaurant ) : ?>
					<tr>
						<?php foreach ( $restaurant_dashboard_columns as $key => $column ) : ?>
							<td class="<?php echo esc_attr( $key ); ?>">
								<?php if ('restaurant_title' === $key ) : ?>
									<?php if ( $restaurant->post_status == 'publish' ) : ?>
										<a href="<?php echo get_permalink( $restaurant->ID ); ?>"><?php the_restaurant_title( $restaurant ); ?></a>
									<?php else : ?>
										<?php the_restaurant_title( $restaurant ); ?> <small>(<?php the_restaurant_status( $restaurant ); ?>)</small>
									<?php endif; ?>
									<ul class="restaurant-dashboard-actions">
										<?php
											$actions = array();

											switch ( $restaurant->post_status ) {
												case 'publish' :
													$actions['edit'] = array( 'label' => __( 'Edit', 'wp-restaurant-listings' ), 'nonce' => false );

													$actions['duplicate'] = array( 'label' => __( 'Duplicate', 'wp-restaurant-listings' ), 'nonce' => true );
													break;
												case 'pending_payment' :
												case 'pending' :
													if ( restaurant_listings_user_can_edit_pending_submissions() ) {
														$actions['edit'] = array( 'label' => __( 'Edit', 'wp-restaurant-listings' ), 'nonce' => false );
													}
												break;
											}

											$actions['delete'] = array( 'label' => __( 'Delete', 'wp-restaurant-listings' ), 'nonce' => true );
											$actions           = apply_filters( 'restaurant_listings_my_restaurant_actions', $actions, $restaurant );

											foreach ( $actions as $action => $value ) {
												$action_url = add_query_arg( array( 'action' => $action, 'restaurant_id' => $restaurant->ID ) );
												if ( $value['nonce'] ) {
													$action_url = wp_nonce_url( $action_url, 'restaurant_listings_my_restaurant_actions' );
												}
												echo '<li><a href="' . esc_url( $action_url ) . '" class="restaurant-dashboard-action-' . esc_attr( $action ) . '">' . esc_html( $value['label'] ) . '</a></li>';
											}
										?>
									</ul>
								<?php elseif ('date' === $key ) : ?>
									<?php echo date_i18n( get_option( 'date_format' ), strtotime( $restaurant->post_date ) ); ?>
								<?php else : ?>
									<?php do_action( 'restaurant_listings_restaurant_dashboard_column_' . $key, $restaurant ); ?>
								<?php endif; ?>
							</td>
						<?php endforeach; ?>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
	<?php get_restaurant_listings_template( 'pagination.php', array( 'max_num_pages' => $max_num_pages ) ); ?>
</div>
