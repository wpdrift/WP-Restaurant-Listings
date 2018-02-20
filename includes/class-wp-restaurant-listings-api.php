<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Handles API requests for WP Restaurant Listings.
 *
 * @package RestaurantListings
 * @since 1.0.0
 */
class WP_Restaurant_Listings_API {

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
		add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0 );
		add_action( 'parse_request', array( $this, 'api_requests' ), 0 );
	}

	/**
	 * Adds query vars used in API calls.
	 *
	 * @param array $vars the query vars
	 * @return array
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'restaurant-listings-api';
		return $vars;
	}

	/**
	 * Adds endpoint for API requests.
	 */
	public function add_endpoint() {
		add_rewrite_endpoint( 'restaurant-listings-api', EP_ALL );
	}

	/**
	 * API request - Trigger any API requests (handy for third party plugins/gateways).
	 */
	public function api_requests() {
		global $wp;

		if ( ! empty( $_GET['restaurant-listings-api'] ) ) {
			$wp->query_vars['restaurant-listings-api'] = $_GET['restaurant-listings-api'];
		}

		if ( ! empty( $wp->query_vars['restaurant-listings-api'] ) ) {
			// Buffer, we won't want any output here
			ob_start();

			// Get API trigger
			$api = strtolower( esc_attr( $wp->query_vars['restaurant-listings-api'] ) );

			// Load class if exists
			if ( has_action( 'restaurant_listings_api_' . $api ) && class_exists( $api ) ) {
				$api_class = new $api();
			}

			/**
			 * Performs an API action.
			 * The dynamic part of the action, $api, is the API action.
			 *
			 * @since 1.0.0
			 */
			do_action( 'restaurant_listings_api_' . $api );

			// Done, clear buffer and exit
			ob_end_clean();
			wp_die();
		}
	}
}

WP_Restaurant_Listings_API::instance();
