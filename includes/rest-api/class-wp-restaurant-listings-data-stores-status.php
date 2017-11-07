<?php
/**
 * Declaration of our Status Data Store
 *
 * @package WPRL/REST
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WP_Restaurant_Listings_Data_Stores_Status
 */
class WP_Restaurant_Listings_Data_Stores_Status extends WP_Restaurant_Listings_REST_Data_Store_Abstract
	implements WP_Restaurant_Listings_REST_Interfaces_Data_Store {

	/**
	 * Get all the models (taking into account any filtering)
	 *
	 * @param  WP_Restaurant_Listings_REST_Interfaces_Model|null $filter A filter.
	 * @return WP_Restaurant_Listings_REST_Model_Collection
	 */
	public function get_entities( $filter = null ) {
		return new WP_Restaurant_Listings_REST_Model_Collection( array( $this->get_entity( null ) ) );
	}

	/**
	 * Get a Model Using it's unique identifier
	 *
	 * @param  mixed $id The id of the entity.
	 * @return WP_Restaurant_Listings_REST_Interfaces_Model
	 */
	public function get_entity( $id ) {
		$should_run_page_setup = (bool) get_transient( '_restaurant_listings_activation_redirect' );
		$params = array(
		 'run_page_setup' => $should_run_page_setup,
		);
		return $this->get_model_factory()->create( $params );
	}

	/**
	 * Delete a Model
	 *
	 * @param  WP_Restaurant_Listings_REST_Interfaces_Model $model The model to delete.
	 * @param  array                                $args  Args.
	 * @return mixed
	 */
	public function delete( $model, $args = array() ) {
		return true;
	}

	/**
	 * Update/Insert Model
	 *
	 * @param  WP_Restaurant_Listings_REST_Interfaces_Model $model The model.
	 * @return mixed
	 */
	public function upsert( $model ) {
		$run_page_setup_val = $model->get( 'run_page_setup' );
		if ( $run_page_setup_val ) {
			set_transient( '_restaurant_listings_activation_redirect', 1, HOUR_IN_SECONDS );
		} else {
			delete_transient( '_restaurant_listings_activation_redirect' );
		}
		return true;
	}
}

