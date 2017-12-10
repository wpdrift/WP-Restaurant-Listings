<?php
/**
 * The template to display the reviewers meta data
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

global $comment;

if ( '0' === $comment->comment_approved ) { ?>

	<p class="meta"><em class="restaurant-review__awaiting-approval"><?php esc_attr_e( 'Your review is awaiting approval', 'wp-restaurant-listings' ); ?></em></p>

<?php } else { ?>

	<p class="meta">
		<strong class="restaurant-review__author"><?php comment_author(); ?></strong> <?php

		?><span class="restaurant-review__dash">&ndash;</span> <time class="restaurant-review__published-date" datetime="<?php echo get_comment_date( 'c' ); ?>"><?php echo get_comment_date(); ?></time>
	</p>

<?php }
