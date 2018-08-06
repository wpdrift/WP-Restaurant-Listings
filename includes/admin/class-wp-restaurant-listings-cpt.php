<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Handles actions and filters specific to the custom post type for Restaurant Listings.
 *
 * @package RestaurantListings
 * @since 1.0.0
 */
class WP_Restaurant_Listings_CPT {

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
		add_filter( 'enter_title_here', array( $this, 'enter_title_here' ), 1, 2 );
		add_filter( 'manage_edit-restaurant_listings_columns', array( $this, 'columns' ) );
		add_filter( 'list_table_primary_column', array( $this, 'primary_column' ), 10, 2 );
		add_filter( 'post_row_actions', array( $this, 'row_actions' ) );
		add_action( 'manage_restaurant_listings_posts_custom_column', array( $this, 'custom_columns' ), 2 );
		add_filter( 'manage_edit-restaurant_listings_sortable_columns', array( $this, 'sortable_columns' ) );
		add_filter( 'request', array( $this, 'sort_columns' ) );
		add_action( 'parse_query', array( $this, 'search_meta' ) );
		add_filter( 'get_search_query', array( $this, 'search_meta_label' ) );
		add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
		add_action( 'bulk_actions-edit-restaurant_listings', array( $this, 'add_bulk_actions' ) );
		add_action( 'handle_bulk_actions-edit-restaurant_listings', array( $this, 'do_bulk_actions' ), 10, 3 );
		add_action( 'admin_init', array( $this, 'approve_restaurant' ) );
		add_action( 'admin_notices', array( $this, 'action_notices' ) );

		if ( get_option( 'restaurant_listings_enable_categories' ) ) {
			add_action( "restrict_manage_posts", array( $this, "restaurants_by_category" ) );
		}

		foreach ( array( 'post', 'post-new' ) as $hook ) {
			add_action( "admin_footer-{$hook}.php", array( $this,'extend_submitdiv_post_status' ) );
		}
	}

	/**
	 * Returns the list of bulk actions that can be performed on restaurant listings.
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions_handled = array();
		$actions_handled['approve_restaurants'] = array(
			'label' => __( 'Approve %s', 'wp-restaurant-listings' ),
			'notice' => __( '%s approved', 'wp-restaurant-listings' ),
			'handler' => array( $this, 'bulk_action_handle_approve_restaurant' ),
		);

		/**
		 * Filters the bulk actions that can be applied to restaurant listings.
		 *
		 * @since 1.0.0
		 *
		 * @param array $actions_handled {
		 *     Bulk actions that can be handled, indexed by a unique key name (approve_restaurants, expire_restaurants, etc). Handlers
		 *     are responsible for checking abilities (`current_user_can( 'manage_restaurant_listings', $post_id )`) before
		 *     performing action.
		 *
		 *     @type string   $label   Label for the bulk actions dropdown. Passed through sprintf with label name of restaurant listings post type.
		 *     @type string   $notice  Success notice shown after performing the action. Passed through sprintf with title(s) of affected restaurant listings.
		 *     @type callback $handler Callable handler for performing action. Passed one argument (int $post_id) and should return true on success and false on failure.
		 * }
		 */
		return apply_filters( 'wprl_restaurant_listings_bulk_actions', $actions_handled );
	}

	/**
	 * Adds bulk actions to drop downs on Restaurant Listings admin page.
	 *
	 * @param array $bulk_actions
	 * @return array
	 */
	public function add_bulk_actions( $bulk_actions ) {
		global $wp_post_types;

		foreach ( $this->get_bulk_actions() as $key => $bulk_action ) {
			if ( isset( $bulk_action['label'] ) ) {
				$bulk_actions[ $key ] = sprintf( $bulk_action['label'], $wp_post_types['restaurant_listings']->labels->name );
			}
		}
		return $bulk_actions;
	}

	/**
	 * Performs bulk actions on Restaurant Listings admin page.
	 *
	 * @since 1.0.0
	 *
	 * @param string $redirect_url The redirect URL.
	 * @param string $action       The action being taken.
	 * @param array  $post_ids     The posts to take the action on.
	 */
	public function do_bulk_actions( $redirect_url, $action, $post_ids ) {
		$actions_handled = $this->get_bulk_actions();
		if ( isset ( $actions_handled[ $action ] ) && isset ( $actions_handled[ $action ]['handler'] ) ) {
			$handled_restaurants = array();
			if ( ! empty( $post_ids ) ) {
				foreach ( $post_ids as $post_id ) {
					if ( 'restaurant_listings' === get_post_type( $post_id )
					     && call_user_func( $actions_handled[ $action ]['handler'], $post_id ) ) {
						$handled_restaurants[] = $post_id;
					}
				}
				wp_redirect( add_query_arg( 'handled_restaurants', $handled_restaurants, add_query_arg( 'action_performed', $action, $redirect_url ) ) );
				exit;
			}
		}
	}

	/**
	 * Performs bulk action to approve a single restaurant listings.
	 *
	 * @param $post_id
	 *
	 * @return bool
	 */
	public function bulk_action_handle_approve_restaurant( $post_id ) {
		$restaurant_data = array(
			'ID'          => $post_id,
			'post_status' => 'publish',
		);
		if ( in_array( get_post_status( $post_id ), array( 'pending', 'pending_payment' ) )
		     && current_user_can( 'publish_post', $post_id )
		     && wp_update_post( $restaurant_data )
		) {
			return true;
		}
		return false;
	}

	/**
	 * Approves a single restaurant.
	 */
	public function approve_restaurant() {
		if ( ! empty( $_GET['approve_restaurant'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'approve_restaurant' ) && current_user_can( 'publish_post', $_GET['approve_restaurant'] ) ) {
			$post_id = absint( $_GET['approve_restaurant'] );
			$restaurant_data = array(
				'ID'          => $post_id,
				'post_status' => 'publish'
			);
			wp_update_post( $restaurant_data );
			wp_redirect( remove_query_arg( 'approve_restaurant', add_query_arg( 'handled_restaurants', $post_id, add_query_arg( 'action_performed', 'approve_restaurants', admin_url( 'edit.php?post_type=restaurant_listings' ) ) ) ) );
			exit;
		}
	}

	/**
	 * Shows a notice if we did a bulk action.
	 */
	public function action_notices() {
		global $post_type, $pagenow;

		$handled_restaurants = isset ( $_REQUEST['handled_restaurants'] ) ? $_REQUEST['handled_restaurants'] : false;
		$action = isset ( $_REQUEST['action_performed'] ) ? $_REQUEST['action_performed'] : false;
		$actions_handled = $this->get_bulk_actions();

		if ( $pagenow == 'edit.php'
			 && $post_type == 'restaurant_listings'
			 && $action
			 && ! empty( $handled_restaurants )
			 && isset ( $actions_handled[ $action ] )
			 && isset ( $actions_handled[ $action ]['notice'] )
		) {
			if ( is_array( $handled_restaurants ) ) {
				$handled_restaurants = array_map( 'absint', $handled_restaurants );
				$titles       = array();
				foreach ( $handled_restaurants as $restaurant_id ) {
					$titles[] = get_the_restaurant_title( $restaurant_id );
				}
				echo '<div class="updated"><p>' . sprintf( $actions_handled[ $action ]['notice'], '&quot;' . implode( '&quot;, &quot;', $titles ) . '&quot;' ) . '</p></div>';
			} else {
				echo '<div class="updated"><p>' . sprintf( $actions_handled[ $action ]['notice'], '&quot;' . get_the_restaurant_title( absint( $handled_restaurants ) ) . '&quot;' ) . '</p></div>';
			}
		}
	}

	/**
	 * Shows category dropdown.
	 */
	public function restaurants_by_category() {
		global $typenow, $wp_query;

	    if ( $typenow != 'restaurant_listings' || ! taxonomy_exists( 'restaurant_listings_category' ) ) {
	    	return;
	    }

	    include_once( RESTAURANT_LISTING_PLUGIN_DIR . '/includes/class-wp-restaurant-listings-category-walker.php' );

		$r                 = array();
		$r['pad_counts']   = 1;
		$r['hierarchical'] = 1;
		$r['hide_empty']   = 0;
		$r['show_count']   = 1;
		$r['selected']     = ( isset( $wp_query->query['restaurant_listings_category'] ) ) ? $wp_query->query['restaurant_listings_category'] : '';
		$r['menu_order']   = false;
		$terms             = get_terms( 'restaurant_listings_category', $r );
		$walker            = new WP_Restaurant_Listings_Category_Walker;

		if ( ! $terms ) {
			return;
		}

		$output  = "<select name='restaurant_listings_category' id='dropdown_restaurant_listings_category'>";
		$output .= '<option value="" ' . selected( isset( $_GET['restaurant_listings_category'] ) ? $_GET['restaurant_listings_category'] : '', '', false ) . '>' . __( 'Select category', 'wp-restaurant-listings' ) . '</option>';
		$output .= $walker->walk( $terms, 0, $r );
		$output .= "</select>";

		echo $output;
	}

	/**
	 * Filters page title placeholder text to show custom label.
	 *
	 * @param string      $text
	 * @param WP_Post|int $post
	 * @return string
	 */
	public function enter_title_here( $text, $post ) {
		if ( $post->post_type == 'restaurant_listings' )
			return __( 'Name', 'wp-restaurant-listings' );
		return $text;
	}

	/**
	 * Filters the post updated message array to add custom post type's messages.
	 *
	 * @param array $messages
	 * @return array
	 */
	public function post_updated_messages( $messages ) {
		global $post, $post_ID, $wp_post_types;

		$messages['restaurant_listings'] = array(
			0 => '',
			1 => sprintf( __( '%s updated. <a href="%s">View</a>', 'wp-restaurant-listings' ), $wp_post_types['restaurant_listings']->labels->singular_name, esc_url( get_permalink( $post_ID ) ) ),
			2 => __( 'Custom field updated.', 'wp-restaurant-listings' ),
			3 => __( 'Custom field deleted.', 'wp-restaurant-listings' ),
			4 => sprintf( __( '%s updated.', 'wp-restaurant-listings' ), $wp_post_types['restaurant_listings']->labels->singular_name ),
			5 => isset( $_GET['revision'] ) ? sprintf( __( '%s restored to revision from %s', 'wp-restaurant-listings' ), $wp_post_types['restaurant_listings']->labels->singular_name, wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => sprintf( __( '%s published. <a href="%s">View</a>', 'wp-restaurant-listings' ), $wp_post_types['restaurant_listings']->labels->singular_name, esc_url( get_permalink( $post_ID ) ) ),
			7 => sprintf( __( '%s saved.', 'wp-restaurant-listings' ), $wp_post_types['restaurant_listings']->labels->singular_name ),
			8 => sprintf( __( '%s submitted. <a target="_blank" href="%s">Preview</a>', 'wp-restaurant-listings' ), $wp_post_types['restaurant_listings']->labels->singular_name, esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
			9 => sprintf( __( '%s scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview</a>', 'wp-restaurant-listings' ), $wp_post_types['restaurant_listings']->labels->singular_name,
			  date_i18n( __( 'M j, Y @ G:i', 'wp-restaurant-listings' ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post_ID ) ) ),
			10 => sprintf( __( '%s draft updated. <a target="_blank" href="%s">Preview</a>', 'wp-restaurant-listings' ), $wp_post_types['restaurant_listings']->labels->singular_name, esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
		);

		return $messages;
	}

	/**
	 * Adds columns to admin listings of Restaurant Listings.
	 *
	 * @param array $columns
	 * @return array
	 */
	public function columns( $columns ) {
		if ( ! is_array( $columns ) ) {
			$columns = array();
		}

		unset( $columns['date'], $columns['author'] );


        $columns['title']                       = __( 'Name', 'wp-restaurant-listings' );
		$columns["restaurant_listings_type"]     = __( "Type", 'wp-restaurant-listings' );
		$columns["restaurant_location"]         = __( "Location", 'wp-restaurant-listings' );
		$columns['restaurant_status']           = '<span class="tips" data-tip="' . __( "Status", 'wp-restaurant-listings' ) . '">' . __( "Status", 'wp-restaurant-listings' ) . '</span>';
		$columns["restaurant_posted"]           = __( "Posted", 'wp-restaurant-listings' );
		$columns["restaurant_listings_category"] = __( "Categories", 'wp-restaurant-listings' );
		$columns['featured_restaurant']         = '<span class="tips" data-tip="' . __( "Featured?", 'wp-restaurant-listings' ) . '">' . __( "Featured?", 'wp-restaurant-listings' ) . '</span>';
		$columns['restaurant_actions']          = __( "Actions", 'wp-restaurant-listings' );

		if ( ! get_option( 'restaurant_listings_enable_categories' ) ) {
			unset( $columns["restaurant_listings_category"] );
		}

		if ( ! get_option( 'restaurant_listings_enable_types' ) ) {
			unset( $columns["restaurant_listings_type"] );
		}

		return $columns;
	}

	/**
	 * This is required to make column responsive since WP 4.3
	 *
	 * @access public
	 * @param string $column
	 * @param string $screen
	 * @return string
	 */
	public function primary_column( $column, $screen ) {
		if ( 'edit-restaurant_listings' === $screen ) {
			$column = 'restaurant_name';
		}
		return $column;
	}

	/**
	 * Removes all action links because WordPress add it to primary column.
	 * Note: Removing all actions also remove mobile "Show more details" toggle button.
	 * So the button need to be added manually in custom_columns callback for primary column.
	 *
	 * @access public
	 * @param array $actions
	 * @return array
	 */
	public function row_actions( $actions ) {
		if ( 'restaurant_listings' == get_post_type() ) {
			return array();
		}
		return $actions;
	}

	/**
	 * Displays the content for each custom column on the admin list for Restaurant Listings.
	 *
	 * @param mixed $column
	 */
	public function custom_columns( $column ) {
		global $post;

		switch ( $column ) {
			case "restaurant_listings_type" :
				$types = get_the_restaurant_types( $post );

				if ( $types && ! empty( $types ) ) {
					foreach ( $types as $type ) {
						echo '<span class="restaurant-type ' . $type->slug . '">' . $type->name . '</span>';
					}
				}
			break;
			case "restaurant_location" :
				the_restaurant_location( $post );
			break;
			case "restaurant_listings_category" :
				if ( ! $terms = get_the_term_list( $post->ID, $column, '', ', ', '' ) ) echo '<span class="na">&ndash;</span>'; else echo $terms;
			break;
			case "featured_restaurant" :
				if ( is_restaurant_featured( $post ) ) echo '&#10004;'; else echo '&ndash;';
			break;
			case "restaurant_posted" :
				echo '<strong>' . date_i18n( __( 'M j, Y', 'wp-restaurant-listings' ), strtotime( $post->post_date ) ) . '</strong><span>';
				echo ( empty( $post->post_author ) ? __( 'by a guest', 'wp-restaurant-listings' ) : sprintf( __( 'by %s', 'wp-restaurant-listings' ), '<a href="' . esc_url( add_query_arg( 'author', $post->post_author ) ) . '">' . get_the_author() . '</a>' ) ) . '</span>';
			break;
			case "restaurant_status" :
				echo '<span data-tip="' . esc_attr( get_the_restaurant_status( $post ) ) . '" class="tips status-' . esc_attr( $post->post_status ) . '">' . get_the_restaurant_status( $post ) . '</span>';
			break;
			case "restaurant_actions" :
				echo '<div class="actions">';
				$admin_actions = apply_filters( 'post_row_actions', array(), $post );

				if ( in_array( $post->post_status, array( 'pending', 'pending_payment' ) ) && current_user_can ( 'publish_post', $post->ID ) ) {
					$admin_actions['approve']   = array(
						'action'  => 'approve',
						'name'    => __( 'Approve', 'wp-restaurant-listings' ),
						'url'     =>  wp_nonce_url( add_query_arg( 'approve_restaurant', $post->ID ), 'approve_restaurant' )
					);
				}
				if ( $post->post_status !== 'trash' ) {
					if ( current_user_can( 'read_post', $post->ID ) ) {
						$admin_actions['view']   = array(
							'action'  => 'view',
							'name'    => __( 'View', 'wp-restaurant-listings' ),
							'url'     => get_permalink( $post->ID )
						);
					}
					if ( current_user_can( 'edit_post', $post->ID ) ) {
						$admin_actions['edit']   = array(
							'action'  => 'edit',
							'name'    => __( 'Edit', 'wp-restaurant-listings' ),
							'url'     => get_edit_post_link( $post->ID )
						);
					}
					if ( current_user_can( 'delete_post', $post->ID ) ) {
						$admin_actions['delete'] = array(
							'action'  => 'delete',
							'name'    => __( 'Delete', 'wp-restaurant-listings' ),
							'url'     => get_delete_post_link( $post->ID )
						);
					}
				}

				$admin_actions = apply_filters( 'restaurant_listings_admin_actions', $admin_actions, $post );

				foreach ( $admin_actions as $action ) {
					if ( is_array( $action ) ) {
						printf( '<a class="button button-icon tips icon-%1$s" href="%2$s" data-tip="%3$s">%4$s</a>', $action['action'], esc_url( $action['url'] ), esc_attr( $action['name'] ), esc_html( $action['name'] ) );
					} else {
						echo str_replace( 'class="', 'class="button ', $action );
					}
				}

				echo '</div>';

			break;
		}
	}

	/**
	 * Filters the list table sortable columns for the admin list of Restaurant Listings.
	 *
	 * @param mixed $columns
	 * @return array
	 */
	public function sortable_columns( $columns ) {
		$custom = array(
			'restaurant_posted'   => 'date',
			'restaurant_name' => 'title',
			'restaurant_location' => 'restaurant_location',
		);
		return wp_parse_args( $custom, $columns );
	}

	/**
	 * Sorts the admin listings of Restaurant Listings by updating the main query in the request.
	 *
	 * @param mixed $vars
	 * @return array
	 */
	public function sort_columns( $vars ) {
		if ( isset( $vars['orderby'] ) ) {
			if ( 'restaurant_location' === $vars['orderby'] ) {
				$vars = array_merge( $vars, array(
					'meta_key' 	=> '_restaurant_location',
					'orderby' 	=> 'meta_value'
				) );
			}
		}
		return $vars;
	}

	/**
	 * Searches custom fields as well as content.
	 *
	 * @param WP_Query $wp
	 */
	public function search_meta( $wp ) {
		global $pagenow, $wpdb;

		if ( 'edit.php' !== $pagenow || empty( $wp->query_vars['s'] ) || 'restaurant_listings' !== $wp->query_vars['post_type'] ) {
			return;
		}

		$post_ids = array_unique( array_merge(
			$wpdb->get_col(
				$wpdb->prepare( "
					SELECT posts.ID
					FROM {$wpdb->posts} posts
					INNER JOIN {$wpdb->postmeta} p1 ON posts.ID = p1.post_id
					WHERE p1.meta_value LIKE '%%%s%%'
					OR posts.post_title LIKE '%%%s%%'
					OR posts.post_content LIKE '%%%s%%'
					AND posts.post_type = 'restaurant_listings'
					",
					esc_sql( $wp->query_vars['s'] ),
					esc_sql( $wp->query_vars['s'] ),
					esc_sql( $wp->query_vars['s'] )
				)
			),
			array( 0 )
		) );

		// Adjust the query vars
		unset( $wp->query_vars['s'] );
		$wp->query_vars['restaurant_listings_search'] = true;
		$wp->query_vars['post__in'] = $post_ids;
	}

	/**
	 * Changes the label when searching meta.
	 *
	 * @param string $query
	 * @return string
	 */
	public function search_meta_label( $query ) {
		global $pagenow, $typenow;

		if ( 'edit.php' !== $pagenow || $typenow !== 'restaurant_listings' || ! get_query_var( 'restaurant_listings_search' ) ) {
			return $query;
		}

		return wp_unslash( sanitize_text_field( $_GET['s'] ) );
	}

	/**
	 * Adds post status to the "submitdiv" Meta Box and post type WP List Table screens. Based on https://gist.github.com/franz-josef-kaiser/2930190
	 */
	public function extend_submitdiv_post_status() {
		global $post, $post_type;

		// Abort if we're on the wrong post type, but only if we got a restriction
		if ( 'restaurant_listings' !== $post_type ) {
			return;
		}

		// Get all non-builtin post status and add them as <option>
		$options = $display = '';
		foreach ( get_restaurant_listings_post_statuses() as $status => $name ) {
			$selected = selected( $post->post_status, $status, false );

			// If we one of our custom post status is selected, remember it
			$selected AND $display = $name;

			// Build the options
			$options .= "<option{$selected} value='{$status}'>{$name}</option>";
		}
		?>
		<script type="text/javascript">
			jQuery( document ).ready( function($) {
				<?php if ( ! empty( $display ) ) : ?>
					jQuery( '#post-status-display' ).html( '<?php echo $display; ?>' );
				<?php endif; ?>

				var select = jQuery( '#post-status-select' ).find( 'select' );
				jQuery( select ).html( "<?php echo $options; ?>" );
			} );
		</script>
		<?php
	}
}
