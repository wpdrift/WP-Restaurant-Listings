<?php
/**
 * Declaration of our Status Filters (will be used in GET requests)
 *
 * @package WPRL/REST
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WP_Restaurant_Listings_Filters_Status
 */
class WP_Restaurant_Listings_Filters_Status extends WP_Restaurant_Listings_REST_Model {

	/**
	 * Declare our fields
	 *
	 * @return array
	 * @throws WP_Restaurant_Listings_REST_Exception Exc.
	 */
	public static function declare_fields() {
		$env = self::get_environment();
		return array(
		 $env->field( 'keys', 'The status keys to return' )
			 ->with_type( $env->type( 'array:string' ) )
			 ->with_before_set( 'explode_keys' )
			 ->with_default( array() ),
		);
	}

	/**
	 * Explode keys
	 *
	 * @param  mixed $keys  The keys.
	 * @return array
	 */
	public function explode_keys( $keys ) {
		if ( is_string( $keys ) ) {
			return explode( ',', $keys );
		}
		return $keys;
	}
}

