<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Comments
 *
 * Handle comments (reviews and order notes).
 */
class WP_Restaurant_Listings_Comments {

	/**
	 * Hook in methods.
	 */
	public static function init() {
		// Rating posts
		add_filter( 'comments_open', array( __CLASS__, 'comments_open' ), 10, 2 );
		add_action( 'comment_post', array( __CLASS__, 'add_comment_rating' ), 1 );

		// Support avatars for `review` comment type
		add_filter( 'get_avatar_comment_types', array( __CLASS__, 'add_avatar_for_review_comment_type' ) );

	}

	/**
	 * See if comments are open.
	 *
	 * @param  bool $open
	 * @param  int $post_id
	 * @return bool
	 */
	public static function comments_open( $open, $post_id ) {
		if ( 'restaurant_listings' === get_post_type( $post_id ) && ! post_type_supports( 'restaurant_listings', 'comments' ) ) {
			$open = false;
		}
		return $open;
	}


	/**
	 * Rating field for comments.
	 * @param int $comment_id
	 */
	public static function add_comment_rating( $comment_id ) {
		if ( isset( $_POST['rating'] ) && 'restaurant_listings' === get_post_type( $_POST['comment_post_ID'] ) ) {
			if ( ! $_POST['rating'] || $_POST['rating'] > 5 || $_POST['rating'] < 0 ) {
				return;
			}
			add_comment_meta( $comment_id, 'rating', (int) esc_attr( $_POST['rating'] ), true );
		}
	}

	/**
	 * Make sure WP displays avatars for comments with the `review` type.
	 * @param  array $comment_types
	 * @return array
	 */
	public static function add_avatar_for_review_comment_type( $comment_types ) {
		return array_merge( $comment_types, array( 'review' ) );
	}

    /**
     * Get restaurant rating for a restaurant. Please note this is not cached.
     */
    public static function get_average_rating_for_restaurant( $post ) {
        global $wpdb;

        $count = array_sum( self::get_rating_counts_for_restaurant($post) );

        if ( $count ) {
            $ratings = $wpdb->get_var( $wpdb->prepare("
				SELECT SUM(meta_value) FROM $wpdb->commentmeta
				LEFT JOIN $wpdb->comments ON $wpdb->commentmeta.comment_id = $wpdb->comments.comment_ID
				WHERE meta_key = 'rating'
				AND comment_post_ID = %d
				AND comment_approved = '1'
				AND meta_value > 0
			", $post->ID ) );
            $average = number_format( $ratings / $count, 2, '.', '' );
        } else {
            $average = 0;
        }

        return $average;
    }

    /**
     * Get restaurant review count for a restaurant (not replies). Please note this is not cached.
     *
     * @since 1.0.0
     * @param WC_Product $product
     * @return int
     */
    public static function get_review_count_for_restaurant( $post ) {
        global $wpdb;

        $count = $wpdb->get_var( $wpdb->prepare("
			SELECT COUNT(*) FROM $wpdb->comments
			WHERE comment_parent = 0
			AND comment_post_ID = %d
			AND comment_approved = '1'
		", $post->ID ) );

        return $count;
    }

    /**
     * Get restaurant rating count for a restaurant. Please note this is not cached.
     *
     * @since 1.0.0
     * @param WC_Product $product
     * @return array of integers
     */
    public static function get_rating_counts_for_restaurant( $post ) {
        global $wpdb;

        $counts     = array();
        $raw_counts = $wpdb->get_results( $wpdb->prepare( "
			SELECT meta_value, COUNT( * ) as meta_value_count FROM $wpdb->commentmeta
			LEFT JOIN $wpdb->comments ON $wpdb->commentmeta.comment_id = $wpdb->comments.comment_ID
			WHERE meta_key = 'rating'
			AND comment_post_ID = %d
			AND comment_approved = '1'
			AND meta_value > 0
			GROUP BY meta_value
		", $post->ID ) );

        foreach ( $raw_counts as $count ) {
            $counts[ $count->meta_value ] = absint( $count->meta_value_count );
        }

        return $counts;
    }

}

WP_Restaurant_Listings_Comments::init();
