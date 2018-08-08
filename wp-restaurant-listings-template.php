<?php
/**
 * Template Functions
 *
 * Template functions specifically created for restaurant listings
 *
 * @package RestaurantListings/Template
 * @version 1.0.0
 */

/**
 * Gets and includes template files.
 *
 * @since 1.0.0
 * @param mixed  $template_name
 * @param array  $args (default: array())
 * @param string $template_path (default: '')
 * @param string $default_path (default: '')
 */
function get_restaurant_listings_template( $template_name, $args = array(), $template_path = 'restaurant_listings', $default_path = '' ) {
	if ( $args && is_array( $args ) ) {
		extract( $args );
	}

	include locate_restaurant_listings_template( $template_name, $template_path, $default_path );
}

/**
 * Locates a template and return the path for inclusion.
 *
 * This is the load order:
 *
 *		yourtheme		/	$template_path	/	$template_name
 *		yourtheme		/	$template_name
 *		$default_path	/	$template_name
 *
 * @since 1.0.0
 * @param string      $template_name
 * @param string      $template_path (default: 'restaurant_listings')
 * @param string|bool $default_path (default: '') False to not load a default
 * @return string
 */
function locate_restaurant_listings_template( $template_name, $template_path = 'restaurant_listings', $default_path = '' ) {
	// Look within passed path within the theme - this is priority
	$template = locate_template(
		array(
			trailingslashit( $template_path ) . $template_name,
			$template_name
		)
	);

	// Get default template
	if ( ! $template && $default_path !== false ) {
		$default_path = $default_path ? $default_path : RESTAURANT_LISTING_PLUGIN_DIR . '/templates/';
		if ( file_exists( trailingslashit( $default_path ) . $template_name ) ) {
			$template = trailingslashit( $default_path ) . $template_name;
		}
	}


	// Return what we found
	return apply_filters( 'restaurant_listings_locate_template', $template, $template_name, $template_path );
}

/**
 * Gets template part (for templates in loops).
 *
 * @since 1.0.0
 * @param string      $slug
 * @param string      $name (default: '')
 * @param string      $template_path (default: 'restaurant_listings')
 * @param string|bool $default_path (default: '') False to not load a default
 */
function get_restaurant_listings_template_part( $slug, $name = '', $template_path = 'restaurant_listings', $default_path = '' ) {
	$template = '';

	if ( $name ) {
		$template = locate_restaurant_listings_template( "{$slug}-{$name}.php", $template_path, $default_path );
	}

	// If template file doesn't exist, look in yourtheme/slug.php and yourtheme/restaurant_listings/slug.php
	if ( ! $template ) {
		$template = locate_restaurant_listings_template( "{$slug}.php", $template_path, $default_path );
	}

	if ( $template ) {
		load_template( $template, false );
	}
}

/**
 * Adds custom body classes.
 *
 * @since 1.0.0
 * @param  array $classes
 * @return array
 */
function restaurant_listings_body_class( $classes ) {
	$classes   = (array) $classes;
	$classes[] = sanitize_title( wp_get_theme() );

	return array_unique( $classes );
}

add_filter( 'body_class', 'restaurant_listings_body_class' );

/**
 * Get restaurants pagination for [restaurants] shortcode.
 *
 * @since 1.0.0
 * @param int $max_num_pages
 * @param int $current_page
 * @return string
 */
function get_restaurant_listings_pagination( $max_num_pages, $current_page = 1 ) {
	ob_start();
	get_restaurant_listings_template( 'restaurant-pagination.php', array( 'max_num_pages' => $max_num_pages, 'current_page' => absint( $current_page ) ) );
	return ob_get_clean();
}

/**
 * Displays the restaurants status.
 *
 * @since 1.0.0
 * @param int|WP_Post $post
 */
function the_restaurant_status( $post = null ) {
	echo get_the_restaurant_status( $post );
}

/**
 * Gets the restaurants status.
 *
 * @since 1.0.0
 * @param int|WP_Post $post
 * @return string
 */
function get_the_restaurant_status( $post = null ) {
	$post     = get_post( $post );
	$status   = $post->post_status;
	$statuses = get_restaurant_listings_post_statuses();

	if ( isset( $statuses[ $status ] ) ) {
		$status = $statuses[ $status ];
	} else {
		$status = __( 'Inactive', 'wp-restaurant-listings' );
	}

	return apply_filters( 'the_restaurant_status', $status, $post );
}

/**
 * Checks whether or not the position has been featured.
 *
 * @since 1.0.0
 * @param  WP_Post|int $post
 * @return boolean
 */
function is_restaurant_featured( $post = null ) {
	$post = get_post( $post );
	return $post->_featured ? true : false;
}


/**
 * Displays the permalink for the restaurant listings post.
 *
 * @since 1.0.0
 * @param int|WP_Post $post (default: null)
 * @return void
 */
function the_restaurant_permalink( $post = null ) {
	echo get_the_restaurant_permalink( $post );
}

/**
 * Gets the permalink for a restaurant listings.
 *
 * @since 1.0.0
 * @param int|WP_Post $post (default: null)
 * @return string
 */
function get_the_restaurant_permalink( $post = null ) {
	$post = get_post( $post );
	$link = get_permalink( $post );

	return apply_filters( 'the_restaurant_permalink', $link, $post );
}

/**
 * Displays the restaurant title for the listings.
 *
 * @since 1.0.0
 * @param int|WP_Post $post
 * @return string
 */
function the_restaurant_title( $post = null ) {
	if ( $restaurant_title = get_the_restaurant_title( $post ) ) {
		echo $restaurant_title;
	}
}

/**
 * Gets the restaurant title for the listings.
 *
 * @since 1.0.0
 * @param int|WP_Post $post (default: null)
 * @return string|bool|null
 */
function get_the_restaurant_title( $post = null ) {
	$post = get_post( $post );
	if ( $post->post_type !== 'restaurant_listings' ) {
		return;
	}

	$title = esc_html( get_the_title( $post ) );

	/**
	 * Filter for the restaurant title.
	 *
	 * @since 1.0.0
	 * @param string      $title Title to be filtered.
	 * @param int|WP_Post $post
	 */
	return apply_filters( 'the_restaurant_title', $title, $post );
}

/**
 * Displays multiple restaurant types for the listings.
 *
 * @since 1.0.0
 *
 * @param int|WP_Post $post Current post object.
 * @param string      $separator String to join the term names with.
 */
function the_restaurant_types( $post = null, $separator = ', ' ) {
	if ( ! get_option( 'restaurant_listings_enable_types' ) ) {
		return;
	}

	$restaurant_types = get_the_restaurant_types( $post );

	if ( $restaurant_types ) {
		$names = wp_list_pluck( $restaurant_types, 'name' );

		echo esc_html( implode( $separator, $names ) );
	}
}

/**
 * Gets the restaurant type for the listings.
 *
 * @since 1.0.0
 *
 * @param int|WP_Post $post (default: null).
 * @return bool|array
 */
function get_the_restaurant_types( $post = null ) {
	$post = get_post( $post );

	if ( 'restaurant_listings' !== $post->post_type ) {
		return false;
	}

	$types = get_the_terms( $post->ID, 'restaurant_listings_type' );

	if ( empty( $types ) || is_wp_error( $types ) ) {
		$types = array();
	}

	// Return single if not enabled.
	if ( ! empty( $types ) && ! restaurant_listings_multi_restaurant_type() ) {
		$types = array( current( $types ) );
	}

	/**
	 * Filter the returned restaurant types for a post.
	 *
	 * @since 1.0.0
	 *
	 * @param array   $types
	 * @param WP_Post $post
	 */
	return apply_filters( 'the_restaurant_types', $types, $post );
}

/**
 * Restaurant price range
 *
 * @param  WP_Post $post post object.
 * @return void
 */
function the_restaurant_price_range( $post = null ) {
	$restaurant_price_range = get_the_restaurant_price_range( $post );
	if ( $restaurant_price_range ) {
		echo '<div class="price-range">' . $restaurant_price_range . '</div>';
	}
}

function get_the_restaurant_price_range( $post = null ) {
    $post = get_post( $post );

    if ( $post->post_type !== 'restaurant_listings' ) {
        return;
    }

    $currency_symbol = get_option('restaurant_listings_currency');

    return apply_filters( 'the_restaurant_price_range', str_repeat( $currency_symbol, absint( $post->_restaurant_price_range ) ), $post );
}

/**
 * Returns the registration fields used when an account is required.
 *
 * @since 1.0.0
 *
 * @return array $registration_fields
 */
function wprl_get_registration_fields() {
	$generate_username_from_email      = restaurant_listings_generate_username_from_email();
	$use_standard_password_setup_email = wprl_use_standard_password_setup_email();
	$account_required                  = restaurant_listings_user_requires_account();

	$registration_fields = array();
	if ( restaurant_listings_enable_registration() ) {
		if ( ! $generate_username_from_email ) {
			$registration_fields['create_account_username'] = array(
				'type'     => 'text',
				'label'    => __( 'Username', 'wp-restaurant-listings' ),
				'required' => $account_required,
				'value'    => isset( $_POST['create_account_username'] ) ? $_POST['create_account_username'] : '',
			);
		}
		if ( ! $use_standard_password_setup_email ) {
			$registration_fields['create_account_password'] = array(
				'type'         => 'password',
				'label'        => __( 'Password', 'wp-restaurant-listings' ),
				'autocomplete' => false,
				'required'     => $account_required,
			);
			$password_hint = wprl_get_password_rules_hint();
			if ( $password_hint ) {
				$registration_fields['create_account_password']['description'] = $password_hint;
			}
			$registration_fields['create_account_password_verify'] = array(
				'type'         => 'password',
				'label'        => __( 'Verify Password', 'wp-restaurant-listings' ),
				'autocomplete' => false,
				'required'     => $account_required,
			);
		}
		$registration_fields['create_account_email'] = array(
			'type'        => 'text',
			'label'       => __( 'Your email', 'wp-restaurant-listings' ),
			'placeholder' => __( 'you@yourdomain.com', 'wp-restaurant-listings' ),
			'required'    => $account_required,
			'value'       => isset( $_POST['create_account_email'] ) ? $_POST['create_account_email'] : '',
		);
	}

	/**
	 * Filters the fields used at registration.
	 *
	 * @since 1.0.0
	 *
	 * @param array $registration_fields
	 */
	return apply_filters( 'wprl_get_registration_fields', $registration_fields );
}

/**
 * Displays the published date of the restaurant listings.
 *
 * @since 1.0.0
 * @param int|WP_Post $post (default: null)
 */
function the_restaurant_publish_date( $post = null ) {
	$date_format = get_option( 'restaurant_listings_date_format' );

	if ( 'default' === $date_format ) {
		$display_date = __( 'Posted on ', 'wp-restaurant-listings' ) . date_i18n( get_option( 'date_format' ), get_post_time( 'U' ) );
	} else {
		$display_date = sprintf( __( 'Posted %s ago', 'wp-restaurant-listings' ), human_time_diff( get_post_time( 'U' ), current_time( 'timestamp' ) ) );
	}

	echo '<time datetime="' . get_post_time( 'Y-m-d' ) . '">' . $display_date . '</time>';
}


/**
 * Gets the published date of the restaurant listings.
 *
 * @since 1.0.0
 * @param int|WP_Post $post (default: null)
 * @return string|int|false
 */
function get_the_restaurant_publish_date( $post = null ) {
	$date_format = get_option( 'restaurant_listings_date_format' );

	if ( $date_format === 'default' ) {
		return get_post_time( get_option( 'date_format' ) );
	} else {
		return sprintf( __( 'Posted %s ago', 'wp-restaurant-listings' ), human_time_diff( get_post_time( 'U' ), current_time( 'timestamp' ) ) );
	}
}


/**
 * Displays the location for the restaurant listings.
 *
 * @since 1.0.0
 * @param  bool        $map_link whether or not to link to Google Maps
 * @param int|WP_Post $post
 */
function the_restaurant_location( $map_link = true, $post = null ) {
	$location = get_the_restaurant_location( $post );

	if ( $location ) {
		if ( $map_link ) {
			// If linking to google maps, we don't want anything but text here
			echo apply_filters( 'the_restaurant_location_map_link', '<a class="google_map_link" href="' .  get_the_restaurant_direction_link() .'" target="_blank">' . esc_html( strip_tags( $location ) ) . '</a>', $location, $post );
		} else {
			echo wp_kses_post( $location );
		}
	} else {
		echo wp_kses_post( apply_filters( 'the_restaurant_location_anywhere_text', __( 'Anywhere', 'wp-restaurant-listings' ) ) );
	}
}

function get_the_restaurant_direction_link() {
    $location = get_the_restaurant_location();
    return apply_filters( 'get_the_restaurant_direction_link', esc_url( 'http://maps.google.com/maps?q=' . urlencode( strip_tags( $location ) ) . '&zoom=14&size=512x512&maptype=roadmap&sensor=false' ) );
}

/**
 * Gets the location for the restaurant listings.
 *
 * @since 1.0.0
 * @param int|WP_Post $post (default: null)
 * @return string|null
 */
function get_the_restaurant_location( $post = null ) {
	$post = get_post( $post );
	if ( $post->post_type !== 'restaurant_listings' ) {
		return;
	}

	return apply_filters( 'the_restaurant_location', $post->_restaurant_location, $post );
}

/**
 * @param null $post
 */
function the_restaurant_street( $post = null ) {
 if ( $street = get_the_restaurant_street( $post ) ) {
     echo '<span class="neighborhood-str-list">'. $street.'</span>';
 }
}

/**
 * @param null $post
 * @return mixed|void
 */
function get_the_restaurant_street( $post = null ) {
    $post = get_post( $post );

    if ( $post->post_type !== 'restaurant_listings' ) {
        return;
    }

    return apply_filters( 'get_the_restaurant_street', $post->geolocation_street, $post );
}


/**
 * @param null $post
 */
function the_restaurant_latest_story( $post = null ) {

	$comment = get_the_restaurant_latest_story( $post );
    if ( $comment instanceof WP_Comment ) {
        ?>
        <div id="comment-<?php $comment->comment_ID ?>" class="comment_container">

            <div class="comment-avatar">
                <?php
                echo get_avatar( $comment, apply_filters( 'restaurant_listings_review_gravatar_size', '60' ), '' );
                ?>
            </div>


            <div class="comment-text">
               <div class="discription">
                <?php echo $comment->comment_content ?>
               </div>
            </div>
        </div>

        <?php
    }

}

/**
 * @param null $post
 * @return mixed|void
 */
function get_the_restaurant_latest_story( $post = null ) {
    $post = get_post( $post );
    $comment = new stdClass();

    if ( $post->post_type !== 'restaurant_listings' ) {
        return;
    }

    $comments = get_comments(array( 'status' => 'approve', 'post_id' => $post->ID, 'number' => 1,  'orderby' => array('comment_date'), 'order' => 'DESC' ) );

    is_array($comments) && sizeof($comments) > 0 && $comment = $comments[0];

    return apply_filters( 'the_restaurant_location', $comment, $post );
}

/**
 * Displays the restaurant logo.
 *
 * @since 1.0.0
 * @param string      $size (default: 'full')
 * @param mixed       $default (default: null)
 * @param int|WP_Post $post (default: null)
 */
function the_restaurant_logo( $size = 'thumbnail', $default = null, $post = null ) {
	$logo = get_the_restaurant_logo( $post, $size );

	if ( has_post_thumbnail( $post ) ) {
		echo '<img class="restaurant_logo" src="' . esc_attr( $logo ) . '" alt="' . esc_attr( get_the_restaurant_name( $post ) ) . '" />';
	} elseif ( $default ) {
		echo '<img class="restaurant_logo" src="' . esc_attr( $default ) . '" alt="' . esc_attr( get_the_restaurant_name( $post ) ) . '" />';
	} else {
		echo '<img class="restaurant_logo" src="' . esc_attr( apply_filters( 'restaurant_listings_default_restaurant_logo', RESTAURANT_LISTING_PLUGIN_URL . '/assets/images/restaurant.png' ) ) . '" alt="' . esc_attr( get_the_restaurant_name( $post ) ) . '" />';
	}
}

/**
 * Gets the restaurant logo.
 *
 * @since 1.0.0
 * @param int|WP_Post $post (default: null)
 * @param string      $size
 * @return string Image SRC
 */
function get_the_restaurant_logo( $post = null, $size = 'thumbnail' ) {
	$post = get_post( $post );

	if ( has_post_thumbnail( $post->ID ) ) {
		$src = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), $size );
		return $src ? $src[0] : '';
	}

	return '';
}

/**
 * Resizes and returns the url of an image.
 *
 * @since 1.0.0
 * @param  string $logo
 * @param  string $size
 * @return string
 */
function restaurant_listings_get_resized_image( $logo, $size ) {
	global $_wp_additional_image_sizes;

	if ( $size !== 'full' && strstr( $logo, WP_CONTENT_URL ) && ( isset( $_wp_additional_image_sizes[ $size ] ) || in_array( $size, array( 'thumbnail', 'medium', 'large' ) ) ) ) {

		if ( in_array( $size, array( 'thumbnail', 'medium', 'large' ) ) ) {
			$img_width  = get_option( $size . '_size_w' );
			$img_height = get_option( $size . '_size_h' );
			$img_crop   = get_option( $size . '_size_crop' );
		} else {
			$img_width  = $_wp_additional_image_sizes[ $size ]['width'];
			$img_height = $_wp_additional_image_sizes[ $size ]['height'];
			$img_crop   = $_wp_additional_image_sizes[ $size ]['crop'];
		}

		$upload_dir        = wp_upload_dir();
		$logo_path         = str_replace( array( $upload_dir['baseurl'], $upload_dir['url'], WP_CONTENT_URL ), array( $upload_dir['basedir'], $upload_dir['path'], WP_CONTENT_DIR ), $logo );
		$path_parts        = pathinfo( $logo_path );
		$dims              = $img_width . 'x' . $img_height;
		$resized_logo_path = str_replace( '.' . $path_parts['extension'], '-' . $dims . '.' . $path_parts['extension'], $logo_path );

		if ( strstr( $resized_logo_path, 'http:' ) || strstr( $resized_logo_path, 'https:' ) ) {
			return $logo;
		}

		if ( ! file_exists( $resized_logo_path ) ) {
			ob_start();

			$image = wp_get_image_editor( $logo_path );

			if ( ! is_wp_error( $image ) ) {

				$resize = $image->resize( $img_width, $img_height, $img_crop );

			   	if ( ! is_wp_error( $resize ) ) {

			   		$save = $image->save( $resized_logo_path );

					if ( ! is_wp_error( $save ) ) {
						$logo = dirname( $logo ) . '/' . basename( $resized_logo_path );
					}
				}
			}

			ob_get_clean();
		} else {
			$logo = dirname( $logo ) . '/' . basename( $resized_logo_path );
		}
	}

	return $logo;
}

/**
 * Displays the restaurant video.
 *
 * @since 1.0.0
 * @param int|WP_Post $post
 */
function the_restaurant_video( $post = null ) {
	$video_embed = false;
	$video       = get_the_restaurant_video( $post );
	$filetype    = wp_check_filetype( $video );

	if( ! empty( $video ) ){
		// FV Wordpress Flowplayer Support for advanced video formats
		if ( shortcode_exists( 'flowplayer' ) ) {
			$video_embed = '[flowplayer src="' . esc_attr( $video ) . '"]';
		} elseif ( ! empty( $filetype[ 'ext' ] ) ) {
			$video_embed = wp_video_shortcode( array( 'src' => $video ) );
		} else {
			$video_embed = wp_oembed_get( $video );
		}
	}

	$video_embed = apply_filters( 'the_restaurant_video_embed', $video_embed, $post );

	if ( $video_embed ) {
		echo '<div class="restaurant_video">' . $video_embed . '</div>';
	}
}

/**
 * Gets the restaurant video URL.
 *
 * @since 1.0.0
 * @param int|WP_Post $post (default: null)
 * @return string|null
 */
function get_the_restaurant_video( $post = null ) {
	$post = get_post( $post );
	if ( $post->post_type !== 'restaurant_listings' ) {
		return;
	}
	return apply_filters( 'the_restaurant_video', $post->_restaurant_video, $post );
}

/**
 * Displays or retrieves the current restaurant name with optional content.
 *
 * @since 1.0.0
 * @param string           $before (default: '')
 * @param string           $after (default: '')
 * @param bool             $echo (default: true)
 * @param int|WP_Post|null $post (default: null)
 * @return string|void
 */
function the_restaurant_name( $before = '', $after = '', $echo = true, $post = null ) {
	$restaurant_name = get_the_restaurant_name( $post );

	if ( strlen( $restaurant_name ) == 0 )
		return;

	$restaurant_name = esc_attr( strip_tags( $restaurant_name ) );
	$restaurant_name = $before . $restaurant_name . $after;

	if ( $echo )
		echo $restaurant_name;
	else
		return $restaurant_name;
}

/**
 * Gets the restaurant name.
 *
 * @since 1.0.0
 * @param int $post (default: null)
 * @return string
 */
function get_the_restaurant_name( $post = null ) {
	$post = get_post( $post );
	if ( $post->post_type !== 'restaurant_listings' ) {
		return '';
	}

	return apply_filters( 'the_restaurant_name', $post->_restaurant_name, $post );
}

/**
 * Gets the restaurant website.
 *
 * @since 1.0.0
 * @param int $post (default: null)
 * @return null|string
 */
function get_the_restaurant_website( $post = null ) {
	$post = get_post( $post );

	if ( $post->post_type !== 'restaurant_listings' )
		return;

	$website = $post->_restaurant_website;

	if ( $website && ! strstr( $website, 'http:' ) && ! strstr( $website, 'https:' ) ) {
		$website = 'http://' . $website;
	}

	return apply_filters( 'the_restaurant_website', $website, $post );
}

/**
 * Displays or retrieves the current restaurant tagline with optional content.
 *
 * @since 1.0.0
 * @param string           $before (default: '')
 * @param string           $after (default: '')
 * @param bool             $echo (default: true)
 * @param int|WP_Post|null $post (default: null)
 * @return string|void
 */
function the_restaurant_tagline( $before = '', $after = '', $echo = true, $post = null ) {
	$restaurant_tagline = get_the_restaurant_tagline( $post );

	if ( strlen( $restaurant_tagline ) == 0 )
		return;

	$restaurant_tagline = esc_attr( strip_tags( $restaurant_tagline ) );
	$restaurant_tagline = $before . $restaurant_tagline . $after;

	if ( $echo )
		echo $restaurant_tagline;
	else
		return $restaurant_tagline;
}

/**
 * Gets the restaurant tagline.
 *
 * @since 1.0.0
 * @param int|WP_Post|null $post (default: null)
 * @return string|null
 */
function get_the_restaurant_tagline( $post = null ) {
	$post = get_post( $post );

	if ( $post->post_type !== 'restaurant_listings' )
		return;

	return apply_filters( 'the_restaurant_tagline', $post->_restaurant_tagline, $post );
}

/**
 * Displays or retrieves the current restaurant Twitter link with optional content.
 *
 * @since 1.0.0
 * @param string           $before (default: '')
 * @param string           $after (default: '')
 * @param bool             $echo (default: true)
 * @param int|WP_Post|null $post (default: null)
 * @return string|void
 */
function the_restaurant_twitter( $before = '', $after = '', $echo = true, $post = null ) {
	$restaurant_twitter = get_the_restaurant_twitter( $post );

	if ( strlen( $restaurant_twitter ) == 0 )
		return;

	$restaurant_twitter = esc_attr( strip_tags( $restaurant_twitter ) );
	$restaurant_twitter = $before . '<a href="http://twitter.com/' . $restaurant_twitter . '" class="restaurant_twitter" target="_blank">' . $restaurant_twitter . '</a>' . $after;

	if ( $echo )
		echo $restaurant_twitter;
	else
		return $restaurant_twitter;
}

/**
 * Gets the restaurant Twitter link.
 *
 * @since 1.0.0
 * @param int|WP_Post|null $post (default: null)
 * @return string|null
 */
function get_the_restaurant_twitter( $post = null ) {
	$post = get_post( $post );
	if ( $post->post_type !== 'restaurant_listings' )
		return;

	$restaurant_twitter = $post->_restaurant_twitter;

	if ( strlen( $restaurant_twitter ) == 0 )
		return;

	if ( strpos( $restaurant_twitter, '@' ) === 0 )
		$restaurant_twitter = substr( $restaurant_twitter, 1 );

	return apply_filters( 'the_restaurant_twitter', $restaurant_twitter, $post );
}

/**
 * Listings Phone Number
 *

 *
 * @return void
 */
function the_restaurant_phone() {
    global $post;

    $phone = $post->_restaurant_phone;

    if ( ! $phone ) {
        return;
    }
    ?>

    <div class="restaurant_listings-phone">
        <span itemprop="telephone"><a href="tel:<?php echo esc_attr( preg_replace( "/[^0-9,.]/", '', $phone ) ); ?>"><?php echo
                esc_attr( $phone ); ?></a></span>
    </div>

    <?php
}

/**
 * Listings Email
 *
 * @since 1.0.0
 *
 * @return void
 */
function the_restaurant_email() {
    $email = get_post()->_application;

    if ( ! $email || ! is_email( $email ) ) {
        return;
    }
    ?>

    <div class="listings-email">
        <a itemprop="email" href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo antispambot( $email ); ?></a>
    </div>

    <?php
}

/**
 * Listings URL
 *

 *
 * @return void
 */
function the_restaurant_url() {
    global $post;

    $url = get_the_restaurant_website( $post->ID );

    if ( ! $url ) {
        return;
    }

    $url = esc_url( $url );
    $base = parse_url( $url );
    $base = $base[ 'host' ];
    ?>

    <div class="restaurant_listings-url">
        <span itemprop="url"><a href="<?php echo $url; ?>" rel="nofollow" target="_blank"><?php echo esc_attr( $base ); ?></a></span>
    </div>

    <?php
}

/**
 * Listings Category
 *
 * @return void
 */
function the_restaurant_category() {
    global $post;

    if ( ! get_option( 'restaurant_listings_enable_categories' ) ) {
        return;
    }
    ?>

    <div class="content-single-restaurant_listings-title-category">
        <?php echo restaurant_listings_category_list( $post->ID, ', ', '<span class="posted_in"> ', '</span>' ); ?>
    </div>

    <?php
}

/**
 * @param null $post
 */
function the_restaurant_rating( $post = null ) {
    $post = get_post( $post );

    if ( $post->post_type !== 'restaurant_listings' )
        return;

    $rating_count = array_sum( WP_Restaurant_Listings_Comments::get_rating_counts_for_restaurant($post) );
    $review_count = WP_Restaurant_Listings_Comments::get_review_count_for_restaurant($post);
    $average      = WP_Restaurant_Listings_Comments::get_average_rating_for_restaurant($post);

    if ( $rating_count > 0 ) : ?>

        <div class="restaurant-rating">
            <div class="star-rating">
                <?php echo get_restaurant_star_rating_html( $average, $rating_count ); ?>
            </div>
            <?php if ( comments_open() ) : ?><a href="#reviews" class="restaurant-listings-review-link" rel="nofollow">(<?php printf( _n( '%s customer review', '%s customer reviews', $review_count, 'wp-restaurant-listings' ), '<span class="count">' . esc_html( $review_count ) . '</span>' ); ?>)</a><?php endif ?>
        </div>

    <?php endif;
}

function get_restaurant_star_rating_html( $rating, $count = 0 ) {
    $html = '<span style="width:' . ( ( $rating / 5 ) * 100 ) . '%">';

    if ( 0 < $count ) {
        /* translators: 1: rating 2: rating count */
        $html .= sprintf( _n( 'Rated %1$s out of 5 based on %2$s customer rating', 'Rated %1$s out of 5 based on %2$s customer ratings', $count, 'wp-restaurant-listings' ), '<strong class="rating">' . esc_html( $rating ) . '</strong>', '<span class="rating">' . esc_html( $count ) . '</span>' );
    } else {
        /* translators: %s: rating */
        $html .= sprintf( esc_html__( 'Rated %s out of 5', 'wp-restaurant-listings' ), '<strong class="rating">' . esc_html( $rating ) . '</strong>' );
    }

    $html .= '</span>';

    return apply_filters( 'get_restaurant_star_rating_html', $html, $rating, $count );
}

/**
 * Displays or retrieves the current restaurant opening hours
 *
 */
function the_restaurant_opening_hours() {
    global $post;

    if ( $post->post_type !== 'restaurant_listings' )
        return;

    $hours = get_post_meta( $post->ID, '_restaurant_hours', true );

    if ( ! $hours ) {
        return;
    }

    global $wp_locale;

    $numericdays = restaurant_listings_get_days_of_week();

    foreach ( $numericdays as $key => $i ) {
        $day = $wp_locale->get_weekday( $i );
        $start = isset( $hours[ $i ][ 'start' ] ) ? $hours[ $i ][ 'start' ] : false;
        $end = isset( $hours[ $i ][ 'end' ] ) ? $hours[ $i ][ 'end' ] : false;

        if ( ! ( $start && $end ) ) {
            continue;
        }

        $days[ $day ] = array( $start, $end );
    }

    if ( empty( $days ) ) {
        return;
    }
    ?>

    <div class="business-hours-drop-wrapper">
        <div class="business-hours-drop-element">
            <div class="business-hours-drop-content-inner">
        <?php foreach ( $days as $day => $hours ) : ?>
            <p class="business-hour" itemprop="openingHours" content="<?php echo $day; ?> <?php echo date_i18n( 'Ga', strtotime( $hours[0] ) ); ?>-<?php echo date( 'Ga', strtotime( $hours[1] ) ); ?>" data-day="<?php echo $day ?>">
                <span class="day"><?php echo $day ?></span>
                <span class="business-hour-time">
                <?php if ( __( 'Closed', 'wp-restaurant-listings' ) == $hours[0] ) : ?>
                    <?php _e( 'Closed', 'wp-restaurant-listings' ); ?>
                <?php else : ?>
                    <span class="start"><?php echo $hours[0]; ?></span> &ndash; <span class="end"><?php echo $hours[1]; ?></span>
                <?php endif; ?>
            </span>
            </p>
        <?php endforeach; ?>
            </div>
        </div>
        <p class="today business-hours">
            <span class="day"><?php _e('Today', 'wp-restaurant-listings' ) ?></span>
            <span class="business-hour-time">

            </span>
        </p>
        <a href="#" class="business-hours-drop-btn"><?php _e('See more', 'wp-restaurant-listings') ?></a>
    </div>
    <?php
}

/**
 * Get directions
 *
 * @return void
 */
function the_restaurant_directions() {
    // dont use formatted, but respect others if set.

    $destination = get_the_restaurant_location();

    if( !$destination ){
        return;
    }
    ?>

    <div class="restaurant_listings-directions">
        <a href="<?php echo esc_url( google_maps_url() ); ?>" rel="nofollow" target="_blank" class="js-toggle-directions" id="get-directions"><?php _e( 'Get Directions', 'wp-restaurant-listings' ); ?></a>
    </div>

    <?php
}

/**
 * Get the coordinates for a listings.
 *
 * @return string $coordinates
 */
function get_the_coordinates( $listing = false ) {
    $post = get_post();

    if ( $listing ) {
        $post = get_post( $listing );
    }

    return $post->geolocation_lat . ',' . $post->geolocation_long;
}

function google_maps_url() {
    global $post;

    $base = 'http://maps.google.com/maps';
    $args = array(
        'daddr' => urlencode( get_the_coordinates() )
    );

    return esc_url( add_query_arg( $args, $base ) );
}

/**
 * Outputs the restaurant listings class.
 *
 * @since 1.0.0
 * @param string      $class (default: '')
 * @param int|WP_Post $post_id (default: null)
 */
function restaurant_listings_class( $class = '', $post_id = null ) {
	// Separates classes with a single space, collates classes for post DIV.
	echo 'class="' . join( ' ', get_restaurant_listings_class( $class, $post_id ) ) . '"';
}

/**
 * Gets the restaurant listings class.
 *
 * @since 1.0.0
 * @param string      $class class.
 * @param int|WP_Post $post_id (default: null).
 * @return array
 */
function get_restaurant_listings_class( $class = '', $post_id = null ) {
	$post = get_post( $post_id );

	if ( empty( $post ) || 'restaurant_listings' !== $post->post_type ) {
		return array();
	}

	$classes = array();

	if ( ! empty( $class ) ) {
		if ( ! is_array( $class ) ) {
			$class = preg_split( '#\s+#', $class );
		}
		$classes = array_merge( $classes, $class );
	}

	return get_post_class( $classes, $post->ID );
}

/**
 * Adds post classes with meta info and the status of the restaurant listings.
 *
 * @since 1.0.0
 *
 * @param array $classes An array of post classes.
 * @param array $class   An array of additional classes added to the post.
 * @param int   $post_id The post ID.
 * @return array
 */
function wprl_add_post_class( $classes, $class, $post_id ) {
	$post = get_post( $post_id );

	if ( empty( $post ) || 'restaurant_listings' !== $post->post_type ) {
		return $classes;
	}

	$classes[] = 'restaurant_listings';

	if ( get_option( 'restaurant_listings_enable_types' ) ) {
		$restaurant_types = get_the_restaurant_types( $post );
		if ( ! empty( $restaurant_types ) ) {
			foreach ( $restaurant_types as $restaurant_type ) {
				$classes[] = 'restaurant-type-' . sanitize_title( $restaurant_type->name );
			}
		}
	}

	if ( is_restaurant_featured( $post ) ) {
		$classes[] = 'restaurant_status_featured';
	}

	return $classes;
}
add_action( 'post_class', 'wprl_add_post_class', 10, 3 );

/**
 * Displays restaurant meta data on the single restaurant page.
 *
 * @since 1.0.0
 */
function restaurant_listings_meta_display() {
	get_restaurant_listings_template( 'content-single-restaurant_listings-meta.php', array() );
}
add_action( 'single_restaurant_listings_start', 'restaurant_listings_meta_display', 20 );

/**
 * Displays restaurant restaurant data on the single restaurant page.
 *
 * @since 1.0.0
 */
function restaurant_listings_restaurant_display() {
	get_restaurant_listings_template( 'content-single-restaurant_listings-restaurant.php', array() );
}
add_action( 'single_restaurant_listings_start', 'restaurant_listings_restaurant_display', 30 );

add_action( 'single_restaurant_listings_end', 'restaurant_listings_output_data_tabs', 10 );

if ( ! function_exists( 'restaurant_listings_output_data_tabs' ) ) {
	/**
	 * Output the tabs.
	 *
	 * @subpackage Product/Tabs
	 */
	function restaurant_listings_output_data_tabs() {

		if ( 'publish' === get_post_status() ) {
			get_restaurant_listings_template( 'single-restaurant_listings/tabs/tabs.php' );
		}
	}
}

/**
 * Displays restaurant restaurant data on the single restaurant page.
 *
 * @since 1.0.0
 */
function restaurant_listings_photoswipe_template() {
	get_restaurant_listings_template( 'restaurant-gallery-photoswipe.php', array() );
}

add_action( 'single_restaurant_listings_end', 'restaurant_listings_photoswipe_template', 20 );

/**
 * Page tabs.
 */
add_filter( 'restaurant_listings_tabs', 'restaurant_listings_default_tabs' );

if ( ! function_exists( 'restaurant_listings_comments' ) ) {

	/**
	 * Output the Review comments template.
	 *
	 * @param WP_Comment $comment comment.
	 * @param array      $args args.
	 * @param int        $depth depth.
	 */
	function restaurant_listings_comments( $comment, $args, $depth ) {
		$GLOBALS['comment'] = $comment;
		get_restaurant_listings_template( 'single-restaurant_listings/review.php', array( 'comment' => $comment, 'args' => $args, 'depth' => $depth ) );
	}
}

/**
 * Reviews
 */
add_action( 'restaurant_listings_review_before', 'restaurant_listings_review_display_gravatar', 10 );
add_action( 'restaurant_listings_review_before_comment_meta', 'restaurant_listings_review_display_rating', 10 );
add_action( 'restaurant_listings_review_meta', 'restaurant_listings_review_display_meta', 10 );
add_action( 'restaurant_listings_review_comment_text', 'restaurant_listings_review_display_comment_text', 10 );

if ( ! function_exists( 'restaurant_listings_review_display_gravatar' ) ) {
	/**
	 * Display the review authors gravatar
	 *
	 * @param WP_Comment $comment comment.
	 * @return void
	 */
	function restaurant_listings_review_display_gravatar( $comment ) {
		echo get_avatar( $comment, apply_filters( 'restaurant_listings_review_gravatar_size', '60' ), '' );
	}
}

if ( ! function_exists( 'restaurant_listings_review_display_rating' ) ) {
	/**
	 * Display the reviewers star rating
	 *
	 * @return void
	 */
	function restaurant_listings_review_display_rating() {
		if ( post_type_supports( 'restaurant_listings', 'comments' ) ) {
			get_restaurant_listings_template( 'single-restaurant_listings/review-rating.php' );
		}
	}
}

if ( ! function_exists( 'restaurant_listings_review_display_meta' ) ) {
	/**
	 * Display the review authors meta (name, verified owner, review date)
	 *
	 * @return void
	 */
	function restaurant_listings_review_display_meta() {
		get_restaurant_listings_template( 'single-restaurant_listings/review-meta.php' );
	}
}

if ( ! function_exists( 'restaurant_listings_overview_tab' ) ) {

	/**
	 * Output the overview tab content.
	 *
	 * @subpackage Product/Tabs
	 */
	function restaurant_listings_overview_tab() {
		get_restaurant_listings_template( 'single-restaurant_listings/tabs/overview.php' );
	}
}

if ( ! function_exists( 'restaurant_listings_menu_tab' ) ) {

	/**
	 * Output the attributes tab content.
	 *
	 * @subpackage Product/Tabs
	 */
	function restaurant_listings_menu_tab() {
		get_restaurant_listings_template( 'single-restaurant_listings/tabs/menu.php' );
	}
}

if ( ! function_exists( 'restaurant_listings_review_display_comment_text' ) ) {

	/**
	 * Display the review content.
	 */
	function restaurant_listings_review_display_comment_text() {
		echo '<div class="description">';
		comment_text();
		echo '</div>';
	}
}

/**
 * Get HTML for ratings.
 *
 * @param  float $rating Rating being shown.
 * @param  int   $count  Total number of ratings.
 * @return string
 */
function restaurant_listings_get_rating_html( $rating, $count = 0 ) {

	if ( 0 < $rating ) {
		$html  = '<div class="star-rating">';
		$html .= restaurant_listings_get_star_rating_html( $rating, $count );
		$html .= '</div>';
	} else {
		$html = '';
	}

	return apply_filters( 'restaurant_listings_get_rating_html', $html, $rating, $count );
}

/**
 * Get HTML for star rating.
 *
 * @param  float $rating Rating being shown.
 * @param  int   $count  Total number of ratings.
 * @return string
 */
function restaurant_listings_get_star_rating_html( $rating, $count = 0 ) {
	$html = '<span style="width:' . ( ( $rating / 5 ) * 100 ) . '%">';

	if ( 0 < $count ) {
		/* translators: 1: rating 2: rating count */
		$html .= sprintf( _n( 'Rated %1$s out of 5 based on %2$s customer rating', 'Rated %1$s out of 5 based on %2$s customer ratings', $count, 'wp-restaurant-listings' ), '<strong class="rating">' . esc_html( $rating ) . '</strong>', '<span class="rating">' . esc_html( $count ) . '</span>' );
	} else {
		/* translators: %s: rating */
		$html .= sprintf( esc_html__( 'Rated %s out of 5', 'wp-restaurant-listings' ), '<strong class="rating">' . esc_html( $rating ) . '</strong>' );
	}

	$html .= '</span>';

	return apply_filters( 'restaurant_listings_get_star_rating_html', $html, $rating, $count );
}
