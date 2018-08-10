<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Featured Restaurants widget.
 *
 * @package RestaurantListings
 * @since 1.0.0
 */
class WP_Restaurant_Listings_Widget_Featured_Restaurants extends WP_Restaurant_Listings_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wp_post_types;

		$this->widget_cssclass    = 'restaurant_listings widget_featured_restaurants';
		$this->widget_description = __( 'Display a list of featured listings on your site.', 'wp-restaurant-listings' );
		$this->widget_id          = 'widget_featured_restaurants';
		$this->widget_name        = sprintf( __( 'Featured %s', 'wp-restaurant-listings' ), $wp_post_types['restaurant_listings']->labels->name );
		$this->settings           = array(
			'title' => array(
				'type'  => 'text',
				'std'   => sprintf( __( 'Featured %s', 'wp-restaurant-listings' ), $wp_post_types['restaurant_listings']->labels->name ),
				'label' => __( 'Title', 'wp-restaurant-listings' ),
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

		$title_instance = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$number         = isset( $instance['number'] ) ? absint( $instance['number'] ) : '';
		$title          = apply_filters( 'widget_title', $title_instance, $instance, $this->id_base );
		$restaurants    = get_restaurant_listings( array(
			'posts_per_page' => $number,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'featured'       => true,
		) );

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

		<?php
		endif;

		wp_reset_postdata();

		$content = ob_get_clean();

		echo $content;

		$this->cache_widget( $args, $content );
	}
}

register_widget( 'WP_Restaurant_Listings_Widget_Featured_Restaurants' );
