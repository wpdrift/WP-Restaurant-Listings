<?php
/**
 * WPRL setup
 *
 * @package  RestaurantListings
 * @since    1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main WPRL Class.
 *
 * @class WP_Restaurant_Listings
 */
class WP_Restaurant_Listings {
	/**
	 * WPRL version.
	 *
	 * @var string
	 */
	public $version = '1.0.2';

	/**
	 * The single instance of the class.
	 *
	 * @var self
	 * @since  1.0.0
	 */
	private static $_instance = null;

	/**
	 * Main WPRL Instance.
	 *
	 * Ensures only one instance of WPRL is loaded or can be loaded.
	 *
	 * @since  1.0.0
	 * @static
	 * @see wprl()
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
		$this->define_constants();
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Define WPRL Constants.
	 */
	private function define_constants() {
		define( 'WPRL_PLUGIN_VERSION', $this->version );
		define( 'RESTAURANT_LISTING_VERSION', $this->version );
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 */
	public function includes() {
		include_once RESTAURANT_LISTING_PLUGIN_DIR . '/includes/class-wp-restaurant-listings-install.php';
		include_once RESTAURANT_LISTING_PLUGIN_DIR . '/includes/class-wp-restaurant-listings-post-types.php';
		include_once RESTAURANT_LISTING_PLUGIN_DIR . '/includes/class-wp-restaurant-listings-ajax.php';
		include_once RESTAURANT_LISTING_PLUGIN_DIR . '/includes/class-wp-restaurant-listings-shortcodes.php';
		include_once RESTAURANT_LISTING_PLUGIN_DIR . '/includes/class-wp-restaurant-listings-api.php';
		include_once RESTAURANT_LISTING_PLUGIN_DIR . '/includes/class-wp-restaurant-listings-forms.php';
		include_once RESTAURANT_LISTING_PLUGIN_DIR . '/includes/class-wp-restaurant-listings-geocode.php';
		include_once RESTAURANT_LISTING_PLUGIN_DIR . '/includes/class-wp-restaurant-listings-cache-helper.php';
		include_once RESTAURANT_LISTING_PLUGIN_DIR . '/includes/class-wp-restaurant-listings-template-loader.php';
		include_once RESTAURANT_LISTING_PLUGIN_DIR . '/includes/class-wp-restaurant-listings-comments.php';

		if ( is_admin() ) {
			include_once RESTAURANT_LISTING_PLUGIN_DIR . '/includes/admin/class-wp-restaurant-listings-admin.php';
			include_once RESTAURANT_LISTING_PLUGIN_DIR . '/includes/admin/class-wp-restaurant-listings-meta-box-gallery.php';
		}

		// Load 3rd party customizations.
		include_once RESTAURANT_LISTING_PLUGIN_DIR . '/includes/3rd-party/3rd-party.php';
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		// Init classes.
		$this->forms      = WP_Restaurant_Listings_Forms::instance();
		$this->post_types = WP_Restaurant_Listings_Post_Types::instance();

		// Schedule cron restaurants.
		self::maybe_schedule_cron_restaurants();

		// Activation - works with symlinks.
		register_activation_hook( basename( dirname( WPRL_PLUGIN_FILE ) ) . '/' . basename( WPRL_PLUGIN_FILE ), array( $this, 'activate' ) );

		// Switch theme.
		add_action( 'after_switch_theme', array( 'WP_Restaurant_Listings_Ajax', 'add_endpoint' ), 10 );
		add_action( 'after_switch_theme', array( $this->post_types, 'register_post_types' ), 11 );
		add_action( 'after_switch_theme', 'flush_rewrite_rules', 15 );

		// Actions.
		add_action( 'after_setup_theme', array( $this, 'load_plugin_textdomain' ) );
		add_action( 'after_setup_theme', array( $this, 'include_template_functions' ), 11 );
		add_action( 'widgets_init', array( $this, 'widgets_init' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
		add_action( 'wp_loaded', array( $this, 'register_scripts' ) );
		add_action( 'admin_init', array( $this, 'updater' ) );
		add_action( 'wp_logout', array( $this, 'cleanup_restaurant_posting_cookies' ) );

		// Defaults for WPRL core actions.
		add_action( 'wprl_notify_new_user', 'wp_restaurant_listings_notify_new_user', 10, 2 );
	}

	/**
	 * Performs plugin activation steps.
	 */
	public function activate() {
		WP_Restaurant_Listings_Ajax::add_endpoint();
		$this->post_types->register_post_types();
		WP_Restaurant_Listings_Install::install();
		flush_rewrite_rules();
	}

	/**
	 * Handles tasks after plugin is updated.
	 */
	public function updater() {
		if ( version_compare( RESTAURANT_LISTING_VERSION, get_option( 'wp_restaurant_listings_version' ), '>' ) ) {
			WP_Restaurant_Listings_Install::install();
			flush_rewrite_rules();
		}
	}

	/**
	 * Loads textdomain for plugin.
	 */
	public function load_plugin_textdomain() {
		load_textdomain( 'wp-restaurant-listings', WP_LANG_DIR . '/wp-restaurant-listings/wp-restaurant-listings-' . apply_filters( 'plugin_locale', get_locale(), 'wp-restaurant-listings' ) . '.mo' );
		load_plugin_textdomain( 'wp-restaurant-listings', false, dirname( plugin_basename( WPRL_PLUGIN_FILE ) ) . '/languages/' );
	}

	/**
	 * Loads plugin's core helper template functions.
	 */
	public function include_template_functions() {
		include_once RESTAURANT_LISTING_PLUGIN_DIR . '/wp-restaurant-listings-functions.php';
		include_once RESTAURANT_LISTING_PLUGIN_DIR . '/wp-restaurant-listings-template.php';
	}

	/**
	 * Loads plugin's widgets.
	 */
	public function widgets_init() {
		include_once RESTAURANT_LISTING_PLUGIN_DIR . '/includes/class-wp-restaurant-listings-widget.php';
		include_once RESTAURANT_LISTING_PLUGIN_DIR . '/includes/widgets/class-wp-restaurant-listings-widget-recent-restaurants.php';
		include_once RESTAURANT_LISTING_PLUGIN_DIR . '/includes/widgets/class-wp-restaurant-listings-widget-featured-restaurants.php';
		include_once RESTAURANT_LISTING_PLUGIN_DIR . '/includes/widgets/class-wp-restaurant-listings-widget-restaurant-hours.php';
		include_once RESTAURANT_LISTING_PLUGIN_DIR . '/includes/widgets/class-wp-restaurant-listings-widget-restaurant-map.php';
		include_once RESTAURANT_LISTING_PLUGIN_DIR . '/includes/widgets/class-wp-restaurant-listings-widget-restaurant-gallery.php';
	}

	/**
	 * Schedule cron restaurants for WPRL events.
	 */
	public static function maybe_schedule_cron_restaurants() {

		if ( ! wp_next_scheduled( 'restaurant_listings_delete_old_previews' ) ) {
			wp_schedule_event( time(), 'daily', 'restaurant_listings_delete_old_previews' );
		}
	}

	/**
	 * Cleanup restaurant posting cookies.
	 */
	public function cleanup_restaurant_posting_cookies() {
		if ( isset( $_COOKIE['wp-restaurant-listings-submitting-restaurant-id'] ) ) {
			setcookie( 'wp-restaurant-listings-submitting-restaurant-id', '', 0, COOKIEPATH, COOKIE_DOMAIN, false );
		}
		if ( isset( $_COOKIE['wp-restaurant-listings-submitting-restaurant-key'] ) ) {
			setcookie( 'wp-restaurant-listings-submitting-restaurant-key', '', 0, COOKIEPATH, COOKIE_DOMAIN, false );
		}
	}

	/**
	 * Registers and enqueues scripts and CSS.
	 */
	public function frontend_scripts() {
		global $post;

		$ajax_url         = WP_Restaurant_Listings_Ajax::get_endpoint();
		$ajax_filter_deps = array( 'jquery', 'jquery-deserialize' );
		$ajax_data        = array(
			'ajax_url'                => $ajax_url,
			'is_rtl'                  => is_rtl() ? 1 : 0,
			'i18n_load_prev_listings' => __( 'Load previous listings', 'wp-restaurant-listings' ),
		);

		/**
		 * Retrieves the current language for use when caching requests.
		 *
		 * @since 1.0.0
		 *
		 * @param string|null $lang
		 */
		$ajax_data['lang'] = apply_filters( 'wprl_lang', null );

		if ( apply_filters( 'restaurant_listings_select2_enabled', true ) ) {
			$ajax_filter_deps[] = 'select2';

			wp_register_script( 'select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.9/js/select2.min.js', array( 'jquery' ), '4.0.9', true );
			wp_register_script( 'wp-restaurant-listings-term-multiselect', RESTAURANT_LISTING_PLUGIN_URL . '/assets/js/term-multiselect.min.js', array( 'jquery', 'select2' ), RESTAURANT_LISTING_VERSION, true );
			wp_register_script( 'wp-restaurant-listings-multiselect', RESTAURANT_LISTING_PLUGIN_URL . '/assets/js/multiselect.min.js', array( 'jquery', 'select2' ), RESTAURANT_LISTING_VERSION, true );

			wp_localize_script(
				'select2',
				'restaurant_listings_select2_multiselect_args',
				apply_filters(
					'restaurant_listings_select2_multiselect_args',
					array(
						'multiple' => true,
					)
				)
			);

			wp_enqueue_style( 'select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.9/css/select2.min.css', array(), '4.0.9' );
		}

		if ( apply_filters( 'restaurant_listings_ajax_file_upload_enabled', true ) ) {
			wp_register_script( 'jquery-iframe-transport', RESTAURANT_LISTING_PLUGIN_URL . '/assets/js/jquery-fileupload/jquery.iframe-transport.js', array( 'jquery' ), '1.8.3', true );
			wp_register_script( 'jquery-fileupload', RESTAURANT_LISTING_PLUGIN_URL . '/assets/js/jquery-fileupload/jquery.fileupload.js', array( 'jquery', 'jquery-iframe-transport', 'jquery-ui-widget' ), '9.11.2', true );
			wp_register_script( 'wp-restaurant-listings-ajax-file-upload', RESTAURANT_LISTING_PLUGIN_URL . '/assets/js/ajax-file-upload.min.js', array( 'jquery', 'jquery-fileupload' ), RESTAURANT_LISTING_VERSION, true );

			ob_start();
			get_restaurant_listings_template(
				'form-fields/uploaded-file-html.php',
				array(
					'name'      => '',
					'value'     => '',
					'extension' => 'jpg',
				)
			);
			$js_field_html_img = ob_get_clean();

			ob_start();
			get_restaurant_listings_template(
				'form-fields/uploaded-file-html.php',
				array(
					'name'      => '',
					'value'     => '',
					'extension' => 'zip',
				)
			);
			$js_field_html = ob_get_clean();

			wp_localize_script(
				'wp-restaurant-listings-ajax-file-upload',
				'restaurant_listings_ajax_file_upload',
				array(
					'ajax_url'               => $ajax_url,
					'js_field_html_img'      => esc_js( str_replace( "\n", '', $js_field_html_img ) ),
					'js_field_html'          => esc_js( str_replace( "\n", '', $js_field_html ) ),
					'i18n_invalid_file_type' => __( 'Invalid file type. Accepted types:', 'wp-restaurant-listings' ),
				)
			);
		}

		// Slick.
		wp_register_style( 'slick', RESTAURANT_LISTING_PLUGIN_URL . '/assets/css/slick.css', array(), '1.8.0' );
		wp_register_style( 'slick-theme', RESTAURANT_LISTING_PLUGIN_URL . '/assets/css/slick-theme.css', array(), '1.8.0' );
		wp_register_script( 'slick', RESTAURANT_LISTING_PLUGIN_URL . '/assets/js/slick.min.js', array( 'jquery' ), '1.8.0' );

		// Drop.
		wp_register_script( 'tether', RESTAURANT_LISTING_PLUGIN_URL . '/assets/js/tether.min.js', array(), '1.4.0' );
		wp_register_script( 'drop', RESTAURANT_LISTING_PLUGIN_URL . '/assets/js/drop.min.js', array(), '1.2.2' );

		// jQuery Deserialize.
		wp_register_script( 'jquery-deserialize', RESTAURANT_LISTING_PLUGIN_URL . '/assets/js/jquery-deserialize/jquery.deserialize.js', array( 'jquery' ), '1.2.1', true );

		// Photoswipe.
		wp_register_style( 'photoswipe', RESTAURANT_LISTING_PLUGIN_URL . '/assets/css/photoswipe/photoswipe.css', array(), '4.1.1' );
		wp_register_style( 'default-skin', RESTAURANT_LISTING_PLUGIN_URL . '/assets/css/photoswipe/default-skin/default-skin.css', array(), '4.1.1' );
		wp_register_script( 'photoswipe', RESTAURANT_LISTING_PLUGIN_URL . '/assets/js/photoswipe/photoswipe.min.js', array(), '4.1.1' );
		wp_register_script( 'photoswipe-ui-default', RESTAURANT_LISTING_PLUGIN_URL . '/assets/js/photoswipe/photoswipe-ui-default.min.js', array(), '4.1.1' );

		// Store locator.
		wp_register_style( 'mapbox-gl', 'https://api.mapbox.com/mapbox-gl-js/v0.39.1/mapbox-gl.css' );
		wp_register_script( 'mapbox-gl', 'https://api.mapbox.com/mapbox-gl-js/v0.39.1/mapbox-gl.js' );
		wp_register_style( 'mapbox-gl-geocoder', 'https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v2.0.1/mapbox-gl-geocoder.css' );
		wp_register_script( 'mapbox-gl-geocoder', 'https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v2.0.1/mapbox-gl-geocoder.js' );

		wp_register_script( 'wp-restaurant-listings-ajax-filters', RESTAURANT_LISTING_PLUGIN_URL . '/assets/js/ajax-filters.min.js', $ajax_filter_deps, RESTAURANT_LISTING_VERSION, true );
		wp_register_script( 'wp-restaurant-listings-restaurant-dashboard', RESTAURANT_LISTING_PLUGIN_URL . '/assets/js/restaurant-dashboard.min.js', array( 'jquery' ), RESTAURANT_LISTING_VERSION, true );
		wp_register_script( 'wp-restaurant-listings-restaurant-submission', RESTAURANT_LISTING_PLUGIN_URL . '/assets/js/restaurant-submission.min.js', array( 'jquery' ), RESTAURANT_LISTING_VERSION, true );

		wp_register_script( 'wp-restaurant-listings-main', RESTAURANT_LISTING_PLUGIN_URL . '/assets/js/wp-restaurant-listings.min.js', array( 'jquery' ), RESTAURANT_LISTING_VERSION );

		wp_localize_script( 'wp-restaurant-listings-ajax-filters', 'restaurant_listings_ajax_filters', $ajax_data );
		wp_localize_script(
			'wp-restaurant-listings-restaurant-dashboard',
			'restaurant_listings_restaurant_dashboard',
			array(
				'i18n_confirm_delete' => __( 'Are you sure you want to delete this listings?', 'wp-restaurant-listings' ),
			)
		);

		wp_localize_script(
			'wp-restaurant-listings-main',
			'restaurant_listings_vars',
			array(
				'access_token'              => get_option( 'restaurant_listings_mapbox_access_token' ),
				'ajax_url'                  => admin_url( 'admin-ajax.php' ),
				'infowindowTemplatePath'    => RESTAURANT_LISTING_PLUGIN_URL . '/templates/storelocator/infowindow-description.html',
				'listTemplatePath'          => RESTAURANT_LISTING_PLUGIN_URL . '/templates/storelocator/location-list-description.html',
				'KMLinfowindowTemplatePath' => RESTAURANT_LISTING_PLUGIN_URL . '/templates/storelocator/kml-infowindow-description.html',
				'KMLlistTemplatePath'       => RESTAURANT_LISTING_PLUGIN_URL . '/templates/storelocator/location-list-description.html',
				'l10n'                      => array(
					'close' => __( 'Close', 'wp-restaurant-listings' ),
				),
			)
		);

		if ( is_singular( 'restaurant_listings' ) ) {
			wp_enqueue_style( 'photoswipe' );
			wp_enqueue_style( 'default-skin' );
			wp_enqueue_script( 'photoswipe' );
			wp_enqueue_script( 'photoswipe-ui-default' );
			wp_enqueue_style( 'slick' );
			wp_enqueue_style( 'slick-theme' );
			wp_enqueue_script( 'slick' );
			wp_enqueue_script( 'tether' );
			wp_enqueue_script( 'drop' );
		}

		wp_enqueue_style( 'wp-restaurant-listings-frontend', RESTAURANT_LISTING_PLUGIN_URL . '/assets/css/frontend.css', array(), RESTAURANT_LISTING_VERSION );

		if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'submit_restaurant_form' ) ) {
			wp_enqueue_style( 'wp-restaurant-listings-restaurant-submission', RESTAURANT_LISTING_PLUGIN_URL . '/assets/css/restaurant-submission.css', array(), RESTAURANT_LISTING_VERSION );
		}

		wp_enqueue_script( 'wp-restaurant-listings-main' );
	}

	/**
	 * Register scripts and styles.
	 */
	public function register_scripts() {
		wp_register_script( 'jquey-timepicker', RESTAURANT_LISTING_PLUGIN_URL . '/assets/js/jquery-timepicker/jquery.timepicker.min.js', array( 'jquery' ) );
		wp_register_style( 'jquery-timepicker', RESTAURANT_LISTING_PLUGIN_URL . '/assets/js/jquery-timepicker/jquery.timepicker.css' );
	}
}
