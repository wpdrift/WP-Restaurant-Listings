<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Handles front admin page for WP Restaurant Listings.
 *
 * @package RestaurantListings
 * @since 1.0.0
 */
class WP_Restaurant_Listings_Admin {

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
		global $wp_version;

		include_once dirname( __FILE__ ) . '/class-wp-restaurant-listings-cpt.php';
		if ( version_compare( $wp_version, '4.7.0', '<' ) ) {
			include_once dirname( __FILE__ ) . '/class-wp-restaurant-listings-cpt-legacy.php';
			WP_Restaurant_Listings_CPT_Legacy::instance();
		} else {
			WP_Restaurant_Listings_CPT::instance();
		}

		include_once dirname( __FILE__ ) . '/class-wp-restaurant-listings-settings.php';
		include_once dirname( __FILE__ ) . '/class-wp-restaurant-listings-writepanels.php';
		include_once dirname( __FILE__ ) . '/class-wp-restaurant-listings-setup.php';

		$this->settings_page = WP_Restaurant_Listings_Settings::instance();

		add_action( 'current_screen', array( $this, 'conditional_includes' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 12 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
	}

	/**
	 * Include admin files conditionally.
	 */
	public function conditional_includes() {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		switch ( $screen->id ) {
			case 'options-permalink':
				include dirname( __FILE__ ) . '/class-wp-restaurant-listings-permalink-settings.php';
				break;
		}
	}

	/**
	 * Enqueues CSS and JS assets.
	 */
	public function admin_enqueue_scripts() {
		global $wp_scripts;

		$screen = get_current_screen();

		if ( in_array( $screen->id, apply_filters( 'restaurant_listings_admin_screen_ids', array( 'edit-restaurant_listings', 'restaurant_listings', 'restaurant_listings_page_restaurant-listings-settings', 'restaurant_listings_page_restaurant-listings-addons' ) ) ) ) {
			$jquery_version = isset( $wp_scripts->registered['jquery-ui-core']->ver ) ? $wp_scripts->registered['jquery-ui-core']->ver : '1.9.2';

			wp_enqueue_style( 'jquery-ui-style', '//code.jquery.com/ui/' . $jquery_version . '/themes/smoothness/jquery-ui.css', array(), $jquery_version );
			wp_enqueue_style( 'restaurant_listings_admin_css', RESTAURANT_LISTING_PLUGIN_URL . '/assets/css/admin.css', array(), RESTAURANT_LISTING_VERSION );
			wp_register_script( 'jquery-tiptip', RESTAURANT_LISTING_PLUGIN_URL. '/assets/js/jquery-tiptip/jquery.tipTip.min.js', array( 'jquery' ), RESTAURANT_LISTING_VERSION, true );
			wp_enqueue_script( 'restaurant_listings_admin_js', RESTAURANT_LISTING_PLUGIN_URL. '/assets/js/admin.js', array( 'jquery', 'jquery-tiptip' ), RESTAURANT_LISTING_VERSION, true );

			wp_localize_script( 'restaurant_listings_admin_js', 'restaurant_listings_admin', array(
				/* translators: jQuery date format, see http://api.jqueryui.com/datepicker/#utility-formatDate */
				'date_format' => _x( 'yy-mm-dd', 'Date format for jQuery datepicker.', 'wp-restaurant-listings' ),
				'time_format' => str_replace( '\\', '\\\\', get_option( 'time_format' ) ),
				'i18n_closed' => __( 'Closed','wp-restaurant-listings' ),
			) );
		}

		wp_enqueue_style( 'restaurant_listings_admin_menu_css', RESTAURANT_LISTING_PLUGIN_URL . '/assets/css/menu.css', array(), RESTAURANT_LISTING_VERSION );
	}

	/**
	 * Adds pages to admin menu.
	 */
	public function admin_menu() {
		add_submenu_page( 'edit.php?post_type=restaurant_listings', __( 'Settings', 'wp-restaurant-listings' ), __( 'Settings', 'wp-restaurant-listings' ), 'manage_options', 'restaurant-listings-settings', array( $this->settings_page, 'output' ) );
	}
}

WP_Restaurant_Listings_Admin::instance();
