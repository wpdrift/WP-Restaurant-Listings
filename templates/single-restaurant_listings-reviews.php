<?php
/**
 * Display single reviews (comments)
 *
 * This template can be overridden by copying it to yourtheme/restaurant_listings/single-restaurant_listings-reviews.php.
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

if ( ! comments_open() ) {
	return;
}

global $post;

if ( WP_Restaurant_Listings_Template_Loader::$comment_template_loaded ) {
	return;
}
WP_Restaurant_Listings_Template_Loader::$comment_template_loaded = true;

?>
<div id="reviews" class="restaurant-listings-Reviews">
	<div id="comments">
		<h2 class="restaurant-listings-Reviews-title"><?php
			    $count = restaurant_listings_get_review_count($post->ID);

				/* translators: 1: reviews count 2: name */
				printf( esc_html( _n( '%1$s review for %2$s', '%1$s reviews for %2$s', 4, 'wp-restaurant-listings' ) ), esc_html( $count ), '<span>' . get_the_title() . '</span>' );

		?></h2>

		<?php if ( have_comments() ) : ?>

			<ol class="commentlist">
				<?php wp_list_comments( apply_filters( 'restaurant-listing_review_list_args', array( 'callback' => 'restaurant_listings_comments' ) ) ); ?>
			</ol>

			<?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) :
				echo '<nav class="restaurant-listings-pagination">';
				paginate_comments_links( apply_filters( 'restaurant_listings_comment_pagination_args', array(
					'prev_text' => '&larr;',
					'next_text' => '&rarr;',
					'type'      => 'list',
				) ) );
				echo '</nav>';
			endif; ?>

		<?php else : ?>

			<p class="restaurant-listings-noreviews"><?php _e( 'There are no reviews yet.', 'wp-restaurant-listings' ); ?></p>

		<?php endif; ?>
	</div>



		<div id="review_form_wrapper">
			<div id="review_form">
				<?php
					$commenter = wp_get_current_commenter();

					$comment_form = array(
						'title_reply'          => have_comments() ? __( 'Add a review', 'wp-restaurant-listings' ) : sprintf( __( 'Be the first to review &ldquo;%s&rdquo;', 'wp-restaurant-listings' ), get_the_title() ),
						'title_reply_to'       => __( 'Leave a Reply to %s', 'wp-restaurant-listings' ),
						'title_reply_before'   => '<span id="reply-title" class="comment-reply-title">',
						'title_reply_after'    => '</span>',
						'comment_notes_after'  => '',
						'fields'               => array(
							'author' => '<p class="comment-form-author">' . '<label for="author">' . esc_html__( 'Name', 'wp-restaurant-listings' ) . ' <span class="required">*</span></label> ' .
										'<input id="author" name="author" type="text" value="' . esc_attr( $commenter['comment_author'] ) . '" size="30" aria-required="true" required /></p>',
							'email'  => '<p class="comment-form-email"><label for="email">' . esc_html__( 'Email', 'wp-restaurant-listings' ) . ' <span class="required">*</span></label> ' .
										'<input id="email" name="email" type="email" value="' . esc_attr( $commenter['comment_author_email'] ) . '" size="30" aria-required="true" required /></p>',
						),
						'label_submit'  => __( 'Submit', 'wp-restaurant-listings' ),
						'logged_in_as'  => '',
						'comment_field' => '',
					);



						$comment_form['comment_field'] = '<div class="comment-form-rating"><label for="rating">' . esc_html__( 'Your rating', 'wp-restaurant-listings' ) . '</label><select name="rating" id="rating" aria-required="true" required>
							<option value="">' . esc_html__( 'Rate&hellip;', 'wp-restaurant-listings' ) . '</option>
							<option value="5">' . esc_html__( 'Perfect', 'wp-restaurant-listings' ) . '</option>
							<option value="4">' . esc_html__( 'Good', 'wp-restaurant-listings' ) . '</option>
							<option value="3">' . esc_html__( 'Average', 'wp-restaurant-listings' ) . '</option>
							<option value="2">' . esc_html__( 'Not that bad', 'wp-restaurant-listings' ) . '</option>
							<option value="1">' . esc_html__( 'Very poor', 'wp-restaurant-listings' ) . '</option>
						</select></div>';


					$comment_form['comment_field'] .= '<p class="comment-form-comment"><label for="comment">' . esc_html__( 'Your review', 'wp-restaurant-listings' ) . ' <span class="required">*</span></label><textarea id="comment" name="comment" cols="45" rows="8" aria-required="true" required></textarea></p>';

					comment_form( apply_filters( 'restaurant_listings_review_comment_form_args', $comment_form ) );
				?>
			</div>
		</div>

	<div class="clear"></div>
</div>
