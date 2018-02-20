<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Handles front admin page for WP Restaurant Listings.
 *
 * @package RestaurantListings
 * @see https://github.com/woocommerce/woocommerce/blob/3.0.8/includes/admin/class-wc-admin-permalink-settings.php  Based on WooCommerce's implementation.
 * @since 1.0.0
 */
class WP_Restaurant_Listings_Permalink_Settings {
	/**
	 * The single instance of the class.
	 *
	 * @var self
	 * @since 1.0.0
	 */
	private static $_instance = null;

	/**
	 * Permalink settings.
	 *
	 * @var array
	 * @since 1.0.0
	 */
	private $permalinks = array();

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
		$this->setup_fields();
		$this->settings_save();
		$this->permalinks = WP_Restaurant_Listings_Post_Types::get_permalink_structure();
	}

	public function setup_fields() {
		add_settings_field(
			'wprl_restaurant_base_slug',
			__( 'Restaurant base', 'wp-restaurant-listings' ),
			array( $this, 'restaurant_base_slug_input' ),
			'permalink',
			'optional'
		);
		add_settings_field(
			'wprl_restaurant_category_slug',
			__( 'Restaurant category base', 'wp-restaurant-listings' ),
			array( $this, 'restaurant_category_slug_input' ),
			'permalink',
			'optional'
		);
		add_settings_field(
			'wprl_restaurant_type_slug',
			__( 'Restaurant type base', 'wp-restaurant-listings' ),
			array( $this, 'restaurant_type_slug_input' ),
			'permalink',
			'optional'
		);
	}

	/**
	 * Show a slug input box for restaurant post type slug.
	 */
	public function restaurant_base_slug_input() {
		?>
		<input name="wprl_restaurant_base_slug" type="text" class="regular-text code" value="<?php echo esc_attr( $this->permalinks['restaurant_base'] ); ?>" placeholder="<?php echo esc_attr_x( 'restaurant', 'Restaurant permalink - resave permalinks after changing this', 'wp-restaurant-listings' ) ?>" />
		<?php
	}

	/**
	 * Show a slug input box for restaurant category slug.
	 */
	public function restaurant_category_slug_input() {
		?>
		<input name="wprl_restaurant_category_slug" type="text" class="regular-text code" value="<?php echo esc_attr( $this->permalinks['category_base'] ); ?>" placeholder="<?php echo esc_attr_x( 'restaurant-category', 'Restaurant category slug - resave permalinks after changing this', 'wp-restaurant-listings' ) ?>" />
		<?php
	}

	/**
	 * Show a slug input box for restaurant type slug.
	 */
	public function restaurant_type_slug_input() {
		?>
		<input name="wprl_restaurant_type_slug" type="text" class="regular-text code" value="<?php echo esc_attr( $this->permalinks['type_base'] ); ?>" placeholder="<?php echo esc_attr_x( 'restaurant-type', 'Restaurant type slug - resave permalinks after changing this', 'wp-restaurant-listings' ) ?>" />
		<?php
	}

	/**
	 * Save the settings.
	 */
	public function settings_save() {
		if ( ! is_admin() ) {
			return;
		}

		if ( isset( $_POST['permalink_structure'] ) ) {
			if ( function_exists( 'switch_to_locale' ) ) {
				switch_to_locale( get_locale() );
			}

			$permalinks                   = (array) get_option( 'wprl_permalinks', array() );
			$permalinks['restaurant_base']       = sanitize_title_with_dashes( $_POST['wprl_restaurant_base_slug'] );
			$permalinks['category_base']  = sanitize_title_with_dashes( $_POST['wprl_restaurant_category_slug'] );
			$permalinks['type_base']      = sanitize_title_with_dashes( $_POST['wprl_restaurant_type_slug'] );

			update_option( 'wprl_permalinks', $permalinks );

			if ( function_exists( 'restore_current_locale' ) ) {
				restore_current_locale();
			}
		}
	}
}

WP_Restaurant_Listings_Permalink_Settings::instance();
