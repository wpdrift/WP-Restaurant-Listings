<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Handles the shortcodes for WP Restaurant Listings.
 *
 * @package RestaurantListings
 * @since 1.0.0
 */
class WP_Restaurant_Listings_Shortcodes {

	/**
	 * Dashboard message.
	 *
	 * @access private
	 * @var string
	 */
	private $restaurant_dashboard_message = '';

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
		add_action( 'wp', array( $this, 'shortcode_action_handler' ) );
		add_action( 'restaurant_listings_restaurant_dashboard_content_edit', array( $this, 'edit_restaurant' ) );
		add_action( 'restaurant_listings_restaurant_filters_end', array( $this, 'restaurant_filter_restaurant_types' ), 20 );
		add_action( 'restaurant_listings_restaurant_filters_end', array( $this, 'restaurant_filter_results' ), 30 );
		add_action( 'restaurant_listings_output_restaurants_no_results', array( $this, 'output_no_results' ) );
		add_shortcode( 'submit_restaurant_form', array( $this, 'submit_restaurant_form' ) );
		add_shortcode( 'restaurant_dashboard', array( $this, 'restaurant_dashboard' ) );
		add_shortcode( 'restaurants_locator', array( $this, 'restaurants_locator' ) );
		add_shortcode( 'restaurants', array( $this, 'output_restaurants' ) );
		add_shortcode( 'restaurant', array( $this, 'output_restaurant' ) );
		add_shortcode( 'restaurant_summary', array( $this, 'output_restaurant_summary' ) );

	}

	/**
	 * Handles actions which need to be run before the shortcode e.g. post actions.
	 */
	public function shortcode_action_handler() {
		global $post;

		if ( is_page() && has_shortcode($post->post_content, 'restaurant_dashboard' ) ) {
			$this->restaurant_dashboard_handler();
		}
	}

	/**
	 * Shows the restaurant submission form.
	 *
	 * @param array $atts
	 * @return string|null
	 */
	public function submit_restaurant_form( $atts = array() ) {
		return $GLOBALS['restaurant_listings']->forms->get_form( 'submit-restaurant', $atts );
	}

	/**
	 * Handles actions on restaurant dashboard.
	 */
	public function restaurant_dashboard_handler() {
		if ( ! empty( $_REQUEST['action'] ) && ! empty( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'restaurant_listings_my_restaurant_actions' ) ) {

			$action = sanitize_title( $_REQUEST['action'] );
			$restaurant_id = absint( $_REQUEST['restaurant_id'] );

			try {
				// Get Restaurant
				$restaurant    = get_post( $restaurant_id );

				// Check ownership
				if ( ! restaurant_listings_user_can_edit_restaurant( $restaurant_id ) ) {
					throw new Exception( __( 'Invalid ID', 'wp-restaurant-listings' ) );
				}

				switch ( $action ) {
					case 'delete' :
						// Trash it
						wp_trash_post( $restaurant_id );

						// Message
						$this->restaurant_dashboard_message = '<div class="restaurant-listings-message">' . sprintf( __( '%s has been deleted', 'wp-restaurant-listings' ), get_the_restaurant_title( $restaurant ) ) . '</div>';

						break;
					case 'duplicate' :
						if ( ! restaurant_listings_get_permalink( 'submit_restaurant_form' ) ) {
							throw new Exception( __( 'Missing submission page.', 'wp-restaurant-listings' ) );
						}

						$new_restaurant_id = restaurant_listings_duplicate_listing( $restaurant_id );

						if ( $new_restaurant_id ) {
							wp_redirect( add_query_arg( array( 'restaurant_id' => absint( $new_restaurant_id ) ), restaurant_listings_get_permalink( 'submit_restaurant_form' ) ) );
							exit;
						}

						break;
					case 'relist' :
						if ( ! restaurant_listings_get_permalink( 'submit_restaurant_form' ) ) {
							throw new Exception( __( 'Missing submission page.', 'wp-restaurant-listings' ) );
						}

						// redirect to post page
						wp_redirect( add_query_arg( array( 'restaurant_id' => absint( $restaurant_id ) ), restaurant_listings_get_permalink( 'submit_restaurant_form' ) ) );
						exit;

						break;
					default :
						do_action( 'restaurant_listings_restaurant_dashboard_do_action_' . $action );
						break;
				}

				do_action( 'restaurant_listings_my_restaurant_do_action', $action, $restaurant_id );

			} catch ( Exception $e ) {
				$this->restaurant_dashboard_message = '<div class="restaurant-listings-error">' . $e->getMessage() . '</div>';
			}
		}
	}

    /**
     * [restaurant_locator]
     * @return mixed
     */
    public function restaurants_locator() {

        wp_enqueue_style('mapbox-gl' );
        wp_enqueue_script( 'mapbox-gl' );
        wp_enqueue_style('mapbox-gl-geocoder');
        wp_enqueue_script( 'mapbox-gl-geocoder');
        ?>

        <div id="restaurant-locator-wrap">
            <div class='restaurant-locator-sidebar'>
                <div class='heading'>
                    <h1>Our locations</h1>
                </div>
                <div id='listings' class='listings'></div>
            </div>
            <div id='restaurant-locator-map' class='restaurant-locator-map  '></div>
        </div>

        <?php
	}

	/**
	 * Handles shortcode which lists the logged in user's restaurants.
	 *
	 * @param array $atts
	 * @return strings
	 */
	public function restaurant_dashboard( $atts ) {
		if ( ! is_user_logged_in() ) {
			ob_start();
			get_restaurant_listings_template( 'restaurant-dashboard-login.php' );
			return ob_get_clean();
		}

		extract( shortcode_atts( array(
			'posts_per_page' => '25',
		), $atts ) );

		wp_enqueue_script( 'wp-restaurant-listings-restaurant-dashboard' );

		ob_start();

		// If doing an action, show conditional content if needed....
		if ( ! empty( $_REQUEST['action'] ) ) {
			$action = sanitize_title( $_REQUEST['action'] );

			// Show alternative content if a plugin wants to
			if ( has_action( 'restaurant_listings_restaurant_dashboard_content_' . $action ) ) {
				do_action( 'restaurant_listings_restaurant_dashboard_content_' . $action, $atts );

				return ob_get_clean();
			}
		}

		// ....If not show the restaurant dashboard
		$args     = apply_filters( 'restaurant_listings_get_dashboard_restaurants_args', array(
			'post_type'           => 'restaurant_listings',
			'post_status'         => array( 'publish', 'pending' ),
			'ignore_sticky_posts' => 1,
			'posts_per_page'      => $posts_per_page,
			'offset'              => ( max( 1, get_query_var('paged') ) - 1 ) * $posts_per_page,
			'orderby'             => 'date',
			'order'               => 'desc',
			'author'              => get_current_user_id()
		) );

		$restaurants = new WP_Query;

		echo $this->restaurant_dashboard_message;

		$restaurant_dashboard_columns = apply_filters( 'restaurant_listings_restaurant_dashboard_columns', array(
			'restaurant_title' => __( 'Title', 'wp-restaurant-listings' ),
			'date'      => __( 'Date Posted', 'wp-restaurant-listings' ),
		) );

		get_restaurant_listings_template( 'restaurant-dashboard.php', array( 'restaurants' => $restaurants->query( $args ), 'max_num_pages' => $restaurants->max_num_pages, 'restaurant_dashboard_columns' => $restaurant_dashboard_columns ) );

		return ob_get_clean();
	}

	/**
	 * Displays edit restaurant form.
	 */
	public function edit_restaurant() {
		global $restaurant_listings;

		echo $restaurant_listings->forms->get_form( 'edit-restaurant' );
	}

	/**
	 * Lists all restaurant listings.
	 *
	 * @param array $atts
	 * @return string
	 */
	public function output_restaurants( $atts ) {
		ob_start();

		extract( $atts = shortcode_atts( apply_filters( 'restaurant_listings_output_restaurants_defaults', array(
			'per_page'                  => get_option( 'restaurant_listings_per_page' ),
			'orderby'                   => 'featured',
			'order'                     => 'DESC',

			// Filters + cats
			'show_filters'              => true,
			'show_categories'           => true,
			'show_category_multiselect' => get_option( 'restaurant_listings_enable_default_category_multiselect', false ),
			'show_pagination'           => false,
			'show_more'                 => true,

			// Limit what restaurants are shown based on category, post status, and type
			'categories'                => '',
			'restaurant_types'                 => '',
			'post_status'               => '',
			'featured'                  => null, // True to show only featured, false to hide featured, leave null to show both.

			// Default values for filters
			'location'                  => '',
			'keywords'                  => '',
			'selected_category'         => '',
			'selected_restaurant_types'        => implode( ',', array_values( get_restaurant_listings_types( 'id=>slug' ) ) ),
		) ), $atts ) );

		if ( ! get_option( 'restaurant_listings_enable_categories' ) ) {
			$show_categories = false;
		}

		$currency = get_option('restaurant_listings_currency');

		// String and bool handling
		$show_filters              = $this->string_to_bool( $show_filters );
		$show_categories           = $this->string_to_bool( $show_categories );
		$show_category_multiselect = $this->string_to_bool( $show_category_multiselect );
		$show_more                 = $this->string_to_bool( $show_more );
		$show_pagination           = $this->string_to_bool( $show_pagination );

		if ( ! is_null( $featured ) ) {
			$featured = ( is_bool( $featured ) && $featured ) || in_array( $featured, array( '1', 'true', 'yes' ) ) ? true : false;
		}

		// Array handling
		$categories         = is_array( $categories ) ? $categories : array_filter( array_map( 'trim', explode( ',', $categories ) ) );
		$restaurant_types          = is_array( $restaurant_types ) ? $restaurant_types : array_filter( array_map( 'trim', explode( ',', $restaurant_types ) ) );
		$post_status        = is_array( $post_status ) ? $post_status : array_filter( array_map( 'trim', explode( ',', $post_status ) ) );
		$selected_restaurant_types = is_array( $selected_restaurant_types ) ? $selected_restaurant_types : array_filter( array_map( 'trim', explode( ',', $selected_restaurant_types ) ) );

		// Get keywords and location from querystring if set
		if ( ! empty( $_GET['search_keywords'] ) ) {
			$keywords = sanitize_text_field( $_GET['search_keywords'] );
		}
		if ( ! empty( $_GET['search_location'] ) ) {
			$location = sanitize_text_field( $_GET['search_location'] );
		}
		if ( ! empty( $_GET['search_category'] ) ) {
			$selected_category = sanitize_text_field( $_GET['search_category'] );
		}

		$data_attributes        = array(
			'location'        => $location,
			'keywords'        => $keywords,
			'show_filters'    => $show_filters ? 'true' : 'false',
			'show_pagination' => $show_pagination ? 'true' : 'false',
			'per_page'        => $per_page,
			'orderby'         => $orderby,
			'order'           => $order,
			'categories'      => implode( ',', $categories ),
		);
		if ( $show_filters ) {

			get_restaurant_listings_template( 'restaurant-filters.php', array( 'per_page' => $per_page, 'orderby' => $orderby, 'order' => $order, 'show_categories' => $show_categories, 'categories' => $categories, 'selected_category' => $selected_category, 'restaurant_types' => $restaurant_types, 'atts' => $atts, 'location' => $location, 'keywords' => $keywords, 'selected_restaurant_types' => $selected_restaurant_types, 'show_category_multiselect' => $show_category_multiselect, 'currency' => $currency ) );

			get_restaurant_listings_template( 'restaurant-listings-start.php' );
			get_restaurant_listings_template( 'restaurant-listings-end.php' );

			if ( ! $show_pagination && $show_more ) {
				echo '<a class="load_more_restaurants" href="#" style="display:none;"><strong>' . __( 'Load more listings', 'wp-restaurant-listings' ) . '</strong></a>';
			}

		} else {
			$restaurants = get_restaurant_listings( apply_filters( 'restaurant_listings_output_restaurants_args', array(
				'search_location'   => $location,
				'search_keywords'   => $keywords,
				'post_status'       => $post_status,
				'search_categories' => $categories,
				'restaurant_types'  => $restaurant_types,
				'orderby'           => $orderby,
				'order'             => $order,
				'posts_per_page'    => $per_page,
				'featured'          => $featured,
			) ) );

			if ( ! empty( $restaurant_types ) ) {
				$data_attributes[ 'restaurant_types' ] = implode( ',', $restaurant_types );
			}

			if ( $restaurants->have_posts() ) : ?>

				<?php get_restaurant_listings_template( 'restaurant-listings-start.php' ); ?>

				<?php while ( $restaurants->have_posts() ) : $restaurants->the_post(); ?>
					<?php get_restaurant_listings_template_part( 'content', 'restaurant_listings' ); ?>
				<?php endwhile; ?>

				<?php get_restaurant_listings_template( 'restaurant-listings-end.php' ); ?>

				<?php if ( $restaurants->found_posts > $per_page && $show_more ) : ?>

					<?php wp_enqueue_script( 'wp-restaurant-listings-ajax-filters' ); ?>

					<?php if ( $show_pagination ) : ?>
						<?php echo get_restaurant_listings_pagination( $restaurants->max_num_pages ); ?>
					<?php else : ?>
						<a class="load_more_restaurants" href="#"><strong><?php _e( 'Load more listings', 'wp-restaurant-listings' ); ?></strong></a>
					<?php endif; ?>

				<?php endif; ?>

			<?php else :
				do_action( 'restaurant_listings_output_restaurants_no_results' );
			endif;

			wp_reset_postdata();
		}

		$data_attributes_string = '';
		if ( ! is_null( $featured ) ) {
			$data_attributes[ 'featured' ]    = $featured ? 'true' : 'false';
		}
		if ( ! empty( $post_status ) ) {
			$data_attributes[ 'post_status' ] = implode( ',', $post_status );
		}
		foreach ( $data_attributes as $key => $value ) {
			$data_attributes_string .= 'data-' . esc_attr( $key ) . '="' . esc_attr( $value ) . '" ';
		}

		$restaurant_listings_output = apply_filters( 'restaurant_listings_restaurant_listings_output', ob_get_clean() );

		return '<div class="restaurant_listings" ' . $data_attributes_string . '>' . $restaurant_listings_output . '</div>';
	}

	/**
	 * Displays some content when no results were found.
	 */
	public function output_no_results() {
		get_restaurant_listings_template( 'content-no-restaurants-found.php' );
	}

	/**
	 * Gets string as a bool.
	 *
	 * @param  string $value
	 * @return bool
	 */
	public function string_to_bool( $value ) {
		return ( is_bool( $value ) && $value ) || in_array( $value, array( '1', 'true', 'yes' ) ) ? true : false;
	}

	/**
	 * Shows restaurant types.
	 *
	 * @param  array $atts
	 */
	public function restaurant_filter_restaurant_types( $atts ) {
		extract( $atts );

		$restaurant_types          = array_filter( array_map( 'trim', explode( ',', $restaurant_types ) ) );
		$selected_restaurant_types = array_filter( array_map( 'trim', explode( ',', $selected_restaurant_types ) ) );

		get_restaurant_listings_template( 'restaurant-filter-restaurant-types.php', array( 'restaurant_types' => $restaurant_types, 'atts' => $atts, 'selected_restaurant_types' => $selected_restaurant_types ) );
	}

	/**
	 * Shows results div.
	 */
	public function restaurant_filter_results() {
		echo '<div class="showing_restaurants"></div>';
	}

	/**
	 * Shows a single restaurant.
	 *
	 * @param array $atts
	 * @return string|null
	 */
	public function output_restaurant( $atts ) {
		extract( shortcode_atts( array(
			'id' => '',
		), $atts ) );

		if ( ! $id ) {
			return;
		}

		ob_start();

		$args = array(
			'post_type'   => 'restaurant_listings',
			'post_status' => 'publish',
			'p'           => $id
		);

		$restaurants = new WP_Query( $args );

		if ( $restaurants->have_posts() ) : ?>

			<?php while ( $restaurants->have_posts() ) : $restaurants->the_post(); ?>

				<h1><?php the_restaurant_title(); ?></h1>

				<?php get_restaurant_listings_template_part( 'content-single', 'restaurant_listings' ); ?>

			<?php endwhile; ?>

		<?php endif;

		wp_reset_postdata();

		return '<div class="restaurant_shortcode single_restaurant_listing">' . ob_get_clean() . '</div>';
	}

	/**
	 * Handles the Restaurant Summary shortcode.
	 *
	 * @param array $atts
	 * @return string
	 */
	public function output_restaurant_summary( $atts ) {
		extract( shortcode_atts( array(
			'id'       => '',
			'width'    => '250px',
			'align'    => 'left',
			'featured' => null, // True to show only featured, false to hide featured, leave null to show both (when leaving out id)
			'limit'    => 1
		), $atts ) );

		ob_start();

		$args = array(
			'post_type'   => 'restaurant_listings',
			'post_status' => 'publish'
		);

		if ( ! $id ) {
			$args['posts_per_page'] = $limit;
			$args['orderby']        = 'rand';
			if ( ! is_null( $featured ) ) {
				$args['meta_query'] = array( array(
					'key'     => '_featured',
					'value'   => '1',
					'compare' => $featured ? '=' : '!='
				) );
			}
		} else {
			$args['p'] = absint( $id );
		}

		$restaurants = new WP_Query( $args );

		if ( $restaurants->have_posts() ) : ?>

			<?php while ( $restaurants->have_posts() ) : $restaurants->the_post(); ?>

				<div class="restaurant_summary_shortcode align<?php echo $align ?>" style="width: <?php echo $width ? $width : auto; ?>">

					<?php get_restaurant_listings_template_part( 'content-summary', 'restaurant_listings' ); ?>

				</div>

			<?php endwhile; ?>

		<?php endif;

		wp_reset_postdata();

		return ob_get_clean();
	}
}

WP_Restaurant_Listings_Shortcodes::instance();
