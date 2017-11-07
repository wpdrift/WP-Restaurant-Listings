<?php
/**
 * The template to display the reviewers star rating in reviews
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $comment;
$rating = intval( get_comment_meta( $comment->comment_ID, 'rating', true ) );
echo restaurant_listings_get_rating_html( $rating );