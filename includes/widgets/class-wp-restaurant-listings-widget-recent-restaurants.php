<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Recent Restaurants widget.
 *
 * @package RestaurantListings
 * @since 1.0.0
 */
class WP_Restaurant_Listings_Widget_Recent_Restaurants extends WP_Restaurant_Listings_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wp_post_types;

		$this->widget_cssclass    = 'restaurant_listings widget_recent_restaurants';
		$this->widget_description = __( 'Display a list of recent listings on your site, optionally matching a keyword and location.', 'wp-restaurant-listings' );
		$this->widget_id          = 'widget_recent_restaurants';
		$this->widget_name        = sprintf( __( 'Recent %s', 'wp-restaurant-listings' ), $wp_post_types['restaurant_listings']->labels->name );
		$this->settings           = array(
			'title' => array(
				'type'  => 'text',
				'std'   => sprintf( __( 'Recent %s', 'wp-restaurant-listings' ), $wp_post_types['restaurant_listings']->labels->name ),
				'label' => __( 'Title', 'wp-restaurant-listings' ),
			),
			'keyword' => array(
				'type'  => 'text',
				'std'   => '',
				'label' => __( 'Keyword', 'wp-restaurant-listings' ),
			),
			'location' => array(
				'type'  => 'text',
				'std'   => '',
				'label' => __( 'Location', 'wp-restaurant-listings' ),
			),
			'number' => array(
				'type'  => 'number',
				'step'  => 1,
				'min'   => 1,
				'max'   => '',
				'std'   => 10,
				'label' => __( 'Number of listings to show', 'wp-restaurant-listings' ),
			),
		);

        parent::__construct();
	}

	/**
	 * Echoes the widget content.
	 *
	 * @see WP_Widget
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		if ( $this->get_cached_widget( $args ) ) {
			return;
		}

		ob_start();

		extract( $args );

		$title  = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
		$number = absint( $instance['number'] );
		$restaurants   = get_restaurant_listings( array(
			'search_location'   => isset( $instance['location'] ) ? $instance['location'] : '',
			'search_keywords'   => isset( $instance['keyword'] ) ? $instance['keyword'] : '',
			'posts_per_page'    => $number,
			'orderby'           => 'date',
			'order'             => 'DESC',
		) );

		/**
		 * Runs before Recent Restaurants widget content.
		 *
		 * @since 1.0.1
		 *
		 * @param array    $args
		 * @param array    $instance
		 * @param WP_Query $restaurants
		 */
		do_action( 'restaurant_listings_recent_restaurants_widget_before', $args, $instance, $restaurants );

		if ( $restaurants->have_posts() ) : ?>

			<?php echo $before_widget; ?>

			<?php if ( $title ) { echo $before_title . $title . $after_title;} ?>

			<ul class="restaurant_listings">

				<?php while ( $restaurants->have_posts() ) : $restaurants->the_post(); ?>

					<?php get_restaurant_listings_template_part( 'content-widget', 'restaurant_listings' ); ?>

				<?php endwhile; ?>

			</ul>

			<?php echo $after_widget; ?>

		<?php else : ?>

			<?php get_restaurant_listings_template_part( 'content-widget', 'no-restaurants-found' ); ?>

		<?php endif;

		/**
		 * Runs after Recent Restaurants widget content.
		 *
		 * @since 1.29.1
		 *
		 * @param array    $args
		 * @param array    $instance
		 * @param WP_Query $restaurants
		 */
		do_action( 'restaurant_listings_recent_restaurants_widget_after', $args, $instance, $restaurants );

		wp_reset_postdata();

		$content = ob_get_clean();

		echo $content;

		$this->cache_widget( $args, $content );
	}
}

register_widget( 'WP_Restaurant_Listings_Widget_Recent_Restaurants' );
