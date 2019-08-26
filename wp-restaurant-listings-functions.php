<?php
if ( ! function_exists( 'get_restaurant_listings' ) ) :
	/**
	 * Queries restaurant listings with certain criteria and returns them.
	 *
	 * @since 1.0.0
	 * @param string|array|object $args Arguments used to retrieve restaurant listings.
	 * @return WP_Query
	 */
	function get_restaurant_listings( $args = array() ) {
		global $wpdb, $restaurant_listings_keyword;

		$args = wp_parse_args( $args, array(
			'search_location'    => '',
			'search_keywords'    => '',
			'search_categories'  => array(),
			'restaurant_types'   => array(),
			'search_price_range' => '',
			'post_status'        => array(),
			'offset'             => 0,
			'posts_per_page'     => 20,
			'orderby'            => 'date',
			'order'              => 'DESC',
			'featured'           => null,
			'fields'             => 'all',
		) );

		/**
		 * Perform actions that need to be done prior to the start of the restaurant listings query.
		 *
		 * @since 1.0.0
		 *
		 * @param array $args Arguments used to retrieve restaurant listings.
		 */
		do_action( 'get_restaurant_listings_init', $args );

		if ( ! empty( $args['post_status'] ) ) {
			$post_status = $args['post_status'];
		} else {
			$post_status = 'publish';
		}

		$query_args = array(
			'post_type'              => 'restaurant_listings',
			'post_status'            => $post_status,
			'ignore_sticky_posts'    => 1,
			'offset'                 => absint( $args['offset'] ),
			'posts_per_page'         => intval( $args['posts_per_page'] ),
			'orderby'                => $args['orderby'],
			'order'                  => $args['order'],
			'tax_query'              => array(),
			'meta_query'             => array(),
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false,
			'cache_results'          => false,
			'fields'                 => $args['fields'],
		);

		if ( $args['posts_per_page'] < 0 ) {
			$query_args['no_found_rows'] = true;
		}

		if ( ! empty( $args['search_location'] ) ) {
			$location_meta_keys = array( 'geolocation_formatted_address', '_restaurant_location', 'geolocation_state_long' );
			$location_search    = array( 'relation' => 'OR' );
			foreach ( $location_meta_keys as $meta_key ) {
				$location_search[] = array(
					'key'     => $meta_key,
					'value'   => $args['search_location'],
					'compare' => 'like',
				);
			}
			$query_args['meta_query'][] = $location_search;
		}

		if ( ! is_null( $args['featured'] ) ) {
			$query_args['meta_query'][] = array(
				'key'     => '_featured',
				'value'   => '1',
				'compare' => $args['featured'] ? '=' : '!=',
			);
		}

		if ( ! empty( $args['restaurant_types'] ) ) {
			$query_args['tax_query'][] = array(
				'taxonomy' => 'restaurant_listings_type',
				'field'    => 'slug',
				'terms'    => $args['restaurant_types'],
			);
		}

		if ( ! empty( $args['search_price_range'] ) ) {
			$query_args['meta_query'][] = array(
				'key'       => '_restaurant_price_range',
				'value'     => (int)$args['search_price_range'],
				'compare'   => '=',
			);
		}

		if ( ! empty( $args['search_categories'] ) ) {
			$field                     = is_numeric( $args['search_categories'][0] ) ? 'term_id' : 'slug';
			$operator                  = 'all' === get_option( 'restaurant_listings_category_filter_type', 'all' ) && sizeof( $args['search_categories'] ) > 1 ? 'AND' : 'IN';
			$query_args['tax_query'][] = array(
				'taxonomy'         => 'restaurant_listings_category',
				'field'            => $field,
				'terms'            => array_values( $args['search_categories'] ),
				'include_children' => 'AND' !== $operator,
				'operator'         => $operator,
			);
		}

		if ( 'featured' === $args['orderby'] ) {
			$query_args['orderby'] = array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			);
		}

		if ( 'rand_featured' === $args['orderby'] ) {
			$query_args['orderby'] = array(
				'menu_order' => 'ASC',
				'rand'       => 'ASC',
			);
		}

		$restaurant_listings_keyword = sanitize_text_field( $args['search_keywords'] );

		if ( ! empty( $restaurant_listings_keyword ) && strlen( $restaurant_listings_keyword ) >= apply_filters( 'restaurant_listings_get_listings_keyword_length_threshold', 2 ) ) {
			$query_args['s'] = $restaurant_listings_keyword;
			add_filter( 'posts_search', 'get_restaurant_listings_keyword_search' );
		}

		$query_args = apply_filters( 'restaurant_listings_get_listings', $query_args, $args );

		if ( empty( $query_args['meta_query'] ) ) {
			unset( $query_args['meta_query'] );
		}

		if ( empty( $query_args['tax_query'] ) ) {
			unset( $query_args['tax_query'] );
		}

		/** This filter is documented in wp-restaurant-listings.php */
		$query_args['lang'] = apply_filters( 'wprl_lang', null );

		// Filter args.
		$query_args = apply_filters( 'get_restaurant_listings_query_args', $query_args, $args );

		do_action( 'before_get_restaurant_listings', $query_args, $args );

		// Cache results.
		if ( apply_filters( 'get_restaurant_listings_cache_results', true ) ) {

			// Generate hash.
			$to_hash              = json_encode( $query_args );
			$query_args_hash      = 'jm_' . md5( $to_hash ) . WP_Restaurant_Listings_Cache_Helper::get_transient_version( 'get_restaurant_listings' );
			$result               = false;
			$cached_query_results = false;
			$cached_query_posts   = get_transient( $query_args_hash );

			if ( is_string( $cached_query_posts ) ) {
				$cached_query_posts = json_decode( $cached_query_posts, false );
				if ( $cached_query_posts
					&& is_object( $cached_query_posts )
					&& isset( $cached_query_posts->max_num_pages )
					&& isset( $cached_query_posts->found_posts )
					&& isset( $cached_query_posts->posts )
					&& is_array( $cached_query_posts->posts )
				) {
					$posts  = array_map( 'get_post', $cached_query_posts->posts );
					$result = new WP_Query();
					$result->parse_query( $query_args );
					$result->posts         = $posts;
					$result->found_posts   = intval( $cached_query_posts->found_posts );
					$result->max_num_pages = intval( $cached_query_posts->max_num_pages );
					$result->post_count    = count( $posts );
				}
			}

			if ( false === $result ) {
				$result                            = new WP_Query( $query_args );
				$cached_query_results              = false;
				$cacheable_result                  = array();
				$cacheable_result['posts']         = array_values( $result->posts );
				$cacheable_result['found_posts']   = $result->found_posts;
				$cacheable_result['max_num_pages'] = $result->max_num_pages;
				set_transient( $query_args_hash, json_encode( $cacheable_result ), DAY_IN_SECONDS );
			}

			if ( $cached_query_results ) {
				// random order is cached so shuffle them.
				if ( 'rand_featured' === $args['orderby'] ) {
					usort( $result->posts, '_wprl_shuffle_featured_post_results_helper' );
				} elseif ( 'rand' === $args['orderby'] ) {
					shuffle( $result->posts );
				}
			}
		} else {
			$result = new WP_Query( $query_args );
		}

		do_action( 'after_get_restaurant_listings', $query_args, $args );

		remove_filter( 'posts_search', 'get_restaurant_listings_keyword_search' );

		return $result;
	}
endif;

if ( ! function_exists( '_wprl_shuffle_featured_post_results_helper' ) ) :
	/**
	 * Helper function to maintain featured status when shuffling results.
	 *
	 * @param WP_Post $a
	 * @param WP_Post $b
	 *
	 * @return bool
	 */
	function _wprl_shuffle_featured_post_results_helper( $a, $b ) {
		if ( -1 === $a->menu_order || -1 === $b->menu_order ) {
			// Left is featured.
			if ( 0 === $b->menu_order ) {
				return -1;
			}
			// Right is featured.
			if ( 0 === $a->menu_order ) {
				return 1;
			}
		}
		return rand( -1, 1 );
	}
endif;

if ( ! function_exists( 'get_restaurant_listings_keyword_search' ) ) :
	/**
	 * Adds join and where query for keywords.
	 *
	 * @since 1.0.0
	 * @since 1.0.0 Moved from the `posts_clauses` filter to the `posts_search` to use WP Query's keyword
	 *               search for `post_title` and `post_content`.
	 * @param string $search
	 * @return string
	 */
	function get_restaurant_listings_keyword_search( $search ) {
		global $wpdb, $restaurant_listings_keyword;

		// Searchable Meta Keys: set to empty to search all meta keys
		$searchable_meta_keys = array(
			'_restaurant_location',
			'_restaurant_name',
			'_application',
			'_restaurant_name',
			'_restaurant_tagline',
			'_restaurant_website',
			'_restaurant_twitter',
		);

		$searchable_meta_keys = apply_filters( 'restaurant_listings_searchable_meta_keys', $searchable_meta_keys );

		// Set Search DB Conditions
		$conditions   = array();

		// Search Post Meta
		if( apply_filters( 'restaurant_listings_search_post_meta', true ) ) {

			// Only selected meta keys
			if( $searchable_meta_keys ) {
				$conditions[] = "{$wpdb->posts}.ID IN ( SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key IN ( '" . implode( "','", array_map( 'esc_sql', $searchable_meta_keys ) ) . "' ) AND meta_value LIKE '%" . esc_sql( $restaurant_listings_keyword ) . "%' )";
			} else {
				// No meta keys defined, search all post meta value
				$conditions[] = "{$wpdb->posts}.ID IN ( SELECT post_id FROM {$wpdb->postmeta} WHERE meta_value LIKE '%" . esc_sql( $restaurant_listings_keyword ) . "%' )";
			}
		}

		// Search taxonomy
		$conditions[] = "{$wpdb->posts}.ID IN ( SELECT object_id FROM {$wpdb->term_relationships} AS tr LEFT JOIN {$wpdb->term_taxonomy} AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id LEFT JOIN {$wpdb->terms} AS t ON tt.term_id = t.term_id WHERE t.name LIKE '%" . esc_sql( $restaurant_listings_keyword ) . "%' )";

		/**
		 * Filters the conditions to use when querying restaurant listings. Resulting array is joined with OR statements.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $conditions          Conditions to join by OR when querying restaurant listings.
		 * @param string $restaurant_listings_keyword Search query.
		 */
		$conditions = apply_filters( 'restaurant_listings_search_conditions', $conditions, $restaurant_listings_keyword );
		if ( empty( $conditions ) ) {
			return $search;
		}

		$conditions_str = implode( ' OR ', $conditions );

		if ( ! empty( $search ) ) {
			$search = preg_replace( '/^ AND /', '', $search );
			$search = " AND ( {$search} OR ( {$conditions_str} ) )";
		} else {
			$search = " AND ( {$conditions_str} )";
		}

		return $search;
	}
endif;

if ( ! function_exists( 'get_restaurant_listings_post_statuses' ) ) :
/**
 * Gets post statuses used for restaurants.
 *
 * @since 1.0.0
 * @return array
 */
function get_restaurant_listings_post_statuses() {
	return apply_filters( 'restaurant_listings_post_statuses', array(
		'draft'           => _x( 'Draft', 'post status', 'wp-restaurant-listings' ),
		'preview'         => _x( 'Preview', 'post status', 'wp-restaurant-listings' ),
		'pending'         => _x( 'Pending approval', 'post status', 'wp-restaurant-listings' ),
		'pending_payment' => _x( 'Pending payment', 'post status', 'wp-restaurant-listings' ),
		'publish'         => _x( 'Active', 'post status', 'wp-restaurant-listings' ),
	) );
}
endif;

if ( ! function_exists( 'get_featured_restaurant_ids' ) ) :
/**
 * Gets the ids of featured restaurants.
 *
 * @since 1.0.0
 * @return array
 */
function get_featured_restaurant_ids() {
	return get_posts( array(
		'posts_per_page' => -1,
		'post_type'      => 'restaurant_listings',
		'post_status'    => 'publish',
		'meta_key'       => '_featured',
		'meta_value'     => '1',
		'fields'         => 'ids'
	) );
}
endif;

if ( ! function_exists( 'get_restaurant_listings_types' ) ) :
/**
 * Gets restaurant listings types.
 *
 * @since 1.0.0
 * @param string|array $fields
 * @return array
 */
function get_restaurant_listings_types( $fields = 'all' ) {
	if ( ! get_option( 'restaurant_listings_enable_types' ) ) {
		return array();
	} else {
		$args = array(
			'fields'     => $fields,
			'hide_empty' => false,
			'order'      => 'ASC',
			'orderby'    => 'name'
		);

		$args = apply_filters( 'get_restaurant_listings_types_args', $args );

		// Prevent users from filtering the taxonomy
		$args['taxonomy'] = 'restaurant_listings_type';

		return get_terms( $args );
	}
}
endif;

if ( ! function_exists( 'get_restaurant_listings_categories' ) ) :
/**
 * Gets restaurant categories.
 *
 * @since 1.0.0
 * @return array
 */
function get_restaurant_listings_categories() {
	if ( ! get_option( 'restaurant_listings_enable_categories' ) ) {
		return array();
	}

	return get_terms( "restaurant_listings_category", array(
		'orderby'       => 'name',
		'order'         => 'ASC',
		'hide_empty'    => false,
	) );
}
endif;

if ( ! function_exists( 'restaurant_listings_get_filtered_links' ) ) :
/**
 * Shows links after filtering restaurants
 *
 * @since 1.0.0
 * @param array $args
 * @return string
 */
function restaurant_listings_get_filtered_links( $args = array() ) {
	$restaurant_categories = array();
	$types          = get_restaurant_listings_types();

	// Convert to slugs
	if ( $args['search_categories'] ) {
		foreach ( $args['search_categories'] as $category ) {
			if ( is_numeric( $category ) ) {
				$category_object = get_term_by( 'id', $category, 'restaurant_listings_category' );
				if ( ! is_wp_error( $category_object ) ) {
					$restaurant_categories[] = $category_object->slug;
				}
			} else {
				$restaurant_categories[] = $category;
			}
		}
	}

	$links = apply_filters( 'restaurant_listings_restaurant_filters_showing_restaurants_links', array(
		'reset' => array(
			'name' => __( 'Reset', 'wp-restaurant-listings' ),
			'url'  => '#'
		),
		'rss_link' => array(
			'name' => __( 'RSS', 'wp-restaurant-listings' ),
			'url'  => get_restaurant_listings_rss_link( apply_filters( 'restaurant_listings_get_listings_custom_filter_rss_args', array(
				'restaurant_types'       => isset( $args['filter_restaurant_types'] ) ? implode( ',', $args['filter_restaurant_types'] ) : '',
				'search_location' => $args['search_location'],
				'restaurant_categories'  => implode( ',', $restaurant_categories ),
				'search_keywords' => $args['search_keywords'],
			) ) )
		)
	), $args );

	if ( sizeof( $args['filter_restaurant_types'] ) === sizeof( $types ) && ! $args['search_keywords'] && ! $args['search_location'] && ! $args['search_categories'] && ! $args['search_price_range'] && ! apply_filters( 'restaurant_listings_get_listings_custom_filter', false ) ) {
		unset( $links['reset'] );
	}

	$return = '';

	foreach ( $links as $key => $link ) {
		$return .= '<a href="' . esc_url( $link['url'] ) . '" class="' . esc_attr( $key ) . '">' . $link['name'] . '</a>';
	}

	return $return;
}
endif;

if ( ! function_exists( 'get_restaurant_listings_rss_link' ) ) :
/**
 * Get the Restaurant Listings RSS link
 *
 * @since 1.0.0
 * @param array $args
 * @return string
 */
function get_restaurant_listings_rss_link( $args = array() ) {
	$rss_link = add_query_arg( urlencode_deep( array_merge( array( 'feed' => 'restaurant_feed' ), $args ) ), home_url() );
	return $rss_link;
}
endif;

if ( ! function_exists( 'wp_restaurant_listings_notify_new_user' ) ) :
	/**
	 * Handles notification of new users.
	 *
	 * @since 1.0.0
	 * @param  int         $user_id
	 * @param  string|bool $password
	 */
	function wp_restaurant_listings_notify_new_user( $user_id, $password ) {
		global $wp_version;

		if ( version_compare( $wp_version, '4.3.1', '<' ) ) {
			wp_new_user_notification( $user_id, $password );
		} else {
			$notify = 'admin';
			if ( empty( $password ) ) {
				$notify = 'both';
			}
			wp_new_user_notification( $user_id, null, $notify );
		}
	}
endif;

if ( ! function_exists( 'restaurant_listings_create_account' ) ) :
/**
 * Handles account creation.
 *
 * @since 1.0.0
 * @param  string|array|object $args containing username, email, role
 * @param  string              $deprecated role string
 * @return WP_Error|bool was an account created?
 */
function wp_restaurant_listings_create_account( $args, $deprecated = '' ) {
	global $current_user;

	// Soft Deprecated in 1.20.0
	if ( ! is_array( $args ) ) {
		$username = '';
		$password = false;
		$email    = $args;
		$role     = $deprecated;
	} else {
		$defaults = array(
			'username' => '',
			'email'    => '',
			'password' => false,
			'role'     => get_option( 'default_role' )
		);

		$args = wp_parse_args( $args, $defaults );
		extract( $args );
	}

	$username = sanitize_user( $username );
	$email    = apply_filters( 'user_registration_email', sanitize_email( $email ) );

	if ( empty( $email ) ) {
		return new WP_Error( 'validation-error', __( 'Invalid email address.', 'wp-restaurant-listings' ) );
	}

	if ( empty( $username ) ) {
		$username = sanitize_user( current( explode( '@', $email ) ) );
	}

	if ( ! is_email( $email ) ) {
		return new WP_Error( 'validation-error', __( 'Your email address isn&#8217;t correct.', 'wp-restaurant-listings' ) );
	}

	if ( email_exists( $email ) ) {
		return new WP_Error( 'validation-error', __( 'This email is already registered, please choose another one.', 'wp-restaurant-listings' ) );
	}

	// Ensure username is unique
	$append     = 1;
	$o_username = $username;

	while ( username_exists( $username ) ) {
		$username = $o_username . $append;
		$append ++;
	}

	// Final error checking
	$reg_errors = new WP_Error();
	$reg_errors = apply_filters( 'restaurant_listings_registration_errors', $reg_errors, $username, $email );

	do_action( 'restaurant_listings_register_post', $username, $email, $reg_errors );

	if ( $reg_errors->get_error_code() ) {
		return $reg_errors;
	}

	// Create account
	$new_user = array(
		'user_login' => $username,
		'user_pass'  => $password,
		'user_email' => $email,
		'role'       => $role,
	);

	// User is forced to set up account with email sent to them. This password will remain a secret.
	if ( empty( $new_user['user_pass'] ) ) {
		$new_user['user_pass'] = wp_generate_password();
	}

	$user_id = wp_insert_user( apply_filters( 'restaurant_listings_create_account_data', $new_user ) );

	if ( is_wp_error( $user_id ) ) {
		return $user_id;
	}

	/**
	 * Send notification to new users.
	 *
	 * @since 1.28.0
	 *
	 * @param  int         $user_id
	 * @param  string|bool $password
	 * @param  array       $new_user {
	 *     Information about the new user.
	 *
	 *     @type string $user_login Username for the user.
	 *     @type string $user_pass  Password for the user (may be blank).
	 *     @type string $user_email Email for the new user account.
	 *     @type string $role       New user's role.
	 * }
	 */
	do_action( 'wprl_notify_new_user', $user_id, $password, $new_user );

	// Login
	wp_set_auth_cookie( $user_id, true, is_ssl() );
	$current_user = get_user_by( 'id', $user_id );

	return true;
}
endif;

/**
 * Checks if the user can upload a file via the Ajax endpoint.
 *
 * @since 1.0.0
 * @return bool
 */
function restaurant_listings_user_can_upload_file_via_ajax() {
	$can_upload = is_user_logged_in() && restaurant_listings_user_can_post_restaurant();

	/**
	 * Override ability of a user to upload a file via Ajax.
	 *
	 * @since 1.0.0
	 * @param bool $can_upload True if they can upload files from Ajax endpoint.
	 */
	return apply_filters( 'restaurant_listings_user_can_upload_file_via_ajax', $can_upload );
}

/**
 * Checks if an the user can post a restaurant. If accounts are required, and reg is enabled, users can post (they signup at the same time).
 *
 * @since 1.0.0
 * @return bool
 */
function restaurant_listings_user_can_post_restaurant() {
	$can_post = true;

	if ( ! is_user_logged_in() ) {
		if ( restaurant_listings_user_requires_account() && ! restaurant_listings_enable_registration() ) {
			$can_post = false;
		}
	}

	return apply_filters( 'restaurant_listings_user_can_post_restaurant', $can_post );
}

/**
 * Checks if the user can edit a restaurant.
 *
 * @since 1.0.0
 * @param int|WP_Post $restaurant_id
 * @return bool
 */
function restaurant_listings_user_can_edit_restaurant( $restaurant_id ) {
	$can_edit = true;

	if ( ! is_user_logged_in() || ! $restaurant_id ) {
		$can_edit = false;
	} else {
		$restaurant      = get_post( $restaurant_id );

		if ( ! $restaurant || ( absint( $restaurant->post_author ) !== get_current_user_id() && ! current_user_can( 'edit_post', $restaurant_id ) ) ) {
			$can_edit = false;
		}
	}

	return apply_filters( 'restaurant_listings_user_can_edit_restaurant', $can_edit, $restaurant_id );
}

/**
 * Checks to see if the standard password setup email should be used.
 *
 * @since 1.0.0
 *
 * @return bool True if they are to use standard email, false to allow user to set password at first restaurant creation.
 */
function wprl_use_standard_password_setup_email() {
	$use_standard_password_setup_email = true;

	// If username is being automatically generated, force them to send password setup email.
	if ( ! restaurant_listings_generate_username_from_email() ) {
		$use_standard_password_setup_email = get_option( 'restaurant_listings_use_standard_password_setup_email' ) == 1 ? true : false;
	}

	/**
	 * Allows an override of the setting for if a password should be auto-generated for new users.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $use_standard_password_setup_email True if a standard account setup email should be sent.
	 */
	return apply_filters( 'wprl_use_standard_password_setup_email', $use_standard_password_setup_email );
}

/**
 * Checks if a password should be auto-generated for new users.
 *
 * @since 1.0.0
 *
 * @param string $password Password to validate.
 * @return bool True if password meets rules.
 */
function wprl_validate_new_password( $password ) {
	// Password must be at least 8 characters long. Trimming here because `wp_hash_password()` will later on.
	$is_valid_password = strlen( trim ( $password ) ) >= 8;

	/**
	 * Allows overriding default WPRL password validation rules.
	 *
	 * @since 1.0.0
	 *
	 * @param bool   $is_valid_password True if new password is validated.
	 * @param string $password          Password to validate.
	 */
	return apply_filters( 'wprl_validate_new_password', $is_valid_password, $password );
}

/**
 * Returns the password rules hint.
 *
 * @return string
 */
function wprl_get_password_rules_hint() {
	/**
	 * Allows overriding the hint shown below the new password input field. Describes rules set in `wprl_validate_new_password`.
	 *
	 * @since 1.0.0
	 *
	 * @param string $password_rules Password rules description.
	 */
	return apply_filters( 'wprl_password_rules_hint', __( 'Passwords must be at least 8 characters long.', 'wp-restaurant-listings') );
}

/**
 * Checks if only one type allowed per restaurant.
 *
 * @since 1.0.0
 * @return bool
 */
function restaurant_listings_multi_restaurant_type() {
	return apply_filters( 'restaurant_listings_multi_restaurant_type', get_option( 'restaurant_listings_multi_restaurant_type' ) == 1 ? true : false );
}

/**
 * Checks if registration is enabled.
 *
 * @since 1.0.0
 * @return bool
 */
function restaurant_listings_enable_registration() {
	return apply_filters( 'restaurant_listings_enable_registration', get_option( 'restaurant_listings_enable_registration' ) == 1 ? true : false );
}

/**
 * Checks if usernames are generated from email addresses.
 *
 * @since 1.0.0
 * @return bool
 */
function restaurant_listings_generate_username_from_email() {
	return apply_filters( 'restaurant_listings_generate_username_from_email', get_option( 'restaurant_listings_generate_username_from_email' ) == 1 ? true : false );
}

/**
 * Checks if an account is required to post a restaurant.
 *
 * @since 1.0.0
 * @return bool
 */
function restaurant_listings_user_requires_account() {
	return apply_filters( 'restaurant_listings_user_requires_account', get_option( 'restaurant_listings_user_requires_account' ) == 1 ? true : false );
}

/**
 * Checks if users are allowed to edit submissions that are pending approval.
 *
 * @since 1.0.0
 * @return bool
 */
function restaurant_listings_user_can_edit_pending_submissions() {
	return apply_filters( 'restaurant_listings_user_can_edit_pending_submissions', get_option( 'restaurant_listings_user_can_edit_pending_submissions' ) == 1 ? true : false );
}

/**
 * Displays category select dropdown.
 *
 * Based on wp_dropdown_categories, with the exception of supporting multiple selected categories.
 *
 * @since 1.0.0
 * @see  wp_dropdown_categories
 * @param string|array|object $args
 * @return string
 */
function restaurant_listings_dropdown_categories( $args = '' ) {
	$defaults = array(
		'orderby'         => 'id',
		'order'           => 'ASC',
		'show_count'      => 0,
		'hide_empty'      => 1,
		'child_of'        => 0,
		'exclude'         => '',
		'echo'            => 1,
		'selected'        => 0,
		'hierarchical'    => 0,
		'name'            => 'cat',
		'id'              => '',
		'class'           => 'restaurant-listings-category-dropdown ' . ( is_rtl() ? 'select2-rtl' : '' ),
		'depth'           => 0,
		'taxonomy'        => 'restaurant_listings_category',
		'value'           => 'id',
		'multiple'        => true,
		'show_option_all' => false,
		'placeholder'     => __( 'Choose a category&hellip;', 'wp-restaurant-listings' ),
		'no_results_text' => __( 'No results match', 'wp-restaurant-listings' ),
		'multiple_text'   => __( 'Select Some Options', 'wp-restaurant-listings' ),
	);

	$r = wp_parse_args( $args, $defaults );

	if ( ! isset( $r['pad_counts'] ) && $r['show_count'] && $r['hierarchical'] ) {
		$r['pad_counts'] = true;
	}

	/** This filter is documented in wp-restaurant-listings.php */
	$r['lang'] = apply_filters( 'wprl_lang', null );

	extract( $r );

	// Store in a transient to help sites with many cats
	$categories_hash = 'jm_cats_' . md5( json_encode( $r ) . WP_Restaurant_Listings_Cache_Helper::get_transient_version( 'jm_get_' . $r['taxonomy'] ) );
	$categories      = get_transient( $categories_hash );

	if ( empty( $categories ) ) {
		$categories = get_terms( $taxonomy, array(
			'orderby'         => $r['orderby'],
			'order'           => $r['order'],
			'hide_empty'      => $r['hide_empty'],
			'child_of'        => $r['child_of'],
			'exclude'         => $r['exclude'],
			'hierarchical'    => $r['hierarchical']
		) );
		set_transient( $categories_hash, $categories, DAY_IN_SECONDS * 7 );
	}

	$name       = esc_attr( $name );
	$class      = esc_attr( $class );
	$id         = $id ? esc_attr( $id ) : $name;

	$output = "<select name='" . esc_attr( $name ) . "[]' id='" . esc_attr( $id ) . "' class='" . esc_attr( $class ) . "' " . ( $multiple ? "multiple='multiple'" : '' ) . " data-placeholder='" . esc_attr( $placeholder ) . "' data-no_results_text='" . esc_attr( $no_results_text ) . "' data-multiple_text='" . esc_attr( $multiple_text ) . "'>\n";

	if ( $show_option_all ) {
		$output .= '<option value="">' . esc_html( $show_option_all ) . '</option>';
	}

	if ( ! empty( $categories ) ) {
		include_once( RESTAURANT_LISTING_PLUGIN_DIR . '/includes/class-wp-restaurant-listings-category-walker.php' );

		$walker = new WP_Restaurant_Listings_Category_Walker;

		if ( $hierarchical ) {
			$depth = $r['depth'];  // Walk the full depth.
		} else {
			$depth = -1; // Flat.
		}

		$output .= $walker->walk( $categories, $depth, $r );
	}

	$output .= "</select>\n";

	if ( $echo ) {
		echo $output;
	}

	return $output;
}

/**
 * Gets the page ID of a page if set.
 *
 * @since 1.0.0
 * @param  string $page e.g. restaurant_dashboard, submit_restaurant_form, restaurants
 * @return int
 */
function restaurant_listings_get_page_id( $page ) {
	$page_id = get_option( 'restaurant_listings_' . $page . '_page_id', false );
	if ( $page_id ) {
		/**
		 * Filters the page ID for a WPRL page.
		 *
		 * @since 1.0.0
		 *
		 * @param int $page_id
		 */
		return apply_filters( 'wprl_page_id', $page_id );
	} else {
		return 0;
	}
}

/**
 * Gets the permalink of a page if set.
 *
 * @since 1.0.0
 * @param  string $page e.g. restaurant_dashboard, submit_restaurant_form, restaurants
 * @return string|bool
 */
function restaurant_listings_get_permalink( $page ) {
	if ( $page_id = restaurant_listings_get_page_id( $page ) ) {
		return get_permalink( $page_id );
	} else {
		return false;
	}
}

/**
 * Prepares files for upload by standardizing them into an array. This adds support for multiple file upload fields.
 *
 * @since 1.0.0
 * @param  array $file_data
 * @return array
 */
function restaurant_listings_prepare_uploaded_files( $file_data ) {
	$files_to_upload = array();

	if ( is_array( $file_data['name'] ) ) {
		foreach( $file_data['name'] as $file_data_key => $file_data_value ) {
			if ( $file_data['name'][ $file_data_key ] ) {
				$type              = wp_check_filetype( $file_data['name'][ $file_data_key ] ); // Map mime type to one WordPress recognises
				$files_to_upload[] = array(
					'name'     => $file_data['name'][ $file_data_key ],
					'type'     => $type['type'],
					'tmp_name' => $file_data['tmp_name'][ $file_data_key ],
					'error'    => $file_data['error'][ $file_data_key ],
					'size'     => $file_data['size'][ $file_data_key ]
				);
			}
		}
	} else {
		$type              = wp_check_filetype( $file_data['name'] ); // Map mime type to one WordPress recognises
		$file_data['type'] = $type['type'];
		$files_to_upload[] = $file_data;
	}

	return apply_filters( 'restaurant_listings_prepare_uploaded_files', $files_to_upload );
}

/**
 * Uploads a file using WordPress file API.
 *
 * @since 1.0.0
 * @param  array|WP_Error      $file Array of $_FILE data to upload.
 * @param  string|array|object $args Optional arguments
 * @return stdClass|WP_Error Object containing file information, or error
 */
function restaurant_listings_upload_file( $file, $args = array() ) {
	global $restaurant_listings_upload, $restaurant_listings_uploading_file;

	include_once( ABSPATH . 'wp-admin/includes/file.php' );
	include_once( ABSPATH . 'wp-admin/includes/media.php' );

	$args = wp_parse_args( $args, array(
		'file_key'           => '',
		'file_label'         => '',
		'allowed_mime_types' => '',
	) );

	//$restaurant_listings_upload         = true;
	$restaurant_listings_uploading_file = $args['file_key'];
	$uploaded_file              = new stdClass();
	if ( '' === $args['allowed_mime_types'] ) {
		$allowed_mime_types = restaurant_listings_get_allowed_mime_types( $restaurant_listings_uploading_file );
	} else {
		$allowed_mime_types = $args['allowed_mime_types'];
	}

	/**
	 * Filter file configuration before upload
	 *
	 * This filter can be used to modify the file arguments before being uploaded, or return a WP_Error
	 * object to prevent the file from being uploaded, and return the error.
	 *
	 * @since 1.0.0
	 *
	 * @param array $file               Array of $_FILE data to upload.
	 * @param array $args               Optional file arguments
	 * @param array $allowed_mime_types Array of allowed mime types from field config or defaults
	 */
	$file = apply_filters( 'restaurant_listings_upload_file_pre_upload', $file, $args, $allowed_mime_types );

	if ( is_wp_error( $file ) ) {
		return $file;
	}

	if ( ! in_array( $file['type'], $allowed_mime_types ) ) {
		if ( $args['file_label'] ) {
			return new WP_Error( 'upload', sprintf( __( '"%s" (filetype %s) needs to be one of the following file types: %s', 'wp-restaurant-listings' ), $args['file_label'], $file['type'], implode( ', ', array_keys( $allowed_mime_types ) ) ) );
		} else {
			return new WP_Error( 'upload', sprintf( __( 'Uploaded files need to be one of the following file types: %s', 'wp-restaurant-listings' ), implode( ', ', array_keys( $allowed_mime_types ) ) ) );
		}
	} else {
		$upload = wp_handle_upload( $file, apply_filters( 'submit_restaurant_wp_handle_upload_overrides', array( 'test_form' => false ) ) );
		if ( ! empty( $upload['error'] ) ) {
			return new WP_Error( 'upload', $upload['error'] );
		} else {
			$uploaded_file->url       = $upload['url'];
			$uploaded_file->file      = $upload['file'];
			$uploaded_file->name      = basename( $upload['file'] );
			$uploaded_file->type      = $upload['type'];
			$uploaded_file->size      = $file['size'];
			$uploaded_file->extension = substr( strrchr( $uploaded_file->name, '.' ), 1 );
		}
	}

	//$restaurant_listings_upload         = false;
	$restaurant_listings_uploading_file = '';

	return $uploaded_file;
}

/**
 * Returns mime types specifically for WPRL.
 *
 * @since 1.0.0
 * @param   string $field Field used.
 * @return  array  Array of allowed mime types
 */
function restaurant_listings_get_allowed_mime_types( $field = '' ){
	if ( 'restaurant_logo' === $field ) {
		$allowed_mime_types = array(
			'jpg|jpeg|jpe' => 'image/jpeg',
			'gif'          => 'image/gif',
			'png'          => 'image/png',
		);
	} else {
		$allowed_mime_types = array(
			'jpg|jpeg|jpe' => 'image/jpeg',
			'gif'          => 'image/gif',
			'png'          => 'image/png',
			'pdf'          => 'application/pdf',
			'doc'          => 'application/msword',
			'docx'         => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		);
	}

	/**
	 * Mime types to accept in uploaded files.
	 *
	 * Default is image, pdf, and doc(x) files.
	 *
	 * @since 1.0.0
	 *
	 * @param array  {
	 *     Array of allowed file extensions and mime types.
	 *     Key is pipe-separated file extensions. Value is mime type.
	 * }
	 * @param string $field The field key for the upload.
	 */
	return apply_filters( 'restaurant_listings_mime_types', $allowed_mime_types, $field );
}

/**
 * Duplicates a listings.
 *
 * @since 1.0.0
 * @param  int $post_id
 * @return int 0 on fail or the post ID.
 */
function restaurant_listings_duplicate_listing( $post_id ) {
	if ( empty( $post_id ) || ! ( $post = get_post( $post_id ) ) ) {
		return 0;
	}

	global $wpdb;

	/**
	 * Duplicate the post.
	 */
	$new_post_id = wp_insert_post( array(
		'comment_status' => $post->comment_status,
		'ping_status'    => $post->ping_status,
		'post_author'    => $post->post_author,
		'post_content'   => $post->post_content,
		'post_excerpt'   => $post->post_excerpt,
		'post_name'      => $post->post_name,
		'post_parent'    => $post->post_parent,
		'post_password'  => $post->post_password,
		'post_status'    => 'preview',
		'post_title'     => $post->post_title,
		'post_type'      => $post->post_type,
		'to_ping'        => $post->to_ping,
		'menu_order'     => $post->menu_order
	) );

	/**
	 * Copy taxonomies.
	 */
	$taxonomies = get_object_taxonomies( $post->post_type );

	foreach ( $taxonomies as $taxonomy ) {
		$post_terms = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'slugs' ) );
		wp_set_object_terms( $new_post_id, $post_terms, $taxonomy, false );
	}

	/*
	 * Duplicate post meta, aside from some reserved fields.
	 */
	$post_meta = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id=%d", $post_id ) );

	if ( ! empty( $post_meta ) ) {
		$post_meta = wp_list_pluck( $post_meta, 'meta_value', 'meta_key' );
		foreach ( $post_meta as $meta_key => $meta_value ) {
			if ( in_array( $meta_key, apply_filters( 'restaurant_listings_duplicate_listings_ignore_keys', array( '_featured', '_restaurant_duration', '_package_id', '_user_package_id' ) ) ) ) {
				continue;
			}
			update_post_meta( $new_post_id, $meta_key, maybe_unserialize( $meta_value ) );
		}
	}

	update_post_meta( $new_post_id, '_featured', 0 );

	return $new_post_id;
}

/**
 * Get days of week
 *
 * @return array
 */
function restaurant_listings_get_days_of_week() {
	$days = array(0, 1, 2, 3, 4, 5, 6);
	$start = get_option( 'start_of_week' );

	$first = array_splice( $days, $start, count( $days ) - $start );
	$second = array_splice( $days, 0, $start );
	$days = array_merge( $first, $second );

	return $days;
}

/**
 * Returns the restaurant categories in a list.
 *
 * @param int $restaurant_id
 * @param string $sep (default: ', ').
 * @param string $before (default: '').
 * @param string $after (default: '').
 * @return string
 */
function restaurant_listings_category_list( $restaurant_id, $sep = ', ', $before = '', $after = '' ) {
	return get_the_term_list( $restaurant_id, 'restaurant_listings_category', $before, $sep, $after );
}

/**
 *  Get restaurant review count for a restaurant (not replies). Please note this is not cached.
 *
 * @param $post_id
 * @return null|string
 */
function restaurant_listings_get_review_count( $post_id ) {
	global $wpdb;

	$count = $wpdb->get_var( $wpdb->prepare("
			SELECT COUNT(*) FROM $wpdb->comments
			WHERE comment_parent = 0
			AND comment_post_ID = %d
			AND comment_approved = '1'
		", $post_id ) );

	return $count;
}


if ( ! function_exists( 'restaurant_listings_default_tabs' ) ) {

	/**
	 * Add default restaurant tabs to restaurant pages.
	 *
	 * @param array $tabs
	 * @return array
	 */
	function restaurant_listings_default_tabs( $tabs = array() ) {
		global  $post;

		$tabs['overview'] = array(
			'title'    => sprintf( __( 'Overview', 'wp-restaurant-listings' ) ),
			'priority' => 20,
			'callback' => 'restaurant_listings_overview_tab',
		);

		$tabs['menu'] = array(
			'title'    => sprintf( __( 'Menu', 'wp-restaurant-listings' ) ),
			'priority' => 30,
			'callback' => 'restaurant_listings_menu_tab',
		);


		// Reviews tab - shows comments
		if ( comments_open() ) {

			$tabs['reviews'] = array(
				'title'    => sprintf( __( 'Reviews (%d)', 'wp-restaurant-listings' ), restaurant_listings_get_review_count( $post->ID ) ),
				'priority' => 30,
				'callback' => 'comments_template',
			);
		}

		return $tabs;
	}
}
