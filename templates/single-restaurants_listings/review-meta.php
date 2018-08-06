<?php
/**
 * The template to display the reviewers meta data
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

global $comment;

if ( '0' === $comment->comment_approved ) { ?>

	<p class="meta"><em class="restaurants-review__awaiting-approval"><?php esc_attr_e( 'Your review is awaiting approval', 'wp-restaurant-listings' ); ?></em></p>

<?php } else { ?>

	<p class="meta">
		<strong class="restaurants-review__author"><?php comment_author(); ?></strong> <?php

		?><span class="restaurants-review__dash">&ndash;</span> <time class="restaurants-review__published-date" datetime="<?php echo get_comment_date( 'c' ); ?>"><?php echo get_comment_date( wc_date_format() ); ?></time>
	</p>

<?php }
