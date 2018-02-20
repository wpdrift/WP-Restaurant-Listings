<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Handles Restaurant Listings's Ajax endpoints.
 *
 * @package RestaurantListings
 * @since 1.0.0
 */
class WP_Restaurant_Listings_Ajax {

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
		add_action( 'init', array( __CLASS__, 'add_endpoint' ) );
		add_action( 'template_redirect', array( __CLASS__, 'do_rl_ajax' ), 0 );

		// JM Ajax endpoints
		add_action( 'restaurant_listings_ajax_get_listings', array( $this, 'get_listings' ) );
		add_action( 'restaurant_listings_ajax_upload_file', array( $this, 'upload_file' ) );

		// BW compatible handlers
		add_action( 'wp_ajax_nopriv_restaurant_listings_get_listings', array( $this, 'get_listings' ) );
		add_action( 'wp_ajax_restaurant_listings_get_listings', array( $this, 'get_listings' ) );
		add_action( 'wp_ajax_nopriv_restaurant_listings_upload_file', array( $this, 'upload_file' ) );
		add_action( 'wp_ajax_restaurant_listings_upload_file', array( $this, 'upload_file' ) );
		add_action( 'wp_ajax_restaurant_listings_locate_restaurant', array( $this, 'locate_restaurant') );
		add_action( 'wp_ajax_nopriv_restaurant_listings_locate_restaurant', array( $this, 'locate_restaurant') );
	}

	/**
	 * Adds endpoint for frontend Ajax requests.
	 */
	public static function add_endpoint() {
		add_rewrite_tag( '%rl-ajax%', '([^/]*)' );
		add_rewrite_rule( 'rl-ajax/([^/]*)/?', 'index.php?rl-ajax=$matches[1]', 'top' );
		add_rewrite_rule( 'index.php/rl-ajax/([^/]*)/?', 'index.php?rl-ajax=$matches[1]', 'top' );
	}

	/**
	 * Gets Restaurant Listings's Ajax Endpoint.
	 *
	 * @param  string $request      Optional
	 * @param  string $ssl (Unused) Optional
	 * @return string
	 */
	public static function get_endpoint( $request = '%%endpoint%%', $ssl = null ) {
		if ( strstr( get_option( 'permalink_structure' ), '/index.php/' ) ) {
			$endpoint = trailingslashit( home_url( '/index.php/rl-ajax/' . $request . '/', 'relative' ) );
		} elseif ( get_option( 'permalink_structure' ) ) {
			$endpoint = trailingslashit( home_url( '/rl-ajax/' . $request . '/', 'relative' ) );
		} else {
			$endpoint = add_query_arg( 'rl-ajax', $request, trailingslashit( home_url( '', 'relative' ) ) );
		}
		return esc_url_raw( $endpoint );
	}

	/**
	 * Performs Restaurant Listings's Ajax actions.
	 */
	public static function do_rl_ajax() {
		global $wp_query;

		if ( ! empty( $_GET['rl-ajax'] ) ) {
			 $wp_query->set( 'rl-ajax', sanitize_text_field( $_GET['rl-ajax'] ) );
		}

		if ( $action = $wp_query->get( 'rl-ajax' ) ) {
			if ( ! defined( 'DOING_AJAX' ) ) {
				define( 'DOING_AJAX', true );
			}

			// Not home - this is an ajax endpoint
			$wp_query->is_home = false;

			/**
			 * Performs an Ajax action.
			 * The dynamic part of the action, $action, is the predefined Ajax action to be performed.
			 *
			 * @since 1.0.0
			 */
			do_action( 'restaurant_listings_ajax_' . sanitize_text_field( $action ) );
			wp_die();
		}
	}

	/**
	 * Returns Restaurant Listings for Ajax endpoint.
	 */
	public function get_listings() {
		global $wp_post_types;

		$result             = array();
		$search_location    = sanitize_text_field( stripslashes( $_REQUEST['search_location'] ) );
		$search_keywords    = sanitize_text_field( stripslashes( $_REQUEST['search_keywords'] ) );
		$search_categories  = isset( $_REQUEST['search_categories'] ) ? $_REQUEST['search_categories'] : '';
		$search_price_range = isset( $_REQUEST['search_price_range'] ) ? $_REQUEST['search_price_range'] : '';
		$filter_restaurant_types   = isset( $_REQUEST['filter_restaurant_type'] ) ? array_filter( array_map( 'sanitize_title', (array) $_REQUEST['filter_restaurant_type'] ) ) : null;
		$filter_post_status = isset( $_REQUEST['filter_post_status'] ) ? array_filter( array_map( 'sanitize_title', (array) $_REQUEST['filter_post_status'] ) ) : null;
		$types              = get_restaurant_listings_types();
		$post_type_label    = $wp_post_types['restaurant_listings']->labels->name;
		$orderby            = sanitize_text_field( $_REQUEST['orderby'] );

		if ( is_array( $search_categories ) ) {
			$search_categories = array_filter( array_map( 'sanitize_text_field', array_map( 'stripslashes', $search_categories ) ) );
		} else {
			$search_categories = array_filter( array( sanitize_text_field( stripslashes( $search_categories ) ) ) );
		}

		$args = array(
			'search_location'    => $search_location,
			'search_keywords'    => $search_keywords,
			'search_categories'  => $search_categories,
			'search_price_range' => $search_price_range,
			'restaurant_types'   => is_null( $filter_restaurant_types ) || sizeof( $types ) === sizeof( $filter_restaurant_types ) ? '' : $filter_restaurant_types + array( 0 ),
			'post_status'        => $filter_post_status,
			'orderby'            => $orderby,
			'order'              => sanitize_text_field( $_REQUEST['order'] ),
			'offset'             => ( absint( $_REQUEST['page'] ) - 1 ) * absint( $_REQUEST['per_page'] ),
			'posts_per_page'     => absint( $_REQUEST['per_page'] ),
		);

		if ( isset( $_REQUEST['featured'] ) && ( $_REQUEST['featured'] === 'true' || $_REQUEST['featured'] === 'false' ) ) {
			$args['featured'] = $_REQUEST['featured'] === 'true' ? true : false;
			$args['orderby']  = 'featured' === $orderby ? 'date' : $orderby;
		}

		/**
		 * Get the arguments to use when building the Restaurant Listings WP Query.
		 *
		 * @since 1.0.0
		 *
		 * @param array $args Arguments used for generating Restaurant Listings query (see `get_restaurant_listings()`)
		 */
		$restaurants = get_restaurant_listings( apply_filters( 'restaurant_listings_get_listings_args', $args ) );

		$result = array(
			'found_restaurants' => $restaurants->have_posts(),
			'showing' => '',
			'max_num_pages' => $restaurants->max_num_pages,
		);

		if ( $restaurants->post_count && ( $search_location || $search_keywords || $search_categories || $search_price_range ) ) {
			$message = sprintf( _n( 'Search completed. Found %d matching record.', 'Search completed. Found %d matching records.', $restaurants->found_posts, 'wp-restaurant-listings' ), $restaurants->found_posts );
			$result['showing_all'] = true;
		} else {
			$message = '';
		}

		$search_values = array(
			'location'   => $search_location,
			'keywords'   => $search_keywords,
			'categories' => $search_categories,
		);

		/**
		 * Filter the message that describes the results of the search query.
		 *
		 * @since 1.0.0
		 *
		 * @param string $message Default message that is generated when posts are found.
		 * @param array $search_values {
		 *  Helpful values often used in the generation of this message.
		 *
		 *  @type string $location   Query used to filter by restaurant listings location.
		 *  @type string $keywords   Query used to filter by general keywords.
		 *  @type array  $categories List of the categories to filter by.
		 * }
		 */
		$result['showing'] = apply_filters( 'restaurant_listings_get_listings_custom_filter_text', $message, $search_values );

		// Generate RSS link
		$result['showing_links'] = restaurant_listings_get_filtered_links( array(
			'filter_restaurant_types'  => $filter_restaurant_types,
			'search_location'   => $search_location,
			'search_categories' => $search_categories,
			'search_keywords'   => $search_keywords,
			'search_price_range'=> $search_price_range
		) );

		/**
		 * Send back a response to the AJAX request without creating HTML.
		 *
		 * @since 1.0.0
		 *
		 * @param array $result
		 * @param WP_Query $restaurants
		 * @return bool True by default. Change to false to halt further response.
		 */
		if ( true !== apply_filters( 'restaurant_listings_ajax_get_restaurants_html_results', true, $result, $restaurants ) ) {
			/**
			 * Filters the results of the restaurant listings Ajax query to be sent back to the client.
			 *
			 * @since 1.0.0
			 *
			 * @param array $result {
			 *  Package of the query results along with meta information.
			 *
			 *  @type bool   $found_restaurants    Whether or not restaurants were found in the query.
			 *  @type string $showing       Description of the search query and results.
			 *  @type int    $max_num_pages Number of pages in the search result.
			 *  @type string $html          HTML representation of the search results (only if filter
			 *                              `restaurant_listings_ajax_get_restaurants_html_results` returns true).
			 *  @type array $pagination     Pagination links to use for stepping through filter results.
			 * }
			 */
			return wp_send_json( apply_filters( 'restaurant_listings_get_listings_result', $result, $restaurants ) );
		}

		ob_start();

		if ( $result['found_restaurants'] ) : ?>

			<?php while ( $restaurants->have_posts() ) : $restaurants->the_post(); ?>

				<?php get_restaurant_listings_template_part( 'content', 'restaurant_listings' ); ?>

			<?php endwhile; ?>

		<?php else : ?>

			<?php get_restaurant_listings_template_part( 'content', 'no-restaurants-found' ); ?>

		<?php endif;

		$result['html'] = ob_get_clean();

		// Generate pagination
		if ( isset( $_REQUEST['show_pagination'] ) && $_REQUEST['show_pagination'] === 'true' ) {
			$result['pagination'] = get_restaurant_listings_pagination( $restaurants->max_num_pages, absint( $_REQUEST['page'] ) );
		}

		/** This filter is documented in includes/class-wp-restaurant-listings-ajax.php (above) */
		wp_send_json( apply_filters( 'restaurant_listings_get_listings_result', $result, $restaurants ) );
	}

	/**
	 * Uploads file from an Ajax request.
	 *
	 * No nonce field since the form may be statically cached.
	 */
	public function upload_file() {
		if ( ! restaurant_listings_user_can_upload_file_via_ajax() ) {
			wp_send_json_error( __( 'You must be logged in to upload files using this method.', 'wp-restaurant-listings' ) );
			return;
		}
		$data = array(
			'files' => array(),
		);

		if ( ! empty( $_FILES ) ) {
			foreach ( $_FILES as $file_key => $file ) {
				$files_to_upload = restaurant_listings_prepare_uploaded_files( $file );
				foreach ( $files_to_upload as $file_to_upload ) {
					$uploaded_file = restaurant_listings_upload_file( $file_to_upload, array(
						'file_key' => $file_key,
					) );

					if ( is_wp_error( $uploaded_file ) ) {
						$data['files'][] = array(
							'error' => $uploaded_file->get_error_message(),
						);
					} else {
						$data['files'][] = $uploaded_file;
					}
				}
			}
		}

		wp_send_json( $data );
	}

    /**
     * Restaurant locator
     */
    public function locate_restaurant() {
		global $wpdb;

        $restaurnat_lat  	= $_GET['origLat'];
        $restaurant_lng  	= $_GET['origLng'];
        $radius 			= get_option('restaurant_listings_search_radius'); // search radius in miles

        // Source: https://stackoverflow.com/questions/29553895/querying-mysql-for-latitude-and-longitude-coordinates-that-are-within-a-given-mi
        // Spherical Law of Cosines Formula
        $query_posts = "SELECT l.post_id, l.lat, l.lng, p.post_title, ( 3959 * acos( cos( radians({$restaurnat_lat}) ) * cos( radians( l.lat ) )
                    	* cos( radians( l.lng ) - radians({$restaurant_lng}) ) + sin( radians({$restaurnat_lat}) ) * sin(radians(l.lat)) ) ) as distance
                    	FROM {$wpdb->prefix}restaurants_location l INNER JOIN {$wpdb->posts} p ON p.ID = l.post_id HAVING distance < {$radius}
                    	ORDER BY distance";

        $restaurants = $wpdb->get_results( $query_posts );

        if ( ! sizeof( $restaurants ) )  wp_send_json(array());

        $restaurant_data = array();

        foreach ( $restaurants as $rest_data ) {
			$restaurant_data[] = array(
				'geometry' => array(
					'type' 			=> 'Point',
					'coordinates' 	=> array((float)$rest_data->lng,(float)$rest_data->lat)
				),
				'properties' => array(
					'name' => $rest_data->post_title,
                    'address' => get_post_meta( $rest_data->post_id, '_restaurant_location', true )
				),

			);
        }

        wp_send_json( $restaurant_data );
	}
}

WP_Restaurant_Listings_Ajax::instance();
