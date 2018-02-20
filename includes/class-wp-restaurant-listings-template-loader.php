<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Template Loader
 *
 */
class WP_Restaurant_Listings_Template_Loader {

    public static $comment_template_loaded = false;

	/**
	 * Hook in methods.
	 */
	public static function init() {
		add_filter( 'template_include', array( __CLASS__, 'template_loader' ) );
		add_filter( 'comments_template', array( __CLASS__, 'comments_template_loader' ) );
	}

	/**
	 * Load a template.
	 *
	 * Handles template usage so that we can use our own templates instead of the themes.
	 *
	 * Templates are in the 'templates' folder. woocommerce looks for theme.
	 * overrides in /theme/woocommerce/ by default.
	 *
	 * For beginners, it also looks for a woocommerce.php template first. If the user adds.
	 * this to the theme (containing a woocommerce() inside) this will be used for all.
	 * woocommerce templates.
	 *
	 * @param mixed $template
	 * @return string
	 */
	public static function template_loader( $template ) {
		if ( is_embed() ) {
			return $template;
		}

		if ( $default_file = self::get_template_loader_default_file() ) {
			/**
			 * Filter hook to choose which files to find before WooCommerce does it's own logic.
			 *
			 * @since 1.0.0
			 * @var array
			 */
			$search_files = self::get_template_loader_files( $default_file );
			$template     = locate_template( $search_files );

			if ( ! $template ) {
				$template = RESTAURANT_LISTING_PLUGIN_DIR . '/templates/' . $default_file;
			}
		}

		return $template;
	}

	/**
	 * Get the default filename for a template.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	private static function get_template_loader_default_file() {
//		if ( is_singular( 'restaurant_listings' ) ) {
//			$default_file = 'single-restaurant_listings.php';
//		} else {
//			$default_file = '';
//		}
		return '';
	}

	/**
	 * Get an array of filenames to search for a given template.
	 *
	 * @since 1.0.0
	 * @param  string $default_file The default file name.
	 * @return string[]
	 */
	private static function get_template_loader_files( $default_file ) {
		$search_files   = apply_filters( 'restaurant_listings_template_loader_files', array(), $default_file );
		$search_files[] = 'restaurant_listings.php';

		$search_files[] = $default_file;
		$search_files[] = 'restaurant_listings/' . $default_file;

		return array_unique( $search_files );
	}

	/**
	 * Load comments template.
	 *
	 * @param mixed $template
	 * @return string
	 */
	public static function comments_template_loader( $template ) {
		if ( get_post_type() !== 'restaurant_listings' ) {
			return $template;
		}

		$check_dirs = array(
			trailingslashit( get_stylesheet_directory() ) . 'restaurant_listings/',
			trailingslashit( get_template_directory() ) . 'restaurant_listings/',
			trailingslashit( get_stylesheet_directory() ),
			trailingslashit( get_template_directory() ),
			trailingslashit( RESTAURANT_LISTING_PLUGIN_DIR ) . 'templates/',
		);

		foreach ( $check_dirs as $dir ) {
			if ( file_exists( trailingslashit( $dir ) . 'single-restaurant_listings-reviews.php' ) ) {
			    return trailingslashit( $dir ) . 'single-restaurant_listings-reviews.php';
			}
		}
	}
}

WP_Restaurant_Listings_Template_Loader::init();
