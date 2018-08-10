<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Handles the listings of Restaurant Listings meta fields.
 *
 * @package RestaurantListings
 * @since 1.0.0
 */
class WP_Restaurant_Listings_Writepanels {

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
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );


        // Save Restaurant Meta Boxes.
        add_action( 'restaurant_listings_save_restaurant_listing', 'WP_Restaurant_Listings_Meta_Box_Gallery::save', 20, 2 );

        add_action( 'save_post', array( $this, 'save_post' ), 1, 2 );
		add_action( 'restaurant_listings_save_restaurant_listing', array( $this, 'save_restaurant_listings_data' ), 20, 2 );
	}

	/**
	 * Returns configuration for custom fields on Restaurant Listings posts.
	 *
	 * @return array
	 */
	public function restaurant_listings_fields() {
		global $post;

		$current_user = wp_get_current_user();
        $currency_symbol = get_option('restaurant_listings_currency');

		$fields = array(
			'_restaurant_location' => array(
				'label' => __( 'Address', 'wp-restaurant-listings' ),
				'placeholder' => __( 'e.g. "44-46 Morningside Road, Edinburgh, Scotland, EH10 4BF"', 'wp-restaurant-listings' ),
				'description' => __( 'Leave this blank if the location is not important.', 'wp-restaurant-listings' ),
				'priority'    => 1
			),
			'_restaurant_price_range' => array(
                'label' => __( 'Price Range', 'wp-restaurant-listings' ),
                'type'  => 'select',
                'options'     => array(
                    1 => str_repeat( $currency_symbol, 1 ),
                    2 => str_repeat( $currency_symbol, 2 ),
                    3 => str_repeat( $currency_symbol, 3 ),
                    4 => str_repeat( $currency_symbol, 4 ),
                ),
                'description' => __( 'Leave this blank if the location is not important.', 'wp-restaurant-listings' ),
                'priority'    => 2
            ),
			'_application' => array(
				'label'       => __( 'Restaurant Email', 'wp-restaurant-listings' ),
				'placeholder' => __( 'URL or email which applicants use to apply', 'wp-restaurant-listings' ),
				'description' => __( 'This field is required for the "application" area to appear beneath the listings.', 'wp-restaurant-listings' ),
				'value'       => metadata_exists( 'post', $post->ID, '_application' ) ? get_post_meta( $post->ID, '_application', true ) : $current_user->user_email,
				'priority'    => 3
			),
			'_restaurant_name' => array(
				'label'       => __( 'Restaurant Name', 'wp-restaurant-listings' ),
				'placeholder' => '',
				'priority'    => 4
			),
            '_restaurant_phone' => array(
				'label'       => __( 'Restaurant Phone', 'wp-restaurant-listings' ),
				'placeholder' => '',
				'priority'    => 5
			),
			'_restaurant_website' => array(
				'label'       => __( 'Restaurant Website', 'wp-restaurant-listings' ),
				'placeholder' => '',
				'priority'    => 6
			),
			'_restaurant_tagline' => array(
				'label'       => __( 'Restaurant Tagline', 'wp-restaurant-listings' ),
				'placeholder' => __( 'Brief description about the restaurant', 'wp-restaurant-listings' ),
				'priority'    => 7
			),
			'_restaurant_twitter' => array(
				'label'       => __( 'Restaurant Twitter', 'wp-restaurant-listings' ),
				'placeholder' => '@yourcompany',
				'priority'    => 8
			),
            '_restaurant_menu' => array(
                'label'       => __( 'Restaurant Menu', 'wp-restaurant-listings' ),
                'placeholder' => __( 'URL to the restaurant menu', 'wp-restaurant-listings' ),
                'type'        => 'menu_file',
                'multiple'    => true,
                'priority'    => 9
            ),
			'_restaurant_video' => array(
				'label'       => __( 'Restaurant Video', 'wp-restaurant-listings' ),
				'placeholder' => __( 'URL to the restaurant video', 'wp-restaurant-listings' ),
				'type'        => 'file',
				'priority'    => 10
			)
		);
		if ( $current_user->has_cap( 'manage_restaurant_listings' ) ) {
			$fields['_featured'] = array(
				'label'       => __( 'Featured Listings', 'wp-restaurant-listings' ),
				'type'        => 'checkbox',
				'description' => __( 'Featured listings will be sticky during searches, and can be styled differently.', 'wp-restaurant-listings' ),
				'priority'    => 11
			);
		}
		if ( $current_user->has_cap( 'edit_others_restaurant_listings' ) ) {
			$fields['_restaurant_author'] = array(
				'label'    => __( 'Posted by', 'wp-restaurant-listings' ),
				'type'     => 'author',
				'priority' => 12
			);
		}

		/**
		 * Filters restaurant listings data fields for WP Admin post editor.
		 *
		 * @since 1.0.0
		 *
		 * @param array $fields
		 * @param int   $post_id
		 */
		$fields = apply_filters( 'restaurant_listings_restaurant_listings_data_fields', $fields, $post->ID );

		uasort( $fields, array( $this, 'sort_by_priority' ) );

		return $fields;
	}

	/**
	 * Sorts array of custom fields by priority value.
	 *
	 * @param array $a
	 * @param array $b
	 * @return int
	 */
	protected function sort_by_priority( $a, $b ) {
	    if ( ! isset( $a['priority'] ) || ! isset( $b['priority'] ) || $a['priority'] === $b['priority'] ) {
	        return 0;
	    }
	    return ( $a['priority'] < $b['priority'] ) ? -1 : 1;
	}

	/**
	 * Handles the hooks to add custom field meta boxes.
	 */
	public function add_meta_boxes() {
		global $wp_post_types;

		add_meta_box( 'restaurant_listings_data', sprintf( __( '%s Data', 'wp-restaurant-listings' ), $wp_post_types['restaurant_listings']->labels->singular_name ), array( $this, 'restaurant_listings_data' ), 'restaurant_listings', 'normal', 'high' );
		add_meta_box( 'restaurant_hours_of_operation', __( 'Hours of Operation', 'wp-restaurant-listings' ), array( $this, 'restaurant_hours_of_operation' ), 'restaurant_listings', 'side', 'low' );
        add_meta_box( 'restaurant_listings_images', __( 'Photo gallery', 'wp-restaurant-listings' ), 'WP_Restaurant_Listings_Meta_Box_Gallery::output', 'restaurant_listings', 'side', 'low' );

		if ( ! get_option( 'restaurant_listings_enable_types' ) || wp_count_terms( 'restaurant_listings_type' ) == 0 ) {
			remove_meta_box( 'restaurant_listings_typediv', 'restaurant_listings', 'side');
		} elseif ( false == restaurant_listings_multi_restaurant_type() ) {
			remove_meta_box( 'restaurant_listings_typediv', 'restaurant_listings', 'side');
			$restaurant_listings_type = get_taxonomy( 'restaurant_listings_type' );
			add_meta_box( 'restaurant_listings_type', $restaurant_listings_type->labels->menu_name, array( $this, 'restaurant_listings_metabox' ),'restaurant_listings' ,'side','core');
		}
	}

	/**
	 * Displays restaurant listings metabox.
	 *
	 * @param int|WP_Post $post
	 */
	public function restaurant_listings_metabox( $post ) {
		// Set up the taxonomy object and get terms
		$taxonomy = 'restaurant_listings_type';
		$tax = get_taxonomy( $taxonomy );// This is the taxonomy object

		// The name of the form
		$name = 'tax_input[' . $taxonomy . ']';

		// Get all the terms for this taxonomy
		$terms = get_terms( $taxonomy, array( 'hide_empty' => 0 ) );
		$postterms = get_the_terms( $post->ID, $taxonomy );
		$current = ( $postterms ? array_pop( $postterms ) : false );
		$current = ( $current ? $current->term_id : 0 );
		// Get current and popular terms
		$popular = get_terms( $taxonomy, array( 'orderby' => 'count', 'order' => 'DESC', 'number' => 10, 'hierarchical' => false ) );
		$postterms = get_the_terms( $post->ID,$taxonomy );
		$current = ($postterms ? array_pop($postterms) : false);
		$current = ($current ? $current->term_id : 0);
		?>

		<div id="taxonomy-<?php echo $taxonomy; ?>" class="categorydiv">

			<!-- Display tabs-->
			<ul id="<?php echo $taxonomy; ?>-tabs" class="category-tabs">
				<li class="tabs"><a href="#<?php echo $taxonomy; ?>-all" tabindex="3"><?php echo $tax->labels->all_items; ?></a></li>
				<li class="hide-if-no-js"><a href="#<?php echo $taxonomy; ?>-pop" tabindex="3"><?php _e( 'Most Used', 'wp-restaurant-listings' ); ?></a></li>
			</ul>

			<!-- Display taxonomy terms -->
			<div id="<?php echo $taxonomy; ?>-all" class="tabs-panel">
				<ul id="<?php echo $taxonomy; ?>checklist" class="list:<?php echo $taxonomy?> categorychecklist form-no-clear">
					<?php   foreach($terms as $term){
						$id = $taxonomy.'-'.$term->term_id;
						echo "<li id='$id'><label class='selectit'>";
						echo "<input type='radio' id='in-$id' name='{$name}'".checked($current,$term->term_id,false)."value='$term->term_id' />$term->name<br />";
					   echo "</label></li>";
					}?>
			   </ul>
			</div>

			<!-- Display popular taxonomy terms -->
			<div id="<?php echo $taxonomy; ?>-pop" class="tabs-panel" style="display: none;">
				<ul id="<?php echo $taxonomy; ?>checklist-pop" class="categorychecklist form-no-clear" >
					<?php   foreach($popular as $term){
						$id = 'popular-'.$taxonomy.'-'.$term->term_id;
						echo "<li id='$id'><label class='selectit'>";
						echo "<input type='radio' id='in-$id'".checked($current,$term->term_id,false)."value='$term->term_id' />$term->name<br />";
						echo "</label></li>";
					}?>
			   </ul>
		   </div>

		</div>
		<?php
	}

    /**
     * Displays label and file input field.
     *
     * @param string $key
     * @param array  $field
     */
    public static function input_file( $key, $field ) {
        global $thepostid;

        if ( ! isset( $field['value'] ) ) {
            $field['value'] = get_post_meta( $thepostid, $key, true );
        }
        if ( empty( $field['placeholder'] ) ) {
            $field['placeholder'] = 'http://';
        }
        if ( ! empty( $field['name'] ) ) {
            $name = $field['name'];
        } else {
            $name = $key;
        }
        ?>
        <p class="form-field">
            <label for="<?php echo esc_attr( $key ); ?>"><?php echo wp_strip_all_tags( $field['label'] ) ; ?>: <?php if ( ! empty( $field['description'] ) ) : ?><span class="tips" data-tip="<?php echo esc_attr( $field['description'] ); ?>">[?]</span><?php endif; ?></label>
            <?php
            if ( ! empty( $field['multiple'] ) ) {
                foreach ( (array) $field['value'] as $value ) {
                    ?><span class="file_url"><input type="text" name="<?php echo esc_attr( $name ); ?>[]" placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>" value="<?php echo esc_attr( $value ); ?>" /><button class="button button-small wp_restaurant_listings_upload_file_button" data-uploader_button_text="<?php _e( 'Use file', 'wp-restaurant-listings' ); ?>"><?php _e( 'Upload', 'wp-restaurant-listings' ); ?></button><button class="button button-small wp_restaurant_listings_view_file_button"><?php _e( 'View', 'wp-restaurant-listings' ); ?></button></span><?php
                }
            } else {
                ?><span class="file_url"><input type="text" name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $key ); ?>" placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>" value="<?php echo esc_attr( $field['value'] ); ?>" /><button class="button button-small wp_restaurant_listings_upload_file_button" data-uploader_button_text="<?php _e( 'Use file', 'wp-restaurant-listings' ); ?>"><?php _e( 'Upload', 'wp-restaurant-listings' ); ?></button><button class="button button-small wp_restaurant_listings_view_file_button"><?php _e( 'View', 'wp-restaurant-listings' ); ?></button></span><?php
            }
            if ( ! empty( $field['multiple'] ) ) {
                ?><button class="button button-small wp_restaurant_listings_add_another_file_button" data-field_name="<?php echo esc_attr( $key ); ?>" data-field_placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>" data-uploader_button_text="<?php _e( 'Use file', 'wp-restaurant-listings' ); ?>" data-uploader_button="<?php _e( 'Upload', 'wp-restaurant-listings' ); ?>" data-view_button="<?php _e( 'View', 'wp-restaurant-listings' ); ?>"><?php _e( 'Add file', 'wp-restaurant-listings' ); ?></button><?php
            }
            ?>
        </p>
        <?php
    }

	/**
	 * Displays label and file menu field.
	 *
	 * @param string $key
	 * @param array  $field
	 */
	public static function input_menu_file( $key, $field ) {
		global $thepostid;

		if ( ! isset( $field['value'] ) ) {
			$field['value'] = get_post_meta( $thepostid, $key, true );
		}
		if ( empty( $field['placeholder'] ) ) {
			$field['placeholder'] = 'http://';
		}
		if ( ! empty( $field['name'] ) ) {
			$name = $field['name'];
		} else {
			$name = $key;
		}

		$menu_ids = explode( ',', $field['value'] );

        ?>
		<p class="form-field">
			<label for="<?php echo esc_attr( $key ); ?>"><?php echo wp_strip_all_tags( $field['label'] ) ; ?>: <?php if ( ! empty( $field['description'] ) ) : ?><span class="tips" data-tip="<?php echo esc_attr( $field['description'] ); ?>">[?]</span><?php endif; ?></label>
			<?php
			if ( ! empty( $field['multiple'] ) ) {
				foreach ( (array) $menu_ids as $value ) {
				    $value = is_numeric($value) ? wp_get_attachment_url( $value ) : $value;
					?><span class="file_url"><input type="text" name="<?php echo esc_attr( $name ); ?>[]" placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>" value="<?php echo esc_attr( $value ); ?>" /><button class="button button-small wp_restaurant_listings_upload_menu_file_button" data-uploader_button_text="<?php _e( 'Use file', 'wp-restaurant-listings' ); ?>"><?php _e( 'Upload', 'wp-restaurant-listings' ); ?></button><button class="button button-small wp_restaurant_listings_upload_menu_file_button"><?php _e( 'View', 'wp-restaurant-listings' ); ?></button></span><?php
				}
			} else {
				?><span class="file_url"><input type="text" name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $key ); ?>" placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>" value="<?php echo esc_attr( $field['value'] ); ?>" /><button class="button button-small wp_restaurant_listings_upload_menu_file_button" data-uploader_button_text="<?php _e( 'Use file', 'wp-restaurant-listings' ); ?>"><?php _e( 'Upload', 'wp-restaurant-listings' ); ?></button><button class="button button-small wp_restaurant_listings_view_menu_file_button"><?php _e( 'View', 'wp-restaurant-listings' ); ?></button></span><?php
			}
			if ( ! empty( $field['multiple'] ) ) {
				?><button class="button button-small wp_restaurant_listings_add_another_menu_file_button" data-field_name="<?php echo esc_attr( $key ); ?>" data-field_placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>" data-uploader_button_text="<?php _e( 'Use file', 'wp-restaurant-listings' ); ?>" data-uploader_button="<?php _e( 'Upload', 'wp-restaurant-listings' ); ?>" data-view_button="<?php _e( 'View', 'wp-restaurant-listings' ); ?>"><?php _e( 'Add file', 'wp-restaurant-listings' ); ?></button><?php
			}
			?>
            <input type="hidden" name="menu_files" id="menu_files" value="<?php echo empty( $field['value'] ) ? '' : $field['value'] ?>">
		</p>
		<?php
	}

	/**
	 * Displays label and text input field.
	 *
	 * @param string $key
	 * @param array  $field
	 */
	public static function input_text( $key, $field ) {
		global $thepostid;

		if ( ! isset( $field['value'] ) ) {
			$field['value'] = get_post_meta( $thepostid, $key, true );
		}
		if ( ! empty( $field['name'] ) ) {
			$name = $field['name'];
		} else {
			$name = $key;
		}
		if ( ! empty( $field['classes'] ) ) {
			$classes = implode( ' ', is_array( $field['classes'] ) ? $field['classes'] : array( $field['classes'] ) );
		} else {
			$classes = '';
		}
		?>
		<p class="form-field">
			<label for="<?php echo esc_attr( $key ); ?>"><?php echo wp_strip_all_tags( $field['label'] ) ; ?>: <?php if ( ! empty( $field['description'] ) ) : ?><span class="tips" data-tip="<?php echo esc_attr( $field['description'] ); ?>">[?]</span><?php endif; ?></label>
			<input type="text" autocomplete="off" name="<?php echo esc_attr( $name ); ?>" class="<?php echo esc_attr( $classes ); ?>" id="<?php echo esc_attr( $key ); ?>" placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>" value="<?php echo esc_attr( $field['value'] ); ?>" />
		</p>
		<?php
	}

	/**
	 * Just displays information.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key
	 * @param array  $field
	 */
	public static function input_info( $key, $field ) {
		self::input_hidden( $key, $field );
	}

	/**
	 * Displays information and/or hidden input.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key
	 * @param array  $field
	 */
	public static function input_hidden( $key, $field ) {
		global $thepostid;

		if ( 'hidden' === $field['type'] && ! isset( $field['value'] ) ) {
			$field['value'] = get_post_meta( $thepostid, $key, true );
		}
		if ( ! empty( $field['name'] ) ) {
			$name = $field['name'];
		} else {
			$name = $key;
		}
		if ( ! empty( $field['classes'] ) ) {
			$classes = implode( ' ', is_array( $field['classes'] ) ? $field['classes'] : array( $field['classes'] ) );
		} else {
			$classes = '';
		}
		$hidden_input = '';
		if ( 'hidden' === $field['type'] ) {
			$hidden_input = '<input type="hidden" name="' . esc_attr( $name ) . '" class="' . esc_attr( $classes ) . '" id="' . esc_attr( $key ) . '" value="' . esc_attr( $field['value'] ) . '" />';
			if ( empty( $field['label'] ) ) {
				echo $hidden_input;
				return;
			}
		}
		?>
		<p class="form-field">
			<label for="<?php echo esc_attr( $key ); ?>"><?php echo wp_strip_all_tags( $field['label'] ) ; ?>: <?php if ( ! empty( $field['description'] ) ) : ?><span class="tips" data-tip="<?php echo esc_attr( $field['description'] ); ?>">[?]</span><?php endif; ?></label>
			<?php if ( ! empty( $field['information'] ) ) : ?><span class="information"><?php echo wp_kses( $field['information'], array( 'a' => array( 'href' => array() ) ) ); ?></span><?php endif; ?>
			<?php echo $hidden_input; ?>
		</p>
		<?php
	}

	/**
	 * Displays label and textarea input field.
	 *
	 * @param string $key
	 * @param array  $field
	 */
	public static function input_textarea( $key, $field ) {
		global $thepostid;

		if ( ! isset( $field['value'] ) ) {
			$field['value'] = get_post_meta( $thepostid, $key, true );
		}
		if ( ! empty( $field['name'] ) ) {
			$name = $field['name'];
		} else {
			$name = $key;
		}
		?>
		<p class="form-field">
			<label for="<?php echo esc_attr( $key ); ?>"><?php echo wp_strip_all_tags( $field['label'] ) ; ?>: <?php if ( ! empty( $field['description'] ) ) : ?><span class="tips" data-tip="<?php echo esc_attr( $field['description'] ); ?>">[?]</span><?php endif; ?></label>
			<textarea name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $key ); ?>" placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>"><?php echo esc_html( $field['value'] ); ?></textarea>
		</p>
		<?php
	}

	/**
	 * Displays label and select input field.
	 *
	 * @param string $key
	 * @param array  $field
	 */
	public static function input_select( $key, $field ) {
		global $thepostid;

		if ( ! isset( $field['value'] ) ) {
			$field['value'] = get_post_meta( $thepostid, $key, true );
		}
		if ( ! empty( $field['name'] ) ) {
			$name = $field['name'];
		} else {
			$name = $key;
		}
		?>
		<p class="form-field">
			<label for="<?php echo esc_attr( $key ); ?>"><?php echo wp_strip_all_tags( $field['label'] ) ; ?>: <?php if ( ! empty( $field['description'] ) ) : ?><span class="tips" data-tip="<?php echo esc_attr( $field['description'] ); ?>">[?]</span><?php endif; ?></label>
			<select name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $key ); ?>">
				<?php foreach ( $field['options'] as $key => $value ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php if ( isset( $field['value'] ) ) selected( $field['value'], $key ); ?>><?php echo esc_html( $value ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<?php
	}

	/**
	 * Displays label and multi-select input field.
	 *
	 * @param string $key
	 * @param array  $field
	 */
	public static function input_multiselect( $key, $field ) {
		global $thepostid;

		if ( ! isset( $field['value'] ) ) {
			$field['value'] = get_post_meta( $thepostid, $key, true );
		}
		if ( ! empty( $field['name'] ) ) {
			$name = $field['name'];
		} else {
			$name = $key;
		}
		?>
		<p class="form-field">
			<label for="<?php echo esc_attr( $key ); ?>"><?php echo wp_strip_all_tags( $field['label'] ) ; ?>: <?php if ( ! empty( $field['description'] ) ) : ?><span class="tips" data-tip="<?php echo esc_attr( $field['description'] ); ?>">[?]</span><?php endif; ?></label>
			<select multiple="multiple" name="<?php echo esc_attr( $name ); ?>[]" id="<?php echo esc_attr( $key ); ?>">
				<?php foreach ( $field['options'] as $key => $value ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php if ( ! empty( $field['value'] ) && is_array( $field['value'] ) ) selected( in_array( $key, $field['value'] ), true ); ?>><?php echo esc_html( $value ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<?php
	}

	/**
	 * Displays label and checkbox input field.
	 *
	 * @param string $key
	 * @param array  $field
	 */
	public static function input_checkbox( $key, $field ) {
		global $thepostid;

		if ( empty( $field['value'] ) ) {
			$field['value'] = get_post_meta( $thepostid, $key, true );
		}
		if ( ! empty( $field['name'] ) ) {
			$name = $field['name'];
		} else {
			$name = $key;
		}
		?>
		<p class="form-field form-field-checkbox">
			<label for="<?php echo esc_attr( $key ); ?>"><?php echo wp_strip_all_tags( $field['label'] ) ; ?></label>
			<input type="checkbox" class="checkbox" name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $key ); ?>" value="1" <?php checked( $field['value'], 1 ); ?> />
			<?php if ( ! empty( $field['description'] ) ) : ?><span class="description"><?php echo $field['description']; ?></span><?php endif; ?>
		</p>
		<?php
	}

	/**
	 * Displays label and author select field.
	 *
	 * @param string $key
	 * @param array  $field
	 */
	public static function input_author( $key, $field ) {
		global $thepostid, $post;

		if ( ! $post || $thepostid !== $post->ID ) {
			$the_post  = get_post( $thepostid );
			$author_id = $the_post->post_author;
		} else {
			$author_id = $post->post_author;
		}

		$posted_by      = get_user_by( 'id', $author_id );
		$field['value'] = ! isset( $field['value'] ) ? get_post_meta( $thepostid, $key, true ) : $field['value'];
		$name           = ! empty( $field['name'] ) ? $field['name'] : $key;
		?>
		<p class="form-field form-field-author">
			<label for="<?php echo esc_attr( $key ); ?>"><?php echo wp_strip_all_tags( $field['label'] ) ; ?>:</label>
			<span class="current-author">
				<?php
					if ( $posted_by ) {
						echo '<a href="' . admin_url( 'user-edit.php?user_id=' . absint( $author_id ) ) . '">#' . absint( $author_id ) . ' &ndash; ' . $posted_by->user_login . '</a>';
					} else {
						 _e( 'Guest User', 'wp-restaurant-listings' );
					}
				?> <a href="#" class="change-author button button-small"><?php _e( 'Change', 'wp-restaurant-listings' ); ?></a>
			</span>
			<span class="hidden change-author">
				<input type="number" name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $key ); ?>" step="1" value="<?php echo esc_attr( $author_id ); ?>" style="width: 4em;" />
				<span class="description"><?php _e( 'Enter the ID of the user, or leave blank if submitted by a guest.', 'wp-restaurant-listings' ) ?></span>
			</span>
		</p>
		<?php
	}

	/**
	 * Displays label and radio input field.
	 *
	 * @param string $key
	 * @param array  $field
	 */
	public static function input_radio( $key, $field ) {
		global $thepostid;

		if ( empty( $field['value'] ) ) {
			$field['value'] = get_post_meta( $thepostid, $key, true );
		}
		if ( ! empty( $field['name'] ) ) {
			$name = $field['name'];
		} else {
			$name = $key;
		}
		?>
		<p class="form-field form-field-checkbox">
			<label><?php echo wp_strip_all_tags( $field['label'] ) ; ?></label>
			<?php foreach ( $field['options'] as $option_key => $value ) : ?>
				<label><input type="radio" class="radio" name="<?php echo esc_attr( isset( $field['name'] ) ? $field['name'] : $key ); ?>" value="<?php echo esc_attr( $option_key ); ?>" <?php checked( $field['value'], $option_key ); ?> /> <?php echo esc_html( $value ); ?></label>
			<?php endforeach; ?>
			<?php if ( ! empty( $field['description'] ) ) : ?><span class="description"><?php echo $field['description']; ?></span><?php endif; ?>
		</p>
		<?php
	}

	/**
	 * Displays metadata fields for Restaurant Listings.
	 *
	 * @param int|WP_Post $post
	 */
	public function restaurant_listings_data( $post ) {
		global $post, $thepostid;

		$thepostid = $post->ID;

		echo '<div class="wp_restaurant_listings_meta_data">';

		wp_nonce_field( 'save_meta_data', 'restaurant_listings_nonce' );

		do_action( 'restaurant_listings_restaurant_listings_data_start', $thepostid );

		foreach ( $this->restaurant_listings_fields() as $key => $field ) {
			$type = ! empty( $field['type'] ) ? $field['type'] : 'text';

			if ( has_action( 'restaurant_listings_input_' . $type ) ) {
				do_action( 'restaurant_listings_input_' . $type, $key, $field );
			} elseif ( method_exists( $this, 'input_' . $type ) ) {
				call_user_func( array( $this, 'input_' . $type ), $key, $field );
			}
		}

		do_action( 'restaurant_listings_restaurant_listings_data_end', $thepostid );

		echo '</div>';
	}

    public function restaurant_hours_of_operation( $key, $field ) {
        global $wp_locale, $post, $thepostid;

        $thepostid = $post->ID;
        ?>

        <div class="form-field" style="position: relative;">

            <?php if ( ! is_admin() ) : ?>
                <label for="<?php echo esc_attr( $key ); ?>"><?php echo wp_strip_all_tags( $field['label'] ) ; ?>:</label>
            <?php endif; ?>

            <?php
            global $field;

            if ( empty( $field[ 'value' ] ) ) {
                $field[ 'value' ] = get_post_meta( $thepostid, '_restaurant_hours', true );
            }

            get_restaurant_listings_template( 'form-fields/business-hours-field.php' );
            ?>

        </div>

        <?php
    }

	/**
	 * Handles `save_post` action.
	 *
	 * @param int     $post_id
	 * @param WP_Post $post
	 */
	public function save_post( $post_id, $post ) {
		if ( empty( $post_id ) || empty( $post ) || empty( $_POST ) ) return;
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
		if ( is_int( wp_is_post_revision( $post ) ) ) return;
		if ( is_int( wp_is_post_autosave( $post ) ) ) return;
		if ( empty($_POST['restaurant_listings_nonce']) || ! wp_verify_nonce( $_POST['restaurant_listings_nonce'], 'save_meta_data' ) ) return;
		if ( ! current_user_can( 'edit_post', $post_id ) ) return;
		if ( $post->post_type != 'restaurant_listings' ) return;

		do_action( 'restaurant_listings_save_restaurant_listing', $post_id, $post );
	}

	/**
	 * Handles the actual saving of restaurant listings data fields.
	 *
	 * @param int     $post_id
	 * @param WP_Post $post (Unused)
	 */
	public function save_restaurant_listings_data( $post_id, $post ) {
		global $wpdb;

		// These need to exist
		add_post_meta( $post_id, '_featured', 0, true );

		// Save fields
		foreach ( $this->restaurant_listings_fields() as $key => $field ) {
			if ( isset( $field['type'] ) && 'info' === $field['type'] ) {
				continue;
			}

			// Locations
			elseif ( '_restaurant_location' === $key ) {
				if ( update_post_meta( $post_id, $key, sanitize_text_field( $_POST[ $key ] ) ) ) {
					// Location data will be updated by hooked in methods
				} elseif ( apply_filters( 'restaurant_listings_geolocation_enabled', true ) && ! WP_Restaurant_Listings_Geocode::has_location_data( $post_id ) ) {
					WP_Restaurant_Listings_Geocode::generate_location_data( $post_id, sanitize_text_field( $_POST[ $key ] ) );
				}
			}

			elseif ( '_restaurant_author' === $key ) {
				$wpdb->update( $wpdb->posts, array( 'post_author' => $_POST[ $key ] > 0 ? absint( $_POST[ $key ] ) : 0 ), array( 'ID' => $post_id ) );
			}

			elseif ( '_application' === $key ) {
				update_post_meta( $post_id, $key, sanitize_text_field( is_email( $_POST[ $key ] ) ? $_POST[ $key ] : urldecode( $_POST[ $key ] ) ) );
			}

			elseif ( '_restaurant_menu' === $key ) {
			    if ( !empty( $_POST['menu_files'] ) ) {
                    update_post_meta( $post_id, $key, $_POST['menu_files'] );
                }
            }
			// Everything else
			else {
				$type = ! empty( $field['type'] ) ? $field['type'] : '';

				switch ( $type ) {
					case 'textarea' :
						update_post_meta( $post_id, $key, wp_kses_post( stripslashes( $_POST[ $key ] ) ) );
					break;
					case 'checkbox' :
						if ( isset( $_POST[ $key ] ) ) {
							update_post_meta( $post_id, $key, 1 );
						} else {
							update_post_meta( $post_id, $key, 0 );
						}
					break;
					default :
						if ( ! isset( $_POST[ $key ] ) ) {
							continue;
						} elseif ( is_array( $_POST[ $key ] ) ) {
							update_post_meta( $post_id, $key, array_filter( array_map( 'sanitize_text_field', $_POST[ $key ] ) ) );
						} else {
							update_post_meta( $post_id, $key, sanitize_text_field( $_POST[ $key ] ) );
						}
					break;
				}
			}
		}

		// Restaurant hours
        if ( isset( $_POST[ 'restaurant_hours' ] ) ) {
            update_post_meta( $post_id, '_restaurant_hours',  $_POST[ 'restaurant_hours' ] );
        }
	}

}

WP_Restaurant_Listings_Writepanels::instance();
