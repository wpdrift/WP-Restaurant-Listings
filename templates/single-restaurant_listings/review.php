<?php
/**
 * Review Comments Template
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<li <?php comment_class(); ?> id="li-comment-<?php comment_ID() ?>">

	<div id="comment-<?php comment_ID(); ?>" class="comment_container">

		<?php
		/**
		 * The restaurant_listings_review_before hook
		 *
		 * @hooked restaurant_listings_review_display_gravatar - 10
		 */
		do_action( 'restaurant_listings_review_before', $comment );
		?>

		<div class="comment-text">

			<?php
			/**
			 * The restaurant_listings_review_before_comment_meta hook.
			 *
			 * @hooked restaurant_listings_review_display_rating - 10
			 */
			do_action( 'restaurant_listings_review_before_comment_meta', $comment );

			/**
			 * The restaurant_listings_review_meta hook.
			 *
			 * @hooked restaurant_listings_review_display_meta - 10
			 * @hooked WC_Structured_Data::generate_review_data() - 20
			 */
			do_action( 'restaurant_listings_review_meta', $comment );

			do_action( 'restaurant_listings_review_before_comment_text', $comment );

			/**
			 * The restaurant_listings_review_comment_text hook
			 *
			 * @hooked restaurant_listings_review_display_comment_text - 10
			 */
			do_action( 'restaurant_listings_review_comment_text', $comment );

			do_action( 'restaurant_listings_review_after_comment_text', $comment ); ?>

		</div>
	</div>
