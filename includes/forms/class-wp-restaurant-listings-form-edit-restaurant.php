<?php

include_once( 'class-wp-restaurant-listings-form-submit-restaurant.php' );

/**
 * Handles the editing of Restaurant Listings from the public facing frontend (from within `[restaurant_dashboard]` shortcode).
 *
 * @package RestaurantListings
 * @since 1.0.0
 * @extends WP_Restaurant_Listings_Form_Submit_Restaurant
 */
class WP_Restaurant_Listings_Form_Edit_Restaurant extends WP_Restaurant_Listings_Form_Submit_Restaurant {

	/**
	 * Form name
	 *
	 * @var string
	 */
	public $form_name = 'edit-restaurant';

	/**
	 * Instance
	 *
	 * @access protected
	 * @var WP_Restaurant_Listings_Form_Edit_Restaurant The single instance of the class
	 */
	protected static $_instance = null;

	/**
	 * Main Instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->restaurant_id = ! empty( $_REQUEST['restaurant_id'] ) ? absint( $_REQUEST[ 'restaurant_id' ] ) : 0;

		if  ( ! restaurant_listings_user_can_edit_restaurant( $this->restaurant_id ) ) {
			$this->restaurant_id = 0;
		}
	}

	/**
	 * output function.
	 *
	 * @param array $atts
	 */
	public function output( $atts = array() ) {
		$this->submit_handler();
		$this->submit();
	}

	/**
	 * Submit Step
	 */
	public function submit() {
		$restaurant = get_post( $this->restaurant_id );

		if ( empty( $this->restaurant_id  ) || ( $restaurant->post_status !== 'publish' && ! restaurant_listings_user_can_edit_pending_submissions() ) ) {
			echo wpautop( __( 'Invalid listings', 'wp-restaurant-listings' ) );
			return;
		}

		$this->init_fields();

		foreach ( $this->fields as $group_key => $group_fields ) {
			foreach ( $group_fields as $key => $field ) {
				if ( ! isset( $this->fields[ $group_key ][ $key ]['value'] ) ) {
					if ( 'restaurant_title' === $key ) {
						$this->fields[ $group_key ][ $key ]['value'] = $restaurant->post_title;

					} elseif ( 'restaurant_description' === $key ) {
						$this->fields[ $group_key ][ $key ]['value'] = $restaurant->post_content;

					} elseif ( 'restaurant_logo' === $key ) {
						$this->fields[ $group_key ][ $key ]['value'] = has_post_thumbnail( $restaurant->ID ) ? get_post_thumbnail_id( $restaurant->ID ) : get_post_meta( $restaurant->ID, '_' . $key, true );

					} elseif ( ! empty( $field['taxonomy'] ) ) {
						$this->fields[ $group_key ][ $key ]['value'] = wp_get_object_terms( $restaurant->ID, $field['taxonomy'], array( 'fields' => 'ids' ) );

					} elseif ( 'restaurant_image_gallery' === $key ) {
                        $this->fields[ $group_key ][ $key ]['value'] = explode( ',', $restaurant->_restaurant_image_gallery );
                    } elseif ( 'restaurant_menu' === $key ) {
                        $this->fields[ $group_key ][ $key ]['value'] = explode( ',', $restaurant->_restaurant_menu );
                    } else {
						$this->fields[ $group_key ][ $key ]['value'] = get_post_meta( $restaurant->ID, '_' . $key, true );
					}
				}
			}
		}

		$this->fields = apply_filters( 'submit_restaurant_form_fields_get_restaurant_data', $this->fields, $restaurant );

		wp_enqueue_script( 'wp-restaurant-listings-restaurant-submission' );

        wp_localize_script( 'wp-restaurant-listings-restaurant-submission', 'restaurant_listings_restaurant_submission', array(
            'time_format' => str_replace( '\\', '\\\\', get_option( 'time_format' ) ),
            'i18n_closed' => __( 'Closed','wp-restaurant-listings' ),
        ) );

		get_restaurant_listings_template( 'restaurant-submit.php', array(
			'form'               => $this->form_name,
			'restaurant_id'             => $this->get_restaurant_id(),
			'action'             => $this->get_action(),
            'basic_fields'       => $this->get_fields( 'basic' ),
            'extra_fields'       => $this->get_fields( 'extra' ),
			'step'               => $this->get_step(),
			'submit_button_text' => __( 'Save changes', 'wp-restaurant-listings' )
			) );
	}

	/**
	 * Submit Step is posted
	 */
	public function submit_handler() {
		if ( empty( $_POST['submit_restaurant'] ) ) {
			return;
		}

		try {

			// Get posted values
			$values = $this->get_posted_fields();

			// Validate required
			if ( is_wp_error( ( $return = $this->validate_fields( $values ) ) ) ) {
				throw new Exception( $return->get_error_message() );
			}

			// Update the restaurant
			$this->save_restaurant( $values['basic']['restaurant_name'], $values['basic']['restaurant_description'], '', $values, true );
			$this->update_restaurant_data( $values );

			// Successful
			switch ( get_post_status( $this->restaurant_id ) ) {
				case 'publish' :
					echo '<div class="restaurant-listings-message">' . __( 'Your changes have been saved.', 'wp-restaurant-listings' ) . ' <a href="' . get_permalink( $this->restaurant_id ) . '">' . __( 'View &rarr;', 'wp-restaurant-listings' ) . '</a>' . '</div>';
				break;
				default :
					echo '<div class="restaurant-listings-message">' . __( 'Your changes have been saved.', 'wp-restaurant-listings' ) . '</div>';
				break;
			}

		} catch ( Exception $e ) {
			echo '<div class="restaurant-listings-error">' . $e->getMessage() . '</div>';
			return;
		}
	}
}
