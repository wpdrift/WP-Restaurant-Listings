<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Handles the listings of plugin settings.
 *
 * @package RestaurantListings
 * @since 1.0.0
 */
class WP_Restaurant_Listings_Settings {

	/**
	 * The single instance of the class.
	 *
	 * @var self
	 * @since 1.0.0
	 */
	private static $_instance = null;

	/**
	 * Our Settings.
	 *
	 * @var array Settings.
	 */
	protected $settings = array();

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
		$this->settings_group = 'restaurant_listings';
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Get Restaurant Listings Settings
	 *
	 * @return array
	 */
	public function get_settings() {
		if ( 0 === count( $this->settings ) ) {
			$this->init_settings();
		}
		return $this->settings;
	}

	/**
	 * Initializes the configuration for the plugin's setting fields.
	 *
	 * @access protected
	 */
	protected function init_settings() {
		// Prepare roles option.
		$roles         = get_editable_roles();
		$account_roles = array();

		foreach ( $roles as $key => $role ) {
			if ( 'administrator' === $key ) {
				continue;
			}
			$account_roles[ $key ] = $role['name'];
		}

		$this->settings = apply_filters( 'restaurant_listings_settings',
			array(
				'restaurant_listings' => array(
					__( 'Restaurant Listings', 'wp-restaurant-listings' ),
					array(
						array(
							'name'        => 'restaurant_listings_per_page',
							'std'         => '10',
							'placeholder' => '',
							'label'       => __( 'Listings Per Page', 'wp-restaurant-listings' ),
							'desc'        => __( 'Number of restaurant listings to display per page.', 'wp-restaurant-listings' ),
							'attributes'  => array()
						),
						array(
							'name'       => 'restaurant_listings_enable_categories',
							'std'        => '0',
							'label'      => __( 'Categories', 'wp-restaurant-listings' ),
							'cb_label'   => __( 'Enable listings categories', 'wp-restaurant-listings' ),
							'desc'       => __( 'This lets users select from a list of categories when submitting a restaurant. Note: an admin has to create categories before site users can select them.', 'wp-restaurant-listings' ),
							'type'       => 'checkbox',
							'attributes' => array()
						),
						array(
							'name'       => 'restaurant_listings_enable_default_category_multiselect',
							'std'        => '0',
							'label'      => __( 'Multi-select Categories', 'wp-restaurant-listings' ),
							'cb_label'   => __( 'Default to category multiselect', 'wp-restaurant-listings' ),
							'desc'       => __( 'The category selection box will default to allowing multiple selections on the [restaurants] shortcode. Without this, users will only be able to select a single category when submitting restaurants.', 'wp-restaurant-listings' ),
							'type'       => 'checkbox',
							'attributes' => array()
						),
						array(
							'name'       => 'restaurant_listings_category_filter_type',
							'std'        => 'any',
							'label'      => __( 'Category Filter Type', 'wp-restaurant-listings' ),
							'desc'       => __( 'Determines the logic used to display restaurants when selecting multiple categories.', 'wp-restaurant-listings' ),
							'type'       => 'radio',
							'options' => array(
								'any'  => __( 'Restaurants will be shown if within ANY selected category', 'wp-restaurant-listings' ),
								'all' => __( 'Restaurants will be shown if within ALL selected categories', 'wp-restaurant-listings' ),
							)
						),
						array(
							'name'       => 'restaurant_listings_enable_types',
							'std'        => '1',
							'label'      => __( 'Types', 'wp-restaurant-listings' ),
							'cb_label'   => __( 'Enable listings types', 'wp-restaurant-listings' ),
							'desc'       => __( 'This lets users select from a list of types when submitting a restaurant. Note: an admin has to create types before site users can select them.', 'wp-restaurant-listings' ),
							'type'       => 'checkbox',
							'attributes' => array()
						),
						array(
							'name'       => 'restaurant_listings_multi_restaurant_type',
							'std'        => '0',
							'label'      => __( 'Multi-select Listings Types', 'wp-restaurant-listings' ),
							'cb_label'   => __( 'Allow multiple types for listings', 'wp-restaurant-listings' ),
							'desc'       => __( 'This allows users to select more than one type when submitting a restaurant. The metabox on the post editor and the selection box on the front-end restaurant submission form will both reflect this.', 'wp-restaurant-listings' ),
							'type'       => 'checkbox',
							'attributes' => array()
						),
                        array(
                            'name'       => 'restaurant_listings_currency',
                            'std'        => '$',
                            'label'      => __( 'Currency', 'wp-restaurant-listings' ),
                            'type'       => 'text',
                            'desc'       => __( 'This control what currency symbol is displayed in the price range.', 'wp-restaurant-listings'),
                            'attributes' => array()
                        ),
						array(
							'name'       => 'restaurant_listings_date_format',
							'std'        => 'relative',
							'label'      => __( 'Date Format', 'wp-restaurant-listings' ),
							'desc'       => __( 'Choose how you want the published date for restaurants to be displayed on the front-end.', 'wp-restaurant-listings' ),
							'type'       => 'radio',
							'options'    => array(
								'relative' => __( 'Relative to the current date (e.g., 1 day, 1 week, 1 month ago)', 'wp-restaurant-listings' ),
								'default'   => __( 'Default date format as defined in Settings', 'wp-restaurant-listings' ),
							)
						),
						array(
							'name'       => 'restaurant_listings_mapbox_access_token',
							'std'        => '',
							'label'      => __( 'Mapbox Access Token', 'wp-restaurant-listings' ),
							'desc'       => sprintf( __( 'Mapbox requires an aceess token to retrieve location information for restaurant listings. Acquire an access token from the <a href="%s" target="_blank">Mapbox site</a>.', 'wp-restaurant-listings' ), 'https://www.mapbox.com/help/how-access-tokens-work/' ),
							'attributes' => array()
						),
                        array(
							'name'       => 'restaurant_listings_search_radius',
							'std'        => '100',
							'label'      => __( 'Search Radius', 'wp-restaurant-listings' ),
							'desc'       => sprintf( __( 'This control the radius of the search to find restaurant farther or closer to the address user enter.', 'wp-restaurant-listings' ), 'https://www.mapbox.com/help/how-access-tokens-work/' ),
							'type'       => 'number',
                            'attributes'  => array()
						),
					),
				),
				'restaurant_submission' => array(
					__( 'Restaurant Submission', 'wp-restaurant-listings' ),
					array(
						array(
							'name'       => 'restaurant_listings_user_requires_account',
							'std'        => '1',
							'label'      => __( 'Account Required', 'wp-restaurant-listings' ),
							'cb_label'   => __( 'Require an account to submit listings', 'wp-restaurant-listings' ),
							'desc'       => __( 'Limits restaurant listings submissions to registered, logged-in users.', 'wp-restaurant-listings' ),
							'type'       => 'checkbox',
							'attributes' => array()
						),
						array(
							'name'       => 'restaurant_listings_enable_registration',
							'std'        => '1',
							'label'      => __( 'Account Creation', 'wp-restaurant-listings' ),
							'cb_label'   => __( 'Enable account creation during submission', 'wp-restaurant-listings' ),
							'desc'       => __( 'Includes account creation on the listings submission form, to allow non-registered users to create an account and submit a restaurant listings simultaneously.', 'wp-restaurant-listings' ),
							'type'       => 'checkbox',
							'attributes' => array()
						),
						array(
							'name'       => 'restaurant_listings_generate_username_from_email',
							'std'        => '1',
							'label'      => __( 'Account Username', 'wp-restaurant-listings' ),
							'cb_label'   => __( 'Generate usernames from email addresses', 'wp-restaurant-listings' ),
							'desc'       => __( 'Automatically generates usernames for new accounts from the registrant\'s email address. If this is not enabled, a "username" field will display instead.', 'wp-restaurant-listings' ),
							'type'       => 'checkbox',
							'attributes' => array()
						),
						array(
							'name'       => 'restaurant_listings_use_standard_password_setup_email',
							'std'        => '1',
							'label'      => __( 'Account Password', 'wp-restaurant-listings' ),
							'cb_label'   => __( 'Email new users a link to set a password', 'wp-restaurant-listings' ),
							'desc'       => __( 'Sends an email to the user with their username and a link to set their password. If this is not enabled, a "password" field will display instead, and their email address won\'t be verified.', 'wp-restaurant-listings' ),
							'type'       => 'checkbox',
							'attributes' => array()
						),
						array(
							'name'       => 'restaurant_listings_registration_role',
							'std'        => 'owner',
							'label'      => __( 'Account Role', 'wp-restaurant-listings' ),
							'desc'       => __( 'Any new accounts created during submission will have this role. If you haven\'t enabled account creation during submission in the options above, your own method of assigning roles will apply.', 'wp-restaurant-listings' ),
							'type'       => 'select',
							'options'    => $account_roles
						),
						array(
							'name'       => 'restaurant_listings_submission_requires_approval',
							'std'        => '1',
							'label'      => __( 'Moderate New Listings', 'wp-restaurant-listings' ),
							'cb_label'   => __( 'Require admin approval of all new listings submissions', 'wp-restaurant-listings' ),
							'desc'       => __( 'Sets all new submissions to "pending." They will not appear on your site until an admin approves them.', 'wp-restaurant-listings' ),
							'type'       => 'checkbox',
							'attributes' => array()
						),
						array(
							'name'       => 'restaurant_listings_user_can_edit_pending_submissions',
							'std'        => '0',
							'label'      => __( 'Allow Pending Edits', 'wp-restaurant-listings' ),
							'cb_label'   => __( 'Allow editing of pending listings', 'wp-restaurant-listings' ),
							'desc'       => __( 'Users can continue to edit pending listings until they are approved by an admin.', 'wp-restaurant-listings' ),
							'type'       => 'checkbox',
							'attributes' => array()
						),
					)
				),
				'restaurant_pages' => array(
					__( 'Pages', 'wp-restaurant-listings' ),
					array(
						array(
							'name' 		=> 'restaurant_listings_submit_restaurant_form_page_id',
							'std' 		=> '',
							'label' 	=> __( 'Submit Restaurant Form Page', 'wp-restaurant-listings' ),
							'desc'		=> __( 'Select the page where you\'ve used the [submit_restaurant_form] shortcode. This lets the plugin know the location of the form.', 'wp-restaurant-listings' ),
							'type'      => 'page'
						),
						array(
							'name' 		=> 'restaurant_listings_restaurant_dashboard_page_id',
							'std' 		=> '',
							'label' 	=> __( 'Restaurant Dashboard Page', 'wp-restaurant-listings' ),
							'desc'		=> __( 'Select the page where you\'ve used the [restaurant_dashboard] shortcode. This lets the plugin know the location of the dashboard.', 'wp-restaurant-listings' ),
							'type'      => 'page'
						),
						array(
							'name' 		=> 'restaurant_listings_restaurants_page_id',
							'std' 		=> '',
							'label' 	=> __( 'Restaurant Listings Page', 'wp-restaurant-listings' ),
							'desc'		=> __( 'Select the page where you\'ve used the [restaurants] shortcode. This lets the plugin know the location of the restaurant listings page.', 'wp-restaurant-listings' ),
							'type'      => 'page'
						),
                        array(
							'name' 		=> 'restaurant_listings_restaurants_locator_page_id',
							'std' 		=> '',
							'label' 	=> __( 'Restaurant Locator Page', 'wp-restaurant-listings' ),
							'desc'		=> __( 'Select the page where you\'ve used the [restaurants_locator] shortcode. This lets the plugin know the location of the restaurant listings page.', 'wp-restaurant-listings' ),
							'type'      => 'page'
						),
					)
				)
			)
		);
	}

	/**
	 * Registers the plugin's settings with WordPress's Settings API.
	 */
	public function register_settings() {
		$this->init_settings();

		foreach ( $this->settings as $section ) {
			foreach ( $section[1] as $option ) {
				if ( isset( $option['std'] ) )
					add_option( $option['name'], $option['std'] );
				register_setting( $this->settings_group, $option['name'] );
			}
		}
	}

	/**
	 * Shows the plugin's settings page.
	 */
	public function output() {
		$this->init_settings();
		?>
		<div class="wrap restaurant-listings-settings-wrap">
			<form method="post" action="options.php">

				<?php settings_fields( $this->settings_group ); ?>

			    <h2 class="nav-tab-wrapper">
			    	<?php
			    		foreach ( $this->settings as $key => $section ) {
			    			echo '<a href="#settings-' . sanitize_title( $key ) . '" class="nav-tab">' . esc_html( $section[0] ) . '</a>';
			    		}
			    	?>
			    </h2>

				<?php
					if ( ! empty( $_GET['settings-updated'] ) ) {
						flush_rewrite_rules();
						echo '<div class="updated fade restaurant-listings-updated"><p>' . __( 'Settings successfully saved', 'wp-restaurant-listings' ) . '</p></div>';
					}

					foreach ( $this->settings as $key => $section ) {

						echo '<div id="settings-' . sanitize_title( $key ) . '" class="settings_panel">';

						echo '<table class="form-table">';

						foreach ( $section[1] as $option ) {

							$placeholder    = ( ! empty( $option['placeholder'] ) ) ? 'placeholder="' . $option['placeholder'] . '"' : '';
							$class          = ! empty( $option['class'] ) ? $option['class'] : '';
							$value          = get_option( $option['name'] );
							$option['type'] = ! empty( $option['type'] ) ? $option['type'] : '';
							$attributes     = array();

							if ( ! empty( $option['attributes'] ) && is_array( $option['attributes'] ) )
								foreach ( $option['attributes'] as $attribute_name => $attribute_value )
									$attributes[] = esc_attr( $attribute_name ) . '="' . esc_attr( $attribute_value ) . '"';

							echo '<tr valign="top" class="' . $class . '"><th scope="row"><label for="setting-' . $option['name'] . '">' . $option['label'] . '</a></th><td>';

							switch ( $option['type'] ) {

								case "checkbox" :

									?><label><input id="setting-<?php echo $option['name']; ?>" name="<?php echo $option['name']; ?>" type="checkbox" value="1" <?php echo implode( ' ', $attributes ); ?> <?php checked( '1', $value ); ?> /> <?php echo $option['cb_label']; ?></label><?php

									if ( $option['desc'] )
										echo ' <p class="description">' . $option['desc'] . '</p>';

								break;
								case "textarea" :

									?><textarea id="setting-<?php echo $option['name']; ?>" class="large-text" cols="50" rows="3" name="<?php echo $option['name']; ?>" <?php echo implode( ' ', $attributes ); ?> <?php echo $placeholder; ?>><?php echo esc_textarea( $value ); ?></textarea><?php

									if ( $option['desc'] )
										echo ' <p class="description">' . $option['desc'] . '</p>';

								break;
								case "select" :

									?><select id="setting-<?php echo $option['name']; ?>" class="regular-text" name="<?php echo $option['name']; ?>" <?php echo implode( ' ', $attributes ); ?>><?php
										foreach( $option['options'] as $key => $name )
											echo '<option value="' . esc_attr( $key ) . '" ' . selected( $value, $key, false ) . '>' . esc_html( $name ) . '</option>';
									?></select><?php

									if ( $option['desc'] ) {
										echo ' <p class="description">' . $option['desc'] . '</p>';
									}

								break;
								case "radio":
									?><fieldset>
										<legend class="screen-reader-text">
											<span><?php echo esc_html( $option['label'] ); ?></span>
										</legend><?php

									if ( $option['desc'] ) {
										echo '<p class="description">' . $option['desc'] . '</p>';
									}

									foreach( $option['options'] as $key => $name )
										echo '<label><input name="' . esc_attr( $option['name'] ) . '" type="radio" value="' . esc_attr( $key ) . '" ' . checked( $value, $key, false ) . ' />' . esc_html( $name ) . '</label><br>';

									?></fieldset><?php

								break;
								case "page" :

									$args = array(
										'name'             => $option['name'],
										'id'               => $option['name'],
										'sort_column'      => 'menu_order',
										'sort_order'       => 'ASC',
										'show_option_none' => __( '--no page--', 'wp-restaurant-listings' ),
										'echo'             => false,
										'selected'         => absint( $value )
									);

									echo str_replace(' id=', " data-placeholder='" . __( 'Select a page&hellip;', 'wp-restaurant-listings' ) .  "' id=", wp_dropdown_pages( $args ) );

									if ( $option['desc'] ) {
										echo ' <p class="description">' . $option['desc'] . '</p>';
									}

								break;
								case "password" :

									?><input id="setting-<?php echo $option['name']; ?>" class="regular-text" type="password" name="<?php echo $option['name']; ?>" value="<?php echo esc_attr( $value ); ?>" <?php echo implode( ' ', $attributes ); ?> <?php echo $placeholder; ?> /><?php

									if ( $option['desc'] ) {
										echo ' <p class="description">' . $option['desc'] . '</p>';
									}

								break;
								case "number" :
									?><input id="setting-<?php echo $option['name']; ?>" class="regular-text" type="number" name="<?php echo $option['name']; ?>" value="<?php echo esc_attr( $value ); ?>" <?php echo implode( ' ', $attributes ); ?> <?php echo $placeholder; ?> /><?php

									if ( $option['desc'] ) {
										echo ' <p class="description">' . $option['desc'] . '</p>';
									}
								break;
								case "" :
								case "input" :
								case "text" :
									?><input id="setting-<?php echo $option['name']; ?>" class="regular-text" type="text" name="<?php echo $option['name']; ?>" value="<?php echo esc_attr( $value ); ?>" <?php echo implode( ' ', $attributes ); ?> <?php echo $placeholder; ?> /><?php

									if ( $option['desc'] ) {
										echo ' <p class="description">' . $option['desc'] . '</p>';
									}
								break;
								default :
									do_action( 'wp_restaurant_listings_admin_field_' . $option['type'], $option, $attributes, $value, $placeholder );
								break;

							}

							echo '</td></tr>';
						}

						echo '</table></div>';

					}
				?>
				<p class="submit">
					<input type="submit" class="button-primary" value="<?php _e( 'Save Changes', 'wp-restaurant-listings' ); ?>" />
				</p>
		    </form>
		</div>
		<script type="text/javascript">
			jQuery('.nav-tab-wrapper a').click(function() {
				jQuery('.settings_panel').hide();
				jQuery('.nav-tab-active').removeClass('nav-tab-active');
				jQuery( jQuery(this).attr('href') ).show();
				jQuery(this).addClass('nav-tab-active');
				return false;
			});
			var goto_hash = window.location.hash;
			if ( goto_hash ) {
				var the_tab = jQuery( 'a[href="' + goto_hash + '"]' );
				if ( the_tab.length > 0 ) {
					the_tab.click();
				} else {
					jQuery( '.nav-tab-wrapper a:first' ).click();
				}
			} else {
				jQuery( '.nav-tab-wrapper a:first' ).click();
			}
			var $use_standard_password_setup_email = jQuery('#setting-restaurant_listings_use_standard_password_setup_email');
			var $generate_username_from_email = jQuery('#setting-restaurant_listings_generate_username_from_email');
			var $restaurant_listings_registration_role = jQuery('#setting-restaurant_listings_registration_role');

			jQuery('#setting-restaurant_listings_enable_registration').change(function(){
				if ( jQuery( this ).is(':checked') ) {
					$restaurant_listings_registration_role.closest('tr').show();
					$use_standard_password_setup_email.closest('tr').show();
					$generate_username_from_email.closest('tr').show();
				} else {
					$restaurant_listings_registration_role.closest('tr').hide();
					$use_standard_password_setup_email.closest('tr').hide();
					$generate_username_from_email.closest('tr').hide();
				}
			}).change();

			// If generate username is enabled on page load, assume use_standard_password_setup_email has been cleared.
			// Default is true, so let's sneakily set it to that before it gets cleared and disabled.
			if ( $generate_username_from_email.is(':checked') ) {
				$use_standard_password_setup_email.prop('checked', true);
			}

			$generate_username_from_email.change(function() {
				if ( jQuery( this ).is(':checked') ) {
				    $use_standard_password_setup_email.data('original-state', $use_standard_password_setup_email.is(':checked')).prop('checked', true).prop('disabled', true);
				} else {
					$use_standard_password_setup_email.prop('disabled', false);
					if ( undefined !== $use_standard_password_setup_email.data('original-state') ) {
						$use_standard_password_setup_email.prop('checked', $use_standard_password_setup_email.data('original-state'));
					}
				}
			}).change();
		</script>
		<?php
	}
}
