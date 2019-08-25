<?php

/**
 * Handles the editing of Restaurant Listings from the public facing frontend (from within `[submit_restaurant_form]` shortcode).
 *
 * @package RestaurantListings
 * @extends WP_Restaurant_Listings_Form
 * @since 1.0.0
 */
class WP_Restaurant_Listings_Form_Submit_Restaurant extends WP_Restaurant_Listings_Form {

	/**
	 * Form name.
	 *
	 * @var string
	 */
	public $form_name = 'submit-restaurant';

	/**
	 * Restaurant listings ID.
	 *
	 * @access protected
	 * @var int
	 */
	protected $restaurant_id;

	/**
	 * Preview restaurant (unused)
	 *
	 * @access protected
	 * @var string
	 */
	protected $preview_restaurant;

	/**
	 * Stores static instance of class.
	 *
	 * @access protected
	 * @var WP_Restaurant_Listings_Form_Submit_Restaurant The single instance of the class
	 */
	protected static $_instance = null;

	/**
	 * Returns static instance of class.
	 *
	 * @return self
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
		add_action( 'wp', array( $this, 'process' ) );

		$this->steps  = (array) apply_filters( 'submit_restaurant_steps', array(
			'submit' => array(
				'name'     => __( 'Submit Details', 'wp-restaurant-listings' ),
				'view'     => array( $this, 'submit' ),
				'handler'  => array( $this, 'submit_handler' ),
				'priority' => 10
				),
			'preview' => array(
				'name'     => __( 'Preview', 'wp-restaurant-listings' ),
				'view'     => array( $this, 'preview' ),
				'handler'  => array( $this, 'preview_handler' ),
				'priority' => 20
			),
			'done' => array(
				'name'     => __( 'Done', 'wp-restaurant-listings' ),
				'view'     => array( $this, 'done' ),
				'priority' => 30
			)
		) );

		uasort( $this->steps, array( $this, 'sort_by_priority' ) );

		// Get step/restaurant
		if ( isset( $_POST['step'] ) ) {
			$this->step = is_numeric( $_POST['step'] ) ? max( absint( $_POST['step'] ), 0 ) : array_search( $_POST['step'], array_keys( $this->steps ) );
		} elseif ( ! empty( $_GET['step'] ) ) {
			$this->step = is_numeric( $_GET['step'] ) ? max( absint( $_GET['step'] ), 0 ) : array_search( $_GET['step'], array_keys( $this->steps ) );
		}

		$this->restaurant_id = ! empty( $_REQUEST[ 'restaurant_id' ] ) ? absint( $_REQUEST[ 'restaurant_id' ] ) : 0;

		if ( ! restaurant_listings_user_can_edit_restaurant( $this->restaurant_id ) ) {
			$this->restaurant_id = 0;
		}

		// Allow resuming from cookie.
		$this->resume_edit = false;
		if ( ! isset( $_GET[ 'new' ] ) && ( 'before' === get_option( 'restaurant_listings_paid_listings_flow' ) || ! $this->restaurant_id ) && ! empty( $_COOKIE['wp-restaurant-listings-submitting-restaurant-id'] ) && ! empty( $_COOKIE['wp-restaurant-listings-submitting-restaurant-key'] ) ) {
			$restaurant_id     = absint( $_COOKIE['wp-restaurant-listings-submitting-restaurant-id'] );
			$restaurant_status = get_post_status( $restaurant_id );

			if ( ( 'preview' === $restaurant_status || 'pending_payment' === $restaurant_status ) && get_post_meta( $restaurant_id, '_submitting_key', true ) === $_COOKIE['wp-restaurant-listings-submitting-restaurant-key'] ) {
				$this->restaurant_id = $restaurant_id;
				$this->resume_edit = get_post_meta( $restaurant_id, '_submitting_key', true );
			}
		}

		// Load restaurant details
		if ( $this->restaurant_id ) {
			$restaurant_status = get_post_status( $this->restaurant_id );
			if ( ! in_array( $restaurant_status, apply_filters( 'restaurant_listings_valid_submit_restaurant_statuses', array( 'preview' ) ) ) ) {
				$this->restaurant_id = 0;
				$this->step   = 0;
			}
		}
	}

	/**
	 * Gets the submitted restaurant ID.
	 *
	 * @return int
	 */
	public function get_restaurant_id() {
		return absint( $this->restaurant_id );
	}

	/**
	 * Initializes the fields used in the form.
	 */
	public function init_fields() {
		if ( $this->fields ) {
			return;
		}

		if ( restaurant_listings_multi_restaurant_type() ) {
			$restaurant_type = 'term-multiselect';
		} else {
			$restaurant_type = 'term-select';
		}

		$currency_symbol = get_option('restaurant_listings_currency');

		$this->fields = apply_filters( 'submit_restaurant_form_fields', array(
			'basic' => array(
				'restaurant_name' => array(
					'label'       => __( 'Restaurant name', 'wp-restaurant-listings' ),
					'type'        => 'text',
					'required'    => true,
					'placeholder' => __( 'Enter the name of the restaurant', 'wp-restaurant-listings' ),
					'priority'    => 1
				),
				'restaurant_location' => array(
					'label'       => __( 'Address', 'wp-restaurant-listings' ),
					'description' => __( 'Leave this blank if the location is not important', 'wp-restaurant-listings' ),
					'type'        => 'text',
					'required'    => false,
					'placeholder' => __( 'e.g. "44-46 Morningside Road, Edinburgh, Scotland, EH10 4BF"', 'wp-restaurant-listings' ),
					'priority'    => 2
				),
				'restaurant_type' => array(
					'label'       => __( 'Restaurant type', 'wp-restaurant-listings' ),
					'type'        => $restaurant_type,
					'required'    => true,
					'placeholder' => __( 'Choose restaurant type&hellip;', 'wp-restaurant-listings' ),
					'priority'    => 3,
					'default'     => 'full-time',
					'taxonomy'    => 'restaurant_listings_type'
				),
				'restaurant_category' => array(
					'label'       => __( 'Restaurant category', 'wp-restaurant-listings' ),
					'type'        => 'term-multiselect',
					'required'    => true,
					'placeholder' => '',
					'priority'    => 4,
					'default'     => '',
					'taxonomy'    => 'restaurant_listings_category'
				),
				'restaurant_price_range' => array(
					'label'       => __( 'Price Range', 'wp-restaurant-listings' ),
					'type'        => 'select',
					'default'     => 1,
					'options'     => array(
						1 => str_repeat( $currency_symbol, 1 ),
						2 => str_repeat( $currency_symbol, 2 ),
						3 => str_repeat( $currency_symbol, 3 ),
						4 => str_repeat( $currency_symbol, 4 ),
					),
					'required'    => true,
					'priority'    => 5,
					'default'     => '',
				),
				'restaurant_description' => array(
					'label'       => __( 'Description', 'wp-restaurant-listings' ),
					'type'        => 'wp-editor',
					'required'    => true,
					'placeholder' => '',
					'priority'    => 6
				),
				'application' => array(
					'label'       => __( 'Restaurant email', 'wp-restaurant-listings' ),
					'type'        => 'text',
					'sanitizer'   => 'url_or_email',
					'required'    => true,
					'placeholder' => __( 'Enter an email address or website URL', 'wp-restaurant-listings' ),
					'priority'    => 7
				)
			),
			'extra' => array(
				'restaurant_phone' => array(
					'label'       => __( 'Phone', 'wp-restaurant-listings' ),
					'type'        => 'text',
					'required'    => false,
					'placeholder' => __( '(877) 273-3049', 'wp-restaurant-listings' ),
					'priority'    => 1
				),
				'restaurant_website' => array(
					'label'       => __( 'Website', 'wp-restaurant-listings' ),
					'type'        => 'text',
					'sanitizer'   => 'url',
					'required'    => false,
					'placeholder' => __( 'http://', 'wp-restaurant-listings' ),
					'priority'    => 2
				),
				'restaurant_tagline' => array(
					'label'       => __( 'Tagline', 'wp-restaurant-listings' ),
					'type'        => 'text',
					'required'    => false,
					'placeholder' => __( 'Brief description about the restaurant', 'wp-restaurant-listings' ),
					'priority'    => 3
				),
				'restaurant_video' => array(
					'label'       => __( 'Video', 'wp-restaurant-listings' ),
					'type'        => 'text',
					'sanitizer'   => 'url',
					'required'    => false,
					'placeholder' => __( 'A link to a video about your restaurant', 'wp-restaurant-listings' ),
					'priority'    => 4
				),
				'restaurant_twitter' => array(
					'label'       => __( 'Twitter username', 'wp-restaurant-listings' ),
					'type'        => 'text',
					'required'    => false,
					'placeholder' => __( '@yourcompany', 'wp-restaurant-listings' ),
					'priority'    => 5
				),
				'restaurant_logo' => array(
					'label'       => __( 'Logo', 'wp-restaurant-listings' ),
					'type'        => 'file',
					'required'    => false,
					'placeholder' => '',
					'priority'    => 6,
					'ajax'        => true,
					'multiple'    => false,
					'allowed_mime_types' => array(
						'jpg'  => 'image/jpeg',
						'jpeg' => 'image/jpeg',
						'gif'  => 'image/gif',
						'png'  => 'image/png'
					)
				),
				'restaurant_image_gallery' => array(
					'label'       => __( 'Gallery Images',  'wp-restaurant-listings' ),
					'type'        => 'file',
					'multiple'    => true,
					'required'    => false,
					'placeholder' => '',
					'priority'    => 7,
					'ajax'        => true,
					'allowed_mime_types' => array(
						'jpg'  => 'image/jpeg',
						'jpeg' => 'image/jpeg',
						'gif'  => 'image/gif',
						'png'  => 'image/png'
					)
				),
				'restaurant_menu' => array(
					'label'       => __( 'Menu',  'wp-restaurant-listings' ),
					'type'        => 'file',
					'multiple'    => true,
					'required'    => false,
					'placeholder' => '',
					'priority'    => 8,
					'ajax'        => true,
					'allowed_mime_types' => array(
						'jpg'  => 'image/jpeg',
						'jpeg' => 'image/jpeg',
						'gif'  => 'image/gif',
						'png'  => 'image/png'
					)
				),
				'restaurant_hours' => array(
					'label'       => __( 'Hours of Operation', 'wp-restaurant-listings' ),
					'type'        => 'business-hours',
					'required'    => false,
					'placeholder' => '',
					'priority'    => 9,
					'default'     => ''
				),
			)
		) );

		if ( ! get_option( 'restaurant_listings_enable_categories' ) || wp_count_terms( 'restaurant_listings_category' ) == 0 ) {
			unset( $this->fields['basic']['restaurant_category'] );
		}
		if ( ! get_option( 'restaurant_listings_enable_types' ) || wp_count_terms( 'restaurant_listings_type' ) == 0 ) {
			unset( $this->fields['basic']['restaurant_type'] );
		}
	}

	/**
	 * Validates the posted fields.
	 *
	 * @param array $values
	 * @throws Exception Uploaded file is not a valid mime-type or other validation error
	 * @return bool|WP_Error True on success, WP_Error on failure
	 */
	protected function validate_fields( $values ) {
		foreach ( $this->fields as $group_key => $group_fields ) {
			foreach ( $group_fields as $key => $field ) {
				if ( $field['required'] && empty( $values[ $group_key ][ $key ] ) ) {
					return new WP_Error( 'validation-error', sprintf( __( '%s is a required field', 'wp-restaurant-listings' ), $field['label'] ) );
				}
				if ( ! empty( $field['taxonomy'] ) && in_array( $field['type'], array( 'term-checklist', 'term-select', 'term-multiselect' ) ) ) {
					if ( is_array( $values[ $group_key ][ $key ] ) ) {
						$check_value = $values[ $group_key ][ $key ];
					} else {
						$check_value = empty( $values[ $group_key ][ $key ] ) ? array() : array( $values[ $group_key ][ $key ] );
					}
					foreach ( $check_value as $term ) {
						if ( ! term_exists( $term, $field['taxonomy'] ) ) {
							return new WP_Error( 'validation-error', sprintf( __( '%s is invalid', 'wp-restaurant-listings' ), $field['label'] ) );
						}
					}
				}
				if ( 'file' === $field['type'] && ! empty( $field['allowed_mime_types'] ) ) {
					if ( is_array( $values[ $group_key ][ $key ] ) ) {
						$check_value = array_filter( $values[ $group_key ][ $key ] );
					} else {
						$check_value = array_filter( array( $values[ $group_key ][ $key ] ) );
					}
					if ( ! empty( $check_value ) ) {
						foreach ( $check_value as $file_url ) {
							$file_url  = current( explode( '?', $file_url ) );
							$file_info = wp_check_filetype( $file_url );

							if ( ! is_numeric( $file_url ) && $file_info && ! in_array( $file_info['type'], $field['allowed_mime_types'] ) ) {
								throw new Exception( sprintf( __( '"%s" (filetype %s) needs to be one of the following file types: %s', 'wp-restaurant-listings' ), $field['label'], $file_info['ext'], implode( ', ', array_keys( $field['allowed_mime_types'] ) ) ) );
							}
						}
					}
				}
			}
		}

		// Application method
		if ( isset( $values['restaurant']['application'] ) && ! empty( $values['restaurant']['application'] ) ) {
			$values['restaurant']['application'] = str_replace( ' ', '+', $values['restaurant']['application'] );

			if ( ! is_email( $values['restaurant']['application'] ) ) {
				// Prefix http if needed
				if ( ! strstr( $values['restaurant']['application'], 'http:' ) && ! strstr( $values['restaurant']['application'], 'https:' ) ) {
					$values['restaurant']['application'] = 'http://' . $values['restaurant']['application'];
				}
				if ( ! filter_var( $values['restaurant']['application'], FILTER_VALIDATE_URL ) ) {
					throw new Exception( __( 'Please enter a valid application email address or URL', 'wp-restaurant-listings' ) );
				}
			}
		}

		return apply_filters( 'submit_restaurant_form_validate_fields', true, $this->fields, $values );
	}

	/**
	 * Returns an array of the restaurant types indexed by slug. (Unused)
	 *
	 * @return array
	 */
	private function restaurant_types() {
		$options = array();
		$terms   = get_restaurant_listings_types();
		foreach ( $terms as $term ) {
			$options[ $term->slug ] = $term->name;
		}
		return $options;
	}

	/**
	 * Displays the form.
	 */
	public function submit() {
		$this->init_fields();


		// Load data if neccessary
		if ( $this->restaurant_id ) {
			$restaurant = get_post( $this->restaurant_id );
			foreach ( $this->fields as $group_key => $group_fields ) {
				foreach ( $group_fields as $key => $field ) {
					switch ( $key ) {
						case 'restaurant_title' :
							$this->fields[ $group_key ][ $key ]['value'] = $restaurant->post_title;
						break;
						case 'restaurant_description' :
							$this->fields[ $group_key ][ $key ]['value'] = $restaurant->post_content;
						break;
						case 'restaurant_type' :
							$this->fields[ $group_key ][ $key ]['value'] = wp_get_object_terms( $restaurant->ID, 'restaurant_listings_type', array( 'fields' => 'ids' ) );
							if ( ! restaurant_listings_multi_restaurant_type() ) {
								$this->fields[ $group_key ][ $key ]['value'] = current( $this->fields[ $group_key ][ $key ]['value'] );
							}
						break;
						case 'restaurant_category' :
							$this->fields[ $group_key ][ $key ]['value'] = wp_get_object_terms( $restaurant->ID, 'restaurant_listings_category', array( 'fields' => 'ids' ) );
						break;
						case 'restaurant_logo' :
							$this->fields[ $group_key ][ $key ]['value'] = has_post_thumbnail( $restaurant->ID ) ? get_post_thumbnail_id( $restaurant->ID ) : get_post_meta( $restaurant->ID, '_' . $key, true );
						break;
						case 'restaurant_image_gallery' :
							$this->fields[ $group_key ][ $key ]['value'] = explode( ',', $restaurant->_restaurant_image_gallery );
							break;
						case 'restaurant_menu' :
							$this->fields[ $group_key ][ $key ]['value'] = explode( ',', $restaurant->_restauruant_menu );
							break;
						default:
							$this->fields[ $group_key ][ $key ]['value'] = get_post_meta( $restaurant->ID, '_' . $key, true );
						break;
					}
				}
			}

			$this->fields = apply_filters( 'submit_restaurant_form_fields_get_restaurant_data', $this->fields, $restaurant );

		// Get user meta
		} elseif ( is_user_logged_in() && empty( $_POST['submit_restaurant'] ) ) {
			if ( ! empty( $this->fields['restaurant'] ) ) {
				foreach ( $this->fields['restaurant'] as $key => $field ) {
					$this->fields['restaurant'][ $key ]['value'] = get_user_meta( get_current_user_id(), '_' . $key, true );
				}
			}
			if ( ! empty( $this->fields['restaurant']['application'] ) ) {
				$current_user = wp_get_current_user();
				$this->fields['restaurant']['application']['value'] = $current_user->user_email;
			}
			$this->fields = apply_filters( 'submit_restaurant_form_fields_get_user_data', $this->fields, get_current_user_id() );
		}

		wp_enqueue_script( 'wp-restaurant-listings-restaurant-submission' );

		wp_localize_script( 'wp-restaurant-listings-restaurant-submission', 'restaurant_listings_restaurant_submission', array(
			'time_format' => str_replace( '\\', '\\\\', get_option( 'time_format' ) ),
			'i18n_closed' => __( 'Closed','wp-restaurant-listings' ),
		) );

		get_restaurant_listings_template( 'restaurant-submit.php', array(
			'form'               => $this->form_name,
			'restaurant_id'      => $this->get_restaurant_id(),
			'resume_edit'        => $this->resume_edit,
			'action'             => $this->get_action(),
			'basic_fields'       => $this->get_fields( 'basic' ),
			'extra_fields'       => $this->get_fields( 'extra' ),
			'step'               => $this->get_step(),
			'submit_button_text' => apply_filters( 'submit_restaurant_form_submit_button_text', __( 'Preview', 'wp-restaurant-listings' ) )
		) );
	}

	/**
	 * Handles the submission of form data.
	 */
	public function submit_handler() {
		try {
			// Init fields
			$this->init_fields();

			// Get posted values
			$values = $this->get_posted_fields();

			if ( empty( $_POST['submit_restaurant'] ) ) {
				return;
			}

			// Validate required
			if ( is_wp_error( ( $return = $this->validate_fields( $values ) ) ) ) {
				throw new Exception( $return->get_error_message() );
			}

			// Account creation
			if ( ! is_user_logged_in() ) {
				$create_account = false;

				if ( restaurant_listings_enable_registration() ) {
					if ( restaurant_listings_user_requires_account() ) {
						if ( ! restaurant_listings_generate_username_from_email() && empty( $_POST['create_account_username'] ) ) {
							throw new Exception( __( 'Please enter a username.', 'wp-restaurant-listings' ) );
						}
						if ( ! wprl_use_standard_password_setup_email() ) {
							if ( empty( $_POST['create_account_password'] ) ) {
								throw new Exception( __( 'Please enter a password.', 'wp-restaurant-listings' ) );
							}
						}
						if ( empty( $_POST['create_account_email'] ) ) {
							throw new Exception( __( 'Please enter your email address.', 'wp-restaurant-listings' ) );
						}
					}

					if ( ! wprl_use_standard_password_setup_email() && ! empty( $_POST['create_account_password'] ) ) {
						if ( empty( $_POST['create_account_password_verify'] ) || $_POST['create_account_password_verify'] !== $_POST['create_account_password'] ) {
							throw new Exception( __( 'Passwords must match.', 'wp-restaurant-listings' ) );
						}
						if ( ! wprl_validate_new_password( $_POST['create_account_password'] ) ) {
							$password_hint = wprl_get_password_rules_hint();
							if ( $password_hint ) {
								throw new Exception( sprintf( __( 'Invalid Password: %s', 'wp-restaurant-listings' ), $password_hint ) );
							} else {
								throw new Exception( __( 'Password is not valid.', 'wp-restaurant-listings' ) );
							}
						}
					}

					if ( ! empty( $_POST['create_account_email'] ) ) {
						$create_account = wp_restaurant_listings_create_account( array(
							'username' => ( restaurant_listings_generate_username_from_email() || empty( $_POST['create_account_username'] ) ) ? '' : $_POST['create_account_username'],
							'password' => ( wprl_use_standard_password_setup_email() || empty( $_POST['create_account_password'] ) ) ? '' : $_POST['create_account_password'],
							'email'    => $_POST['create_account_email'],
							'role'     => get_option( 'restaurant_listings_registration_role' ),
						) );
					}
				}

				if ( is_wp_error( $create_account ) ) {
					throw new Exception( $create_account->get_error_message() );
				}
			}

			if ( restaurant_listings_user_requires_account() && ! is_user_logged_in() ) {
				throw new Exception( __( 'You must be signed in to post a new listings.', 'wp-restaurant-listings' ) );
			}

			// Update the restaurant
			$this->save_restaurant( $values['basic']['restaurant_name'], $values['basic']['restaurant_description'], $this->restaurant_id ? '' : 'preview', $values );
			$this->update_restaurant_data( $values );

			// Successful, show next step
			$this->step ++;

		} catch ( Exception $e ) {
			$this->add_error( $e->getMessage() );
			return;
		}
	}

	/**
	 * Updates or creates a restaurant listings from posted data.
	 *
	 * @param  string $post_title
	 * @param  string $post_content
	 * @param  string $status
	 * @param  array  $values
	 * @param  bool   $update_slug
	 */
	protected function save_restaurant( $post_title, $post_content, $status = 'preview', $values = array(), $update_slug = true ) {
		$restaurant_data = array(
			'post_title'     => $post_title,
			'post_content'   => $post_content,
			'post_type'      => 'restaurant_listings',
			'comment_status' => 'open'
		);

		if ( $update_slug ) {
			$restaurant_slug   = array();

			// Prepend with restaurant name
			if ( apply_filters( 'submit_restaurant_form_prefix_post_name_with_company', true ) && ! empty( $values['restaurant']['restaurant_name'] ) ) {
				$restaurant_slug[] = $values['restaurant']['restaurant_name'];
			}

			// Prepend location
			if ( apply_filters( 'submit_restaurant_form_prefix_post_name_with_location', true ) && ! empty( $values['restaurant']['restaurant_location'] ) ) {
				$restaurant_slug[] = $values['restaurant']['restaurant_location'];
			}

			// Prepend with restaurant type
			if ( apply_filters( 'submit_restaurant_form_prefix_post_name_with_restaurant_type', true ) && ! empty( $values['restaurant']['restaurant_type'] ) ) {
				if ( ! restaurant_listings_multi_restaurant_type() ) {
					$restaurant_slug[] = $values['restaurant']['restaurant_type'];
				} else {
					$terms = $values['restaurant']['restaurant_type'];

					foreach ( $terms as $term ) {
						$term = get_term_by( 'id', intval( $term ), 'restaurant_listings_type' );

						if ( $term ) {
							$restaurant_slug[] = $term->slug;
						}
					}
				}
			}

			$restaurant_slug[]            = $post_title;
			$restaurant_data['post_name'] = sanitize_title( implode( '-', $restaurant_slug ) );
		}

		if ( $status ) {
			$restaurant_data['post_status'] = $status;
		}

		$restaurant_data = apply_filters( 'submit_restaurant_form_save_restaurant_data', $restaurant_data, $post_title, $post_content, $status, $values );

		if ( $this->restaurant_id ) {
			$restaurant_data['ID'] = $this->restaurant_id;
			wp_update_post( $restaurant_data );
		} else {
			$this->restaurant_id = wp_insert_post( $restaurant_data );

			if ( ! headers_sent() ) {
				$submitting_key = uniqid();

				setcookie( 'wp-restaurant-listings-submitting-restaurant-id', $this->restaurant_id, 0, COOKIEPATH, COOKIE_DOMAIN, false );
				setcookie( 'wp-restaurant-listings-submitting-restaurant-key', $submitting_key, 0, COOKIEPATH, COOKIE_DOMAIN, false );

				update_post_meta( $this->restaurant_id, '_submitting_key', $submitting_key );
			}
		}
	}

	/**
	 * Creates a file attachment.
	 *
	 * @param  string $attachment_url
	 * @return int attachment id
	 */
	protected function create_attachment( $attachment_url ) {
		include_once( ABSPATH . 'wp-admin/includes/image.php' );
		include_once( ABSPATH . 'wp-admin/includes/media.php' );

		$upload_dir     = wp_upload_dir();
		$attachment_url = str_replace( array( $upload_dir['baseurl'], WP_CONTENT_URL, site_url( '/' ) ), array( $upload_dir['basedir'], WP_CONTENT_DIR, ABSPATH ), $attachment_url );

		if ( empty( $attachment_url ) || ! is_string( $attachment_url ) ) {
			return 0;
		}

		$attachment     = array(
			'post_title'   => get_the_restaurant_title( $this->restaurant_id ),
			'post_content' => '',
			'post_status'  => 'inherit',
			'post_parent'  => $this->restaurant_id,
			'guid'         => $attachment_url
		);

		if ( $info = wp_check_filetype( $attachment_url ) ) {
			$attachment['post_mime_type'] = $info['type'];
		}

		$attachment_id = wp_insert_attachment( $attachment, $attachment_url, $this->restaurant_id );

		if ( ! is_wp_error( $attachment_id ) ) {
			wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $attachment_url ) );
			return $attachment_id;
		}

		return 0;
	}

	/**
	 * Sets restaurant meta and terms based on posted values.
	 *
	 * @param  array $values
	 */
	protected function update_restaurant_data( $values ) {
		// Set defaults
		add_post_meta( $this->restaurant_id, '_featured', 0, true );

		$maybe_attach = array();
		$image_gallery = array();
		$menu = array();

		// Loop fields and save meta and term data
		foreach ( $this->fields as $group_key => $group_fields ) {
			foreach ( $group_fields as $key => $field ) {
				// Save taxonomies
				if ( ! empty( $field['taxonomy'] ) ) {
					if ( is_array( $values[ $group_key ][ $key ] ) ) {
						wp_set_object_terms( $this->restaurant_id, $values[ $group_key ][ $key ], $field['taxonomy'], false );
					} else {
						wp_set_object_terms( $this->restaurant_id, array( $values[ $group_key ][ $key ] ), $field['taxonomy'], false );
					}

				// Restaurant logo is a featured image
				} elseif ( 'restaurant_logo' === $key ) {
					$attachment_id = is_numeric($values[$group_key][$key]) ? absint($values[$group_key][$key]) : $this->create_attachment($values[$group_key][$key]);
					if (empty($attachment_id)) {
						delete_post_thumbnail($this->restaurant_id);
					} else {
						set_post_thumbnail($this->restaurant_id, $attachment_id);
					}
					update_user_meta(get_current_user_id(), '_restaurant_logo', $attachment_id);


				// Restaurant image gallery
				} elseif ( 'restaurant_image_gallery' === $key ) {

					$field_values = $values[$group_key][$key];

					if (is_array($field_values) && sizeof($field_values) >0 ) {

						foreach ( $field_values as $image ) {
							$attachment_id = is_numeric( $image ) ? absint( $image ) : $this->create_attachment( $image );
							if ( !empty( $attachment_id ) ) {
								$image_gallery[] = $attachment_id;
							}
						}

						$image_gallery = array_filter( $image_gallery );

						if ( sizeof( $image_gallery ) ) {
							$this->fields['extra']['restaurant_image_gallery']['value'] = $image_gallery;
							update_post_meta( $this->restaurant_id, '_restaurant_image_gallery', join( ',', $image_gallery ) );
						}
					}

					// Restaurant menu
				} elseif ( 'restaurant_menu' === $key ) {

					$field_values = $values[$group_key][$key];

					if ( is_array($field_values) && sizeof($field_values) > 0 ) {
						foreach ( $field_values as $image ) {
							$attachment_id = is_numeric( $image ) ? absint( $image ) : $this->create_attachment( $image );
							if ( !empty( $attachment_id ) ) {
								$menu[] = $attachment_id;
							}
						}

						$menu = array_filter( $menu );

						if ( sizeof( $menu ) ) {
							$this->fields['extra']['restaurant_menu']['value'] = $menu;
							update_post_meta( $this->restaurant_id, '_restaurant_menu', join( ',', $menu ) );
						}
					}

					// Save meta data
				} else {

					if (  'restaurant_image_gallery' !== $key ) {
						update_post_meta( $this->restaurant_id, '_' . $key, $values[ $group_key ][ $key ] );
					}

					// Handle attachments
					if ( 'file' === $field['type'] ) {
						if ( is_array( $values[ $group_key ][ $key ] ) ) {
							foreach ( $values[ $group_key ][ $key ] as $file_url ) {
								$maybe_attach[] = $file_url;
							}
						} else {
							$maybe_attach[] = $values[ $group_key ][ $key ];
						}
					}
				}
			}
		}

		$maybe_attach = array_filter( $maybe_attach );

		// Handle attachments
		if ( sizeof( $maybe_attach ) && apply_filters( 'restaurant_listings_attach_uploaded_files', true ) ) {
			// Get attachments
			$attachments     = get_posts( 'post_parent=' . $this->restaurant_id . '&post_type=attachment&fields=ids&numberposts=-1' );
			$attachment_urls = array();
			$attachment_ids  = array();

			// Loop attachments already attached to the restaurant
			foreach ( $attachments as $attachment_id ) {
				$attachment_urls[] = wp_get_attachment_url( $attachment_id );
			}

			foreach ( $maybe_attach as $attachment_url ) {
				if ( ! in_array( $attachment_url, $attachment_urls ) ) {
					$this->create_attachment( $attachment_url );
				}
			}

		}

		// And user meta to save time in future
		if ( is_user_logged_in() ) {
			update_user_meta( get_current_user_id(), '_restaurant_name', isset( $values['restaurant']['restaurant_name'] ) ? $values['restaurant']['restaurant_name'] : '' );
			update_user_meta( get_current_user_id(), '_restaurant_website', isset( $values['restaurant']['restaurant_website'] ) ? $values['restaurant']['restaurant_website'] : '' );
			update_user_meta( get_current_user_id(), '_restaurant_tagline', isset( $values['restaurant']['restaurant_tagline'] ) ? $values['restaurant']['restaurant_tagline'] : '' );
			update_user_meta( get_current_user_id(), '_restaurant_twitter', isset( $values['restaurant']['restaurant_twitter'] ) ? $values['restaurant']['restaurant_twitter'] : '' );
			update_user_meta( get_current_user_id(), '_restaurant_video', isset( $values['restaurant']['restaurant_video'] ) ? $values['restaurant']['restaurant_video'] : '' );
		}

		do_action( 'restaurant_listings_update_restaurant_data', $this->restaurant_id, $values );
	}

	/**
	 * Displays preview of Restaurant Listings.
	 */
	public function preview() {
		global $post, $restaurant_preview;

		if ( $this->restaurant_id ) {
			$restaurant_preview       = true;
			$post              = get_post( $this->restaurant_id );
			$post->post_status = 'preview';

			setup_postdata( $post );

			get_restaurant_listings_template( 'restaurant-preview.php', array(
				'form' => $this
			) );

			wp_reset_postdata();
		}
	}

	/**
	 * Handles the preview step form response.
	 */
	public function preview_handler() {
		if ( ! $_POST ) {
			return;
		}

		// Edit = show submit form again
		if ( ! empty( $_POST['edit_restaurant'] ) ) {
			$this->step --;
		}

		// Continue = change restaurant status then show next screen
		if ( ! empty( $_POST['continue'] ) ) {
			$restaurant = get_post( $this->restaurant_id );

			if ( in_array( $restaurant->post_status, array( 'preview' ) ) ) {
				// Update restaurant listings
				$update_restaurant                  = array();
				$update_restaurant['ID']            = $restaurant->ID;
				$update_restaurant['post_status']   = apply_filters( 'submit_restaurant_post_status', get_option( 'restaurant_listings_submission_requires_approval' ) ? 'pending' : 'publish', $restaurant );
				$update_restaurant['post_date']     = current_time( 'mysql' );
				$update_restaurant['post_date_gmt'] = current_time( 'mysql', 1 );
				$update_restaurant['post_author']   = get_current_user_id();

				wp_update_post( $update_restaurant );
			}

			$this->step ++;
		}
	}

	/**
	 * Displays the final screen after a restaurant listings has been submitted.
	 */
	public function done() {
		do_action( 'restaurant_listings_restaurant_submitted', $this->restaurant_id );
		get_restaurant_listings_template( 'restaurant-submitted.php', array( 'restaurant' => get_post( $this->restaurant_id ) ) );
	}
}
