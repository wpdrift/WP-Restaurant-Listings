<?php
/**
 * Handles displays and hooks for the Restaurant Listings custom post type.
 *
 * @package RestaurantListings
 * @since 1.0.0
 */
class WP_Restaurant_Listings_Post_Types {

	/**
	 * The single instance of the class.
	 *
	 * @var self
	 * @since 1.0.0
	 */
	private static $_instance = null;

	/**
	 * Allows for accessing single instance of class. Class should only be constructed once per call.
	 *
	 * @since 1.0.0
	 * @static
	 * @return self Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_post_types' ), 0 );
		add_filter( 'admin_head', array( $this, 'admin_head' ) );
		add_action( 'restaurant_listings_delete_old_previews', array( $this, 'delete_old_previews' ) );

		add_action( 'pending_to_publish', array( $this, 'set_expiry' ) );
		add_action( 'preview_to_publish', array( $this, 'set_expiry' ) );
		add_action( 'draft_to_publish', array( $this, 'set_expiry' ) );
		add_action( 'auto-draft_to_publish', array( $this, 'set_expiry' ) );

		add_filter( 'the_restaurant_description', 'wptexturize'        );
		add_filter( 'the_restaurant_description', 'convert_smilies'    );
		add_filter( 'the_restaurant_description', 'convert_chars'      );
		add_filter( 'the_restaurant_description', 'wpautop'            );
		add_filter( 'the_restaurant_description', 'shortcode_unautop'  );
		add_filter( 'the_restaurant_description', 'prepend_attachment' );
		if ( ! empty( $GLOBALS['wp_embed'] ) ) {
			add_filter( 'the_restaurant_description', array( $GLOBALS['wp_embed'], 'run_shortcode' ), 8 );
			add_filter( 'the_restaurant_description', array( $GLOBALS['wp_embed'], 'autoembed' ), 8 );
		}

		add_filter( 'wp_insert_post_data', array( $this, 'fix_post_name' ), 10, 2 );
		add_action( 'add_post_meta', array( $this, 'maybe_add_geolocation_data' ), 10, 3 );
		add_action( 'update_post_meta', array( $this, 'update_post_meta' ), 10, 4 );
		add_action( 'wp_insert_post', array( $this, 'maybe_add_default_meta_data' ), 10, 2 );

		add_action( 'parse_query', array( $this, 'add_feed_query_args' ) );

		// WP ALL Import
		add_action( 'pmxi_saved_post', array( $this, 'pmxi_saved_post' ), 10, 1 );

		// RP4WP
		add_filter( 'rp4wp_get_template', array( $this, 'rp4wp_template' ), 10, 3 );
		add_filter( 'rp4wp_related_meta_fields', array( $this, 'rp4wp_related_meta_fields' ), 10, 3 );
		add_filter( 'rp4wp_related_meta_fields_weight', array( $this, 'rp4wp_related_meta_fields_weight' ), 10, 3 );

		// Single restaurant content
		$this->restaurant_content_filter( true );
	}

	/**
	 * Registers the custom post type and taxonomies.
	 */
	public function register_post_types() {
		if ( post_type_exists( "restaurant_listings" ) )
			return;

		$admin_capability = 'manage_restaurant_listings';

		$permalink_structure = WP_Restaurant_Listings_Post_Types::get_permalink_structure();

		/**
		 * Taxonomies
		 */
		if ( get_option( 'restaurant_listings_enable_categories' ) ) {
			$singular  = __( 'Restaurant category', 'wp-restaurant-listings' );
			$plural    = __( 'Restaurant categories', 'wp-restaurant-listings' );

			if ( current_theme_supports( 'restaurant-listings-templates' ) ) {
				$rewrite   = array(
					'slug'         => $permalink_structure['category_rewrite_slug'],
					'with_front'   => false,
					'hierarchical' => false
				);
				$public    = true;
			} else {
				$rewrite   = false;
				$public    = false;
			}

			register_taxonomy( "restaurant_listings_category",
				apply_filters( 'register_taxonomy_restaurant_listings_category_object_type', array( 'restaurant_listings' ) ),
				apply_filters( 'register_taxonomy_restaurant_listings_category_args', array(
					'hierarchical' 			=> true,
					'update_count_callback' => '_update_post_term_count',
					'label' 				=> $plural,
					'labels' => array(
						'name'              => $plural,
						'singular_name'     => $singular,
						'menu_name'         => ucwords( $plural ),
						'search_items'      => sprintf( __( 'Search %s', 'wp-restaurant-listings' ), $plural ),
						'all_items'         => sprintf( __( 'All %s', 'wp-restaurant-listings' ), $plural ),
						'parent_item'       => sprintf( __( 'Parent %s', 'wp-restaurant-listings' ), $singular ),
						'parent_item_colon' => sprintf( __( 'Parent %s:', 'wp-restaurant-listings' ), $singular ),
						'edit_item'         => sprintf( __( 'Edit %s', 'wp-restaurant-listings' ), $singular ),
						'update_item'       => sprintf( __( 'Update %s', 'wp-restaurant-listings' ), $singular ),
						'add_new_item'      => sprintf( __( 'Add New %s', 'wp-restaurant-listings' ), $singular ),
						'new_item_name'     => sprintf( __( 'New %s Name', 'wp-restaurant-listings' ),  $singular )
					),
					'show_ui' 				=> true,
					'show_tagcloud'			=> false,
					'public' 	     		=> $public,
					'capabilities'			=> array(
						'manage_terms' 		=> $admin_capability,
						'edit_terms' 		=> $admin_capability,
						'delete_terms' 		=> $admin_capability,
						'assign_terms' 		=> $admin_capability,
					),
					'rewrite' 				=> $rewrite,
				) )
			);
		}

		if ( get_option( 'restaurant_listings_enable_types' ) ) {
			$singular  = __( 'Restaurant type', 'wp-restaurant-listings' );
			$plural    = __( 'Restaurant types', 'wp-restaurant-listings' );

			if ( current_theme_supports( 'restaurant-listings-templates' ) ) {
				$rewrite   = array(
					'slug'         => $permalink_structure['type_rewrite_slug'],
					'with_front'   => false,
					'hierarchical' => false
				);
				$public    = true;
			} else {
				$rewrite   = false;
				$public    = false;
			}

			register_taxonomy( "restaurant_listings_type",
				apply_filters( 'register_taxonomy_restaurant_listings_type_object_type', array( 'restaurant_listings' ) ),
				apply_filters( 'register_taxonomy_restaurant_listings_type_args', array(
					'hierarchical' 			=> true,
					'label' 				=> $plural,
					'labels' => array(
						'name' 				=> $plural,
						'singular_name' 	=> $singular,
						'menu_name'         => ucwords( $plural ),
						'search_items' 		=> sprintf( __( 'Search %s', 'wp-restaurant-listings' ), $plural ),
						'all_items' 		=> sprintf( __( 'All %s', 'wp-restaurant-listings' ), $plural ),
						'parent_item' 		=> sprintf( __( 'Parent %s', 'wp-restaurant-listings' ), $singular ),
						'parent_item_colon' => sprintf( __( 'Parent %s:', 'wp-restaurant-listings' ), $singular ),
						'edit_item' 		=> sprintf( __( 'Edit %s', 'wp-restaurant-listings' ), $singular ),
						'update_item' 		=> sprintf( __( 'Update %s', 'wp-restaurant-listings' ), $singular ),
						'add_new_item' 		=> sprintf( __( 'Add New %s', 'wp-restaurant-listings' ), $singular ),
						'new_item_name' 	=> sprintf( __( 'New %s Name', 'wp-restaurant-listings' ),  $singular )
					),
					'show_ui' 				=> true,
					'show_tagcloud'			=> false,
					'public' 			    => $public,
					'capabilities'			=> array(
						'manage_terms' 		=> $admin_capability,
						'edit_terms' 		=> $admin_capability,
						'delete_terms' 		=> $admin_capability,
						'assign_terms' 		=> $admin_capability,
					),
					'rewrite' 				=> $rewrite,
				) )
			);
		}

		/**
		 * Post types
		 */
		$singular  = __( 'Restaurant', 'wp-restaurant-listings' );
		$plural    = __( 'Restaurants', 'wp-restaurant-listings' );

		if ( current_theme_supports( 'restaurant-listings-templates' ) ) {
			$has_archive = _x( 'restaurants', 'Post type archive slug - resave permalinks after changing this', 'wp-restaurant-listings' );
		} else {
			$has_archive = false;
		}

		$rewrite     = array(
			'slug'       => $permalink_structure['restaurant_rewrite_slug'],
			'with_front' => false,
			'feeds'      => true,
			'pages'      => false
		);

	    register_post_type( "restaurant_listings",
			apply_filters( "register_post_type_restaurant_listings", array(
				'labels' => array(
					'name'			=> $plural,
					'singular_name' 	=> $singular,
					'menu_name'             => $plural,
					'all_items'             => sprintf( __( 'All %s', 'wp-restaurant-listings' ), $plural ),
					'add_new' 		=> __( 'Add New', 'wp-restaurant-listings' ),
					'add_new_item' 		=> sprintf( __( 'Add %s', 'wp-restaurant-listings' ), $singular ),
					'edit' 			=> __( 'Edit', 'wp-restaurant-listings' ),
					'edit_item' 		=> sprintf( __( 'Edit %s', 'wp-restaurant-listings' ), $singular ),
					'new_item' 		=> sprintf( __( 'New %s', 'wp-restaurant-listings' ), $singular ),
					'view' 			=> sprintf( __( 'View %s', 'wp-restaurant-listings' ), $singular ),
					'view_item' 		=> sprintf( __( 'View %s', 'wp-restaurant-listings' ), $singular ),
					'search_items' 		=> sprintf( __( 'Search %s', 'wp-restaurant-listings' ), $plural ),
					'not_found' 		=> sprintf( __( 'No %s found', 'wp-restaurant-listings' ), $plural ),
					'not_found_in_trash' 	=> sprintf( __( 'No %s found in trash', 'wp-restaurant-listings' ), $plural ),
					'parent' 		=> sprintf( __( 'Parent %s', 'wp-restaurant-listings' ), $singular ),
					'featured_image'        => __( 'Restaurant Logo', 'wp-restaurant-listings' ),
					'set_featured_image'    => __( 'Set restaurant logo', 'wp-restaurant-listings' ),
					'remove_featured_image' => __( 'Remove restaurant logo', 'wp-restaurant-listings' ),
					'use_featured_image'    => __( 'Use as restaurant logo', 'wp-restaurant-listings' ),
				),
				'description' => sprintf( __( 'This is where you can create and manage %s.', 'wp-restaurant-listings' ), $plural ),
				'public' 				=> true,
				'show_ui' 				=> true,
				'capability_type' 		=> 'restaurant_listing',
				'map_meta_cap'          => true,
				'publicly_queryable' 	=> true,
				'exclude_from_search' 	=> false,
				'hierarchical' 			=> false,
				'rewrite' 				=> $rewrite,
				'query_var' 			=> true,
				'supports' 				=> array( 'title', 'editor', 'custom-fields', 'publicize', 'thumbnail', 'comments' ),
				'has_archive' 			=> $has_archive,
				'show_in_nav_menus' 	=> true
			) )
		);

		/**
		 * Feeds
		 */
		add_feed( 'restaurant_feed', array( $this, 'restaurant_feed' ) );

		/**
		 * Post status
		 */
		register_post_status( 'preview', array(
			'label'                     => _x( 'Preview', 'post status', 'wp-restaurant-listings' ),
			'public'                    => false,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => false,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Preview <span class="count">(%s)</span>', 'Preview <span class="count">(%s)</span>', 'wp-restaurant-listings' ),
		) );
	}

	/**
	 * Change label for admin menu item to show number of Restaurant Listings items pending approval.
	 */
	public function admin_head() {
		global $menu;

		$pending_restaurants = WP_Restaurant_Listings_Cache_Helper::get_listings_count();

		// No need to go further if no pending restaurants, menu is not set, or is not an array
		if( empty( $pending_restaurants ) || empty( $menu ) || ! is_array( $menu ) ){
			return;
		}

		// Try to pull menu_name from post type object to support themes/plugins that change the menu string
		$post_type = get_post_type_object( 'restaurant_listings' );
		$plural = isset( $post_type->labels, $post_type->labels->menu_name ) ? $post_type->labels->menu_name : __( 'Restaurant Listings', 'wp-restaurant-listings' );

		foreach ( $menu as $key => $menu_item ) {
			if ( strpos( $menu_item[0], $plural ) === 0 ) {
				$menu[ $key ][0] .= " <span class='awaiting-mod update-plugins count-{$pending_restaurants}'><span class='pending-count'>" . number_format_i18n( $pending_restaurants ) . "</span></span>" ;
				break;
			}
		}
	}

	/**
	 * Toggles content filter on and off.
	 *
	 * @param bool $enable
	 */
	private function restaurant_content_filter( $enable ) {
		if ( ! $enable ) {
			remove_filter( 'the_content', array( $this, 'restaurant_content' ) );
		} else {
			add_filter( 'the_content', array( $this, 'restaurant_content' ) );
		}
	}

	/**
	 * Adds extra content before/after the post for single restaurant listings.
	 *
	 * @param string $content
	 * @return string
	 */
	public function restaurant_content( $content ) {
		global $post;

		if ( ! is_singular( 'restaurant_listings' ) || ! in_the_loop() || 'restaurant_listings' !== $post->post_type ) {
			return $content;
		}

		ob_start();

		$this->restaurant_content_filter( false );

		do_action( 'restaurant_content_start' );

		get_restaurant_listings_template_part( 'content-single', 'restaurant_listings' );

		do_action( 'restaurant_content_end' );

		$this->restaurant_content_filter( true );

		return apply_filters( 'restaurant_listings_single_restaurant_content', ob_get_clean(), $post );
	}

	/**
	 * Generates the RSS feed for Restaurant Listings.
	 */
	public function restaurant_feed() {
		$query_args = array(
			'post_type'           => 'restaurant_listings',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => 1,
			'posts_per_page'      => isset( $_GET['posts_per_page'] ) ? absint( $_GET['posts_per_page'] ) : 10,
			'tax_query'           => array(),
			'meta_query'          => array()
		);

		if ( ! empty( $_GET['search_location'] ) ) {
			$location_meta_keys = array( 'geolocation_formatted_address', '_restaurant_location', 'geolocation_state_long' );
			$location_search    = array( 'relation' => 'OR' );
			foreach ( $location_meta_keys as $meta_key ) {
				$location_search[] = array(
					'key'     => $meta_key,
					'value'   => sanitize_text_field( $_GET['search_location'] ),
					'compare' => 'like'
				);
			}
			$query_args['meta_query'][] = $location_search;
		}

		if ( ! empty( $_GET['restaurant_types'] ) ) {
			$query_args['tax_query'][] = array(
				'taxonomy' => 'restaurant_listings_type',
				'field'    => 'slug',
				'terms'    => explode( ',', sanitize_text_field( $_GET['restaurant_types'] ) ) + array( 0 )
			);
		}

		if ( ! empty( $_GET['restaurant_categories'] ) ) {
			$cats     = explode( ',', sanitize_text_field( $_GET['restaurant_categories'] ) ) + array( 0 );
			$field    = is_numeric( $cats ) ? 'term_id' : 'slug';
			$operator = 'all' === get_option( 'restaurant_listings_category_filter_type', 'all' ) && sizeof( $args['search_categories'] ) > 1 ? 'AND' : 'IN';
			$query_args['tax_query'][] = array(
				'taxonomy'         => 'restaurant_listings_category',
				'field'            => $field,
				'terms'            => $cats,
				'include_children' => $operator !== 'AND' ,
				'operator'         => $operator
			);
		}

		$restaurant_listings_keyword = isset( $_GET['search_keywords'] ) ? sanitize_text_field( $_GET['search_keywords'] ) : '';
		if ( !empty( $restaurant_listings_keyword ) ) {
			$query_args['s'] = $restaurant_listings_keyword;
			add_filter( 'posts_search', 'get_restaurant_listings_keyword_search' );
		}

		if ( empty( $query_args['meta_query'] ) ) {
			unset( $query_args['meta_query'] );
		}

		if ( empty( $query_args['tax_query'] ) ) {
			unset( $query_args['tax_query'] );
		}

		query_posts( apply_filters( 'restaurant_feed_args', $query_args ) );
		add_action( 'rss2_ns', array( $this, 'restaurant_feed_namespace' ) );
		add_action( 'rss2_item', array( $this, 'restaurant_feed_item' ) );
		do_feed_rss2( false );
		remove_filter( 'posts_search', 'get_restaurant_listings_keyword_search' );
	}

	/**
	 * Adds query arguments in order to make sure that the feed properly queries the 'restaurant_listings' type.
	 *
	 * @param WP_Query $wp
	 */
	public function add_feed_query_args( $wp ) {

		// Let's leave if not the restaurant feed
		if ( ! isset( $wp->query_vars['feed'] ) || 'restaurant_feed' !== $wp->query_vars['feed'] ) {
			return;
		}

		// Leave if not a feed.
		if ( false === $wp->is_feed ) {
			return;
		}

		// If the post_type was already set, let's get out of here.
		if ( isset( $wp->query_vars['post_type'] ) && ! empty( $wp->query_vars['post_type'] ) ) {
			return;
		}

		$wp->query_vars['post_type'] = 'restaurant_listings';
	}

	/**
	 * Adds a custom namespace to the restaurant feed.
	 */
	public function restaurant_feed_namespace() {
		echo 'xmlns:restaurant_listings="' .  site_url() . '"' . "\n";
	}

	/**
	 * Adds custom data to the restaurant feed.
	 */
	public function restaurant_feed_item() {
		$post_id         = get_the_ID();
		$location        = get_the_restaurant_location( $post_id );
		$company         = get_the_restaurant_name( $post_id );
		$restaurant_types       = get_the_restaurant_types( $post_id );

		if ( $location ) {
			echo "<restaurant_listings:location><![CDATA[" . esc_html( $location ) . "]]></restaurant_listings:location>\n";
		}
		if ( ! empty( $restaurant_types ) ) {
			$restaurant_types_names = implode( ', ', wp_list_pluck( $restaurant_types, 'name' ) );
			echo "<restaurant_listings:restaurant_type><![CDATA[" . esc_html( $restaurant_types_names ) . "]]></restaurant_listings:restaurant_type>\n";
		}
		if ( $company ) {
			echo "<restaurant_listings:restaurant><![CDATA[" . esc_html( $company ) . "]]></restaurant_listings:restaurant>\n";
		}

		/**
		 * Fires at the end of each restaurant RSS feed item.
		 *
		 * @param int $post_id The post ID of the restaurant.
		 */
		 do_action( 'restaurant_feed_item', $post_id );
	}

	/**
	 * Deletes old previewed restaurants after 30 days to keep the DB clean.
	 */
	public function delete_old_previews() {
		global $wpdb;

		// Delete old expired restaurants
		$restaurant_ids = $wpdb->get_col( $wpdb->prepare( "
			SELECT posts.ID FROM {$wpdb->posts} as posts
			WHERE posts.post_type = 'restaurant_listings'
			AND posts.post_modified < %s
			AND posts.post_status = 'preview'
		", date( 'Y-m-d', strtotime( '-30 days', current_time( 'timestamp' ) ) ) ) );

		if ( $restaurant_ids ) {
			foreach ( $restaurant_ids as $restaurant_id ) {
				wp_delete_post( $restaurant_id, true );
			}
		}
	}

	/**
	 * Typo wrapper for `set_expiry` method.
	 *
	 * @param WP_Post $post
	 * @deprecated
	 */
	public function set_expirey( $post ) {
		$this->set_expiry( $post );
	}

	/**
	 * Sets expiry date when restaurant status changes.
	 *
	 * @param WP_Post $post
	 */
	public function set_expiry( $post ) {
		if ( $post->post_type !== 'restaurant_listings' ) {
			return;
		}

	}

	/**
	 * Fixes post name when wp_update_post changes it.
	 *
	 * @param array $data
	 * @param array $postarr
	 * @return array
	 */
	public function fix_post_name( $data, $postarr ) {
		 if ( 'restaurant_listings' === $data['post_type'] && 'pending' === $data['post_status'] && ! current_user_can( 'publish_posts' ) && isset( $postarr['post_name'] ) ) {
				$data['post_name'] = $postarr['post_name'];
		 }
		 return $data;
	}

	/**
	 * Retrieves permalink settings.
     *
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_permalink_structure() {
		// Switch to the site's default locale, bypassing the active user's locale.
		if ( function_exists( 'switch_to_locale' ) && did_action( 'admin_init' ) ) {
			switch_to_locale( get_locale() );
		}

		$permalinks = wp_parse_args( (array) get_option( 'wprl_permalinks', array() ), array(
			'restaurant_base'           => '',
			'category_base'          => '',
			'type_base'               => '',
		) );

		// Ensure rewrite slugs are set.
		$permalinks['restaurant_rewrite_slug']      = untrailingslashit( empty( $permalinks['restaurant_base'] ) ? _x( 'restaurant', 'Restaurant permalink - resave permalinks after changing this', 'wp-restaurant-listings' )                   : $permalinks['restaurant_base'] );
		$permalinks['category_rewrite_slug'] = untrailingslashit( empty( $permalinks['category_base'] ) ? _x( 'restaurant-category', 'Restaurant category slug - resave permalinks after changing this', 'wp-restaurant-listings' ) : $permalinks['category_base'] );
		$permalinks['type_rewrite_slug']     = untrailingslashit( empty( $permalinks['type_base'] ) ? _x( 'restaurant-type', 'Restaurant type slug - resave permalinks after changing this', 'wp-restaurant-listings' )             : $permalinks['type_base'] );

		// Restore the original locale.
		if ( function_exists( 'restore_current_locale' ) && did_action( 'admin_init' ) ) {
			restore_current_locale();
		}
		return $permalinks;
	}

	/**
	 * Generates location data if a post is added.
	 *
	 * @param int    $object_id
	 * @param string $meta_key
	 * @param mixed  $meta_value
	 */
	public function maybe_add_geolocation_data( $object_id, $meta_key, $meta_value ) {
		if ( '_restaurant_location' !== $meta_key || 'restaurant_listings' !== get_post_type( $object_id ) ) {
			return;
		}
		do_action( 'restaurant_listings_restaurant_location_edited', $object_id, $meta_value );
	}

	/**
	 * Triggered when updating meta on a restaurant listings.
	 *
	 * @param int    $meta_id
	 * @param int    $object_id
	 * @param string $meta_key
	 * @param mixed  $meta_value
	 */
	public function update_post_meta( $meta_id, $object_id, $meta_key, $meta_value ) {
		if ( 'restaurant_listings' === get_post_type( $object_id ) ) {
			switch ( $meta_key ) {
				case '_restaurant_location' :
					$this->maybe_update_geolocation_data( $meta_id, $object_id, $meta_key, $meta_value );
				break;
				case '_featured' :
					$this->maybe_update_menu_order( $meta_id, $object_id, $meta_key, $meta_value );
				break;
			}
		}
	}

	/**
	 * Generates location data if a post is updated.
	 *
	 * @param int    $meta_id (Unused)
	 * @param int    $object_id
	 * @param string $meta_key (Unused)
	 * @param mixed  $meta_value
	 */
	public function maybe_update_geolocation_data( $meta_id, $object_id, $meta_key, $meta_value ) {
		do_action( 'restaurant_listings_restaurant_location_edited', $object_id, $meta_value );
	}

	/**
	 * Maybe sets menu_order if the featured status of a restaurant is changed.
	 *
	 * @param int    $meta_id (Unused)
	 * @param int    $object_id
	 * @param string $meta_key (Unused)
	 * @param mixed  $meta_value
	 */
	public function maybe_update_menu_order( $meta_id, $object_id, $meta_key, $meta_value ) {
		global $wpdb;

		if ( '1' == $meta_value ) {
			$wpdb->update( $wpdb->posts, array( 'menu_order' => -1 ), array( 'ID' => $object_id ) );
		} else {
			$wpdb->update( $wpdb->posts, array( 'menu_order' => 0 ), array( 'ID' => $object_id, 'menu_order' => -1 ) );
		}

		clean_post_cache( $object_id );
	}

	/**
	 * Legacy.
	 *
	 * @param int    $meta_id
	 * @param int    $object_id
	 * @param string $meta_key
	 * @param mixed  $meta_value
	 * @deprecated 1.19.1
	 */
	public function maybe_generate_geolocation_data( $meta_id, $object_id, $meta_key, $meta_value ) {
		$this->maybe_update_geolocation_data( $meta_id, $object_id, $meta_key, $meta_value );
	}

	/**
	 * Maybe sets default meta data for restaurant listings.
	 *
	 * @param  int            $post_id
	 * @param  WP_Post|string $post
	 */
	public function maybe_add_default_meta_data( $post_id, $post = '' ) {
		if ( empty( $post ) || 'restaurant_listings' === $post->post_type ) {
			add_post_meta( $post_id, '_featured', 0, true );
		}
	}

	/**
	 * After importing via WP All Import, adds default meta data.
	 *
	 * @param  int $post_id
	 */
	public function pmxi_saved_post( $post_id ) {
		if ( 'restaurant_listings' === get_post_type( $post_id ) ) {
			$this->maybe_add_default_meta_data( $post_id );
			if ( ! WP_Restaurant_Listings_Geocode::has_location_data( $post_id ) && ( $location = get_post_meta( $post_id, '_restaurant_location', true ) ) ) {
				WP_Restaurant_Listings_Geocode::generate_location_data( $post_id, $location );
			}
		}
	}

	/**
	 * Replaces RP4WP template with the template from Restaurant Listings.
	 *
	 * @param  string $located
	 * @param  string $template_name
	 * @param  array  $args
	 * @return string
	 */
	public function rp4wp_template( $located, $template_name, $args ) {
		if ( 'related-post-default.php' === $template_name && 'restaurant_listings' === $args['related_post']->post_type ) {
			return RESTAURANT_LISTING_PLUGIN_DIR . '/templates/content-restaurant_listings.php';
		}
		return $located;
	}

	/**
	 * Adds meta fields for RP4WP to relate restaurants by.
	 *
	 * @param  array   $meta_fields
	 * @param  int     $post_id
	 * @param  WP_Post $post
	 * @return array
	 */
	public function rp4wp_related_meta_fields( $meta_fields, $post_id, $post ) {
		if ( 'restaurant_listings' === $post->post_type ) {
			$meta_fields[] = '_restaurant_name';
			$meta_fields[] = '_restaurant_location';
		}
		return $meta_fields;
	}

	/**
	 * Adds meta fields for RP4WP to relate restaurants by.
	 *
	 * @param  int     $weight
	 * @param  WP_Post $post
	 * @param  string  $meta_field
	 * @return int
	 */
	public function rp4wp_related_meta_fields_weight( $weight, $post, $meta_field ) {
		if ( 'restaurant_listings' === $post->post_type ) {
			$weight = 100;
		}
		return $weight;
	}
}
