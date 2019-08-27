<?php
/**
 * Restaurant Images
 *
 * Display the restaurant images meta box.
 *
 * @category    Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WP_Restaurant_Listings_Meta_Box_Gallery Class.
 */
class WP_Restaurant_Listings_Meta_Box_Gallery {

	/**
	 * Output the metabox.
	 *
	 * @param WP_Post $post
	 */
	public static function output( $post ) {
		?>
		<div id="restaurant_images_container">
			<ul class="restaurant_images">
				<?php
				$restaurant_image_gallery = '';
				if ( metadata_exists( 'post', $post->ID, '_restaurant_image_gallery' ) ) {
					$restaurant_image_gallery = get_post_meta( $post->ID, '_restaurant_image_gallery', true );
				}

				$attachments         = array_filter( explode( ',', $restaurant_image_gallery ) );
				$update_meta         = false;
				$updated_gallery_ids = array();

				if ( ! empty( $attachments ) ) {
					foreach ( $attachments as $attachment_id ) {
						$attachment = wp_get_attachment_image( $attachment_id, 'thumbnail' );

						// if attachment is empty skip
						if ( empty( $attachment ) ) {
							$update_meta = true;
							continue;
						}

						echo '<li class="image" data-attachment_id="' . esc_attr( $attachment_id ) . '">
									' . $attachment . '
									<ul class="actions">
										<li><a href="#" class="delete tips" data-tip="' . esc_attr__( 'Delete image', 'wp-restaurant-listings' ) . '">' . __( 'Delete', 'wp-restaurant-listings' ) . '</a></li>
									</ul>
								</li>';

						// rebuild ids to be saved
						$updated_gallery_ids[] = $attachment_id;
					}

					// need to update restaurant meta to set new gallery ids
					if ( $update_meta ) {
						update_post_meta( $post->ID, '_restaurant_image_gallery', implode( ',', $updated_gallery_ids ) );
					}
				}
				?>
			</ul>

			<input type="hidden" id="restaurant_image_gallery" name="restaurant_image_gallery" value="<?php echo esc_attr( $restaurant_image_gallery ); ?>" />

		</div>
		<p class="add_restaurant_images hide-if-no-js">
			<a href="#" data-choose="<?php esc_attr_e( 'Add images to restaurant gallery', 'wp-restaurant-listings' ); ?>" data-update="<?php esc_attr_e( 'Add to gallery', 'wp-restaurant-listings' ); ?>" data-delete="<?php esc_attr_e( 'Delete image', 'wp-restaurant-listings' ); ?>" data-text="<?php esc_attr_e( 'Delete', 'wp-restaurant-listings' ); ?>"><?php _e( 'Add restaurant gallery images', 'wp-restaurant-listings' ); ?></a>
		</p>
		<?php
	}

	/**
	 * Save meta box data.
	 *
	 * @param int $post_id
	 * @param WP_Post $post
	 */
	public static function save( $post_id, $post ) {
		$attachment_ids = isset( $_POST['restaurant_image_gallery'] ) ? array_filter( explode( ',', $_POST['restaurant_image_gallery'] ) ) : array();

		update_post_meta( $post_id, '_restaurant_image_gallery', implode( ',', $attachment_ids ) );
	}
}
