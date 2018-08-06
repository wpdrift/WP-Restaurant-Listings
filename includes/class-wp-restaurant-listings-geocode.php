<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Obtains Geolocation data for posted restaurants from Google.
 *
 * @package RestaurantListings
 * @since 1.0.0
 */
class WP_Restaurant_Listings_Geocode {

	const MAPS_GEOCODE_API_URL = 'https://api.mapbox.com/geocoding/v5/mapbox.places/';

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
		add_filter( 'restaurant_listings_geolocation_endpoint', array( $this, 'add_geolocation_endpoint_query_args' ), 0, 2 );
		add_filter( 'restaurant_listings_geolocation_api_key', array( $this, 'get_google_maps_api_key' ), 0 );
		add_action( 'restaurant_listings_update_restaurant_data', array( $this, 'update_location_data' ), 20, 2 );
		add_action( 'restaurant_listings_restaurant_location_edited', array( $this, 'change_location_data' ), 20, 2 );
	}

	/**
	 * Updates location data when submitting a restaurant.
	 *
	 * @param int   $restaurant_id
	 * @param array $values
	 */
	public function update_location_data( $restaurant_id, $values ) {
		if ( apply_filters( 'restaurant_listings_geolocation_enabled', true ) && isset( $values['restaurant']['restaurant_location'] ) ) {
			$address_data = self::get_location_data( $values['restaurant']['restaurant_location'] );
			self::save_location_data( $restaurant_id, $address_data );
		}
	}

	/**
	 * Changes a restaurants location data upon editing.
	 *
	 * @param  int    $restaurant_id
	 * @param  string $new_location
	 */
	public function change_location_data( $restaurant_id, $new_location ) {
		if ( apply_filters( 'restaurant_listings_geolocation_enabled', true ) ) {
			$address_data = self::get_location_data( $new_location );
			self::clear_location_data( $restaurant_id );
			self::save_location_data( $restaurant_id, $address_data );
		}
	}

	/**
	 * Checks if a restaurant has location data or not.
	 *
	 * @param  int $restaurant_id
	 * @return boolean
	 */
	public static function has_location_data( $restaurant_id ) {
		return get_post_meta( $restaurant_id, 'geolocated', true ) == 1;
	}

	/**
	 * Generates location data and saves to a post.
	 *
	 * @param  int    $restaurant_id
	 * @param  string $location
	 */
	public static function generate_location_data( $restaurant_id, $location ) {
		$address_data = self::get_location_data( $location );
		self::save_location_data( $restaurant_id, $address_data );
	}

	/**
	 * Deletes a restaurant's location data.
	 *
	 * @param  int $restaurant_id
	 */
	public static function clear_location_data( $restaurant_id ) {
		delete_post_meta( $restaurant_id, 'geolocated' );
		delete_post_meta( $restaurant_id, 'geolocation_city' );
		delete_post_meta( $restaurant_id, 'geolocation_country_long' );
		delete_post_meta( $restaurant_id, 'geolocation_country_short' );
		delete_post_meta( $restaurant_id, 'geolocation_formatted_address' );
		delete_post_meta( $restaurant_id, 'geolocation_lat' );
		delete_post_meta( $restaurant_id, 'geolocation_long' );
		delete_post_meta( $restaurant_id, 'geolocation_state_long' );
		delete_post_meta( $restaurant_id, 'geolocation_state_short' );
		delete_post_meta( $restaurant_id, 'geolocation_street' );
		delete_post_meta( $restaurant_id, 'geolocation_street_number' );
		delete_post_meta( $restaurant_id, 'geolocation_zipcode' );
		delete_post_meta( $restaurant_id, 'geolocation_postcode' );
	}

	/**
	 * Saves any returned data to post meta.
	 *
	 * @param  int   $restaurant_id
	 * @param  array $address_data
	 */
	public static function save_location_data( $restaurant_id, $address_data ) {
	    global $wpdb;

		if ( ! is_wp_error( $address_data ) && $address_data ) {
			foreach ( $address_data as $key => $value ) {
				if ( $value ) {
					update_post_meta( $restaurant_id, 'geolocation_' . $key, $value );
				}
			}
			update_post_meta( $restaurant_id, 'geolocated', 1 );

            // Store restaurant location latitude and longitude
            $sql = "INSERT INTO {$wpdb->prefix}restaurants_location (post_id, lat, lng) VALUES (%d,  %f, %f) ON DUPLICATE KEY UPDATE lat = %f, lng = %f";
            $sql = $wpdb->prepare( $sql, $restaurant_id, $address_data['lat'], $address_data['long'], $address_data['lat'], $address_data['long'] );
            $wpdb->query($sql);
		}

	}

	/**
	 * Retrieves the Google Maps API key from the plugin's settings.
	 *
	 * @param  string $key
	 * @return string
	 */
	public function get_google_maps_api_key( $key ) {
		return get_option('restaurant_listings_mapbox_access_token');
	}

	/**
	 * Adds the necessary query arguments for a Google Maps Geocode API request.
	 *
	 * @param  string $geocode_endpoint_url
	 * @param  string $raw_address
	 * @return string|bool
	 */
	public function add_geolocation_endpoint_query_args( $geocode_endpoint_url, $raw_address ) {
		// Add an API key if available.

        // Add address in endpoint url
        $geocode_endpoint_url .= "{$raw_address}.json/";

		$api_key = apply_filters( 'restaurant_listings_geolocation_api_key', '', $raw_address );

		if ( '' !== $api_key ) {
			$geocode_endpoint_url = add_query_arg( 'access_token', urlencode( $api_key ), $geocode_endpoint_url );
		}

		$locale = get_locale();
		if ( $locale ) {
			$geocode_endpoint_url = add_query_arg( 'language',  substr( $locale, 0, 2 ), $geocode_endpoint_url );
		}

        $geocode_endpoint_url = add_query_arg( 'limit', '1', $geocode_endpoint_url );

		return $geocode_endpoint_url;
	}

	/**
	 * Gets Location Data from Google.
	 *
	 * Based on code by Eyal Fitoussi.
	 *
	 * @param string $raw_address
	 * @return array|bool location data
	 */
	public static function get_location_data( $raw_address ) {
		$invalid_chars = array( "," => " ", "?" => " ", "&" => " ", "=" => " " , "#" => " " );
		$raw_address   = trim( strtolower( str_replace( array_keys( $invalid_chars ), array_values( $invalid_chars ), $raw_address ) ) );

		if ( empty( $raw_address ) ) {
			return false;
		}

		$transient_name              = 'jm_geocode_' . md5( $raw_address );
		$geocoded_address            = get_transient( $transient_name );
		$jm_geocode_over_query_limit = get_transient( 'jm_geocode_over_query_limit' );

		// Query limit reached - don't geocode for a while
		if ( $jm_geocode_over_query_limit && false === $geocoded_address ) {
			return false;
		}

		$geocode_api_url = apply_filters( 'restaurant_listings_geolocation_endpoint', self::MAPS_GEOCODE_API_URL, $raw_address );
		if ( false === $geocode_api_url ) {
			return false;
		}

		try {
			if ( false === $geocoded_address || empty( $geocoded_address->features[0] ) ) {
				$result = wp_remote_get(
					$geocode_api_url,
					array(
						'timeout'     => 5,
						'redirection' => 1,
						'httpversion' => '1.1',
						'user-agent'  => 'WordPress/WP-Restaurant-Manager-' . RESTAURANT_LISTING_VERSION . '; ' . get_bloginfo( 'url' ),
						'sslverify'   => false
					)
				);
				$result           = wp_remote_retrieve_body( $result );
				$geocoded_address = json_decode( $result );

				if ( empty( $geocoded_address->features ) ) {
					throw new Exception( __( "Geocoding error", 'wp-restaurant-listings' ) );
				}
			}
		} catch ( Exception $e ) {
			return new WP_Error( 'error', $e->getMessage() );
		}

		$address                      = array();
		$address['lat']               = sanitize_text_field( $geocoded_address->features[0]->center[1] );
		$address['long']              = sanitize_text_field( $geocoded_address->features[0]->center[0] );
		$address['formatted_address'] = sanitize_text_field( $geocoded_address->features[0]->place_name );

		if ( ! empty( $geocoded_address->features[0]->context ) ) {
			$address_data             = $geocoded_address->features[0]->context;
			$address['street_number'] = false;
			$address['street']        = false;
			$address['city']          = false;
			$address['state_short']   = false;
			$address['state_long']    = false;
			$address['postcode']      = false;
			$address['country_short'] = false;
			$address['country_long']  = false;

			foreach ( $address_data as $data ) {

			    $context = explode('.', $data->id );

				switch ( $context[0] ) {
					case 'neighborhood' :
						$address['street']        = sanitize_text_field( $data->text );
					break;
					case 'place' :
						$address['city']          = sanitize_text_field( $data->text );
					break;
					case 'region' :
						$address['state_short']   = sanitize_text_field( $data->short_code );
						$address['state_long']    = sanitize_text_field( $data->text );
					break;
					case 'postcode' :
						$address['postcode']      = sanitize_text_field( $data->text );
					break;
					case 'country' :
						$address['country_short'] = sanitize_text_field( $data->short_code );
						$address['country_long']  = sanitize_text_field( $data->text );
					break;
				}
			}
		}

		return apply_filters( 'restaurant_listings_geolocation_get_location_data', $address, $geocoded_address );
	}
}

WP_Restaurant_Listings_Geocode::instance();
