<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the installation of the WP Restaurant Listings plugin.
 *
 * @package RestaurantListings
 * @since 1.0.0
 */
class WP_Restaurant_Listings_Install {

	/**
	 * Installs WP Restaurant Listings.
	 */
	public static function install() {
		global $wpdb;

		self::init_user_roles();
		self::default_terms();
		self::create_tables();

		// Redirect to setup screen for new installs
		if ( ! get_option( 'wp_restaurant_listings_version' ) ) {
			set_transient( '_restaurant_listings_activation_redirect', 1, HOUR_IN_SECONDS );
		}

		// Update featured posts ordering
		if ( version_compare( get_option( 'wp_restaurant_listings_version', RESTAURANT_LISTING_VERSION ), '1.22.0', '<' ) ) {
			$wpdb->query( "UPDATE {$wpdb->posts} p SET p.menu_order = 0 WHERE p.post_type='restaurant_listings';" );
			$wpdb->query( "UPDATE {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id SET p.menu_order = -1 WHERE pm.meta_key = '_featured' AND pm.meta_value='1' AND p.post_type='restaurant_listings';" );
		}

		// Update legacy options
		if ( false === get_option( 'restaurant_listings_submit_restaurant_form_page_id', false ) && get_option( 'restaurant_listings_submit_page_slug' ) ) {
			$page_id = get_page_by_path( get_option( 'restaurant_listings_submit_page_slug' ) )->ID;
			update_option( 'restaurant_listings_submit_restaurant_form_page_id', $page_id );
		}
		if ( false === get_option( 'restaurant_listings_restaurant_dashboard_page_id', false ) && get_option( 'restaurant_listings_restaurant_dashboard_page_slug' ) ) {
			$page_id = get_page_by_path( get_option( 'restaurant_listings_restaurant_dashboard_page_slug' ) )->ID;
			update_option( 'restaurant_listings_restaurant_dashboard_page_id', $page_id );
		}

		delete_transient( 'wp_restaurant_listings_addons_html' );
		update_option( 'wp_restaurant_listings_version', RESTAURANT_LISTING_VERSION );
	}

	/**
	 * Initializes user roles.
	 */
	private static function init_user_roles() {
		global $wp_roles;

		if ( class_exists( 'WP_Roles' ) && ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}

		if ( is_object( $wp_roles ) ) {
			add_role( 'owner', __( 'Owner', 'wp-restaurant-listings' ), array(
				'read'         => true,
				'edit_posts'   => false,
				'delete_posts' => false
			) );

			$capabilities = self::get_core_capabilities();

			foreach ( $capabilities as $cap_group ) {
				foreach ( $cap_group as $cap ) {
					$wp_roles->add_cap( 'administrator', $cap );
				}
			}
		}
	}

	/**
	 * Returns capabilities.
	 *
	 * @return array
	 */
	private static function get_core_capabilities() {
		return array(
			'core' => array(
				'manage_restaurant_listings'
			),
			'restaurant_listings' => array(
				"edit_restaurant_listings",
				"read_restaurant_listings",
				"delete_restaurant_listings",
				"edit_restaurant_listings",
				"edit_others_restaurant_listings",
				"publish_restaurant_listings",
				"read_private_restaurant_listings",
				"delete_restaurant_listings",
				"delete_private_restaurant_listings",
				"delete_published_restaurant_listings",
				"delete_others_restaurant_listings",
				"edit_private_restaurant_listings",
				"edit_published_restaurant_listings",
				"manage_restaurant_listings_terms",
				"edit_restaurant_listings_terms",
				"delete_restaurant_listings_terms",
				"assign_restaurant_listings_terms"
			)
		);
	}

	/**
	 * Returns the default Restaurant Listings terms.
	 */
	private static function default_terms() {
		if ( get_option( 'restaurant_listings_installed_terms' ) == 1 ) {
			return;
		}

		$taxonomies = array(
			'restaurant_listings_type' => array(
				'Full Time',
				'Part Time',
				'Temporary',
				'Freelance',
				'Internship'
			)
		);

		foreach ( $taxonomies as $taxonomy => $terms ) {
			foreach ( $terms as $term ) {
				if ( ! get_term_by( 'slug', sanitize_title( $term ), $taxonomy ) ) {
					wp_insert_term( $term, $taxonomy );
				}
			}
		}

		update_option( 'restaurant_listings_installed_terms', 1 );
	}

    /**
     * Create the tables
     */
    private static function create_tables() {
        global $wpdb;

        $wpdb->hide_errors();

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $collate = '';

        if ( $wpdb->has_cap( 'collation' ) ) {
            $collate = $wpdb->get_charset_collate();
        }

        $sql = "CREATE TABLE {$wpdb->prefix}restaurants_location (
			id BIGINT UNSIGNED NOT NULL auto_increment,
			post_id BIGINT UNSIGNED NOT NULL,
			lat float NOT NULL,
			lng float NOT NULL,
			PRIMARY KEY  (id),
            UNIQUE KEY post_id (post_id)
		) $collate;";

        dbDelta( $sql );
    }

}
