<?php
/**
 * Declaration of our Status Model
 *
 * @package WPRL/REST
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WP_Restaurant_Listings_Models_Status
 */
class WP_Restaurant_Listings_Models_Status extends WP_Restaurant_Listings_REST_Model
	implements WP_Restaurant_Listings_REST_Interfaces_Permissions_Provider {


	/**
	 * Declare our fields
	 *
	 * @return array
	 * @throws WP_Restaurant_Listings_REST_Exception Exc.
	 */
	public static function declare_fields() {
		$env = self::get_environment();
		return array(
		 $env->field( 'run_page_setup', 'Should we run page setup' )
			 ->with_type( $env->type( 'boolean' ) ),
		);
	}

	/**
	 * Handle Permissions for a REST Controller Action
	 *
	 * @param  WP_REST_Request $request The request.
	 * @param  string          $action  The action (e.g. index, create update etc).
	 * @return bool
	 */
	public static function permissions_check( $request, $action ) {
		if ( in_array( $action, array( 'index', 'show' ), true ) ) {
			return true;
		}
		return current_user_can( 'manage_options' );
	}
}

