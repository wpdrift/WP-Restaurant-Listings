<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles initial environment setup after plugin is first activated.
 *
 * @package RestaurantListings
 * @since 1.0.0
 */
class WP_Restaurant_Listings_Setup {

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
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 12 );
		add_action( 'admin_head', array( $this, 'admin_head' ) );
		add_action( 'admin_init', array( $this, 'redirect' ) );
		if ( isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] == 'restaurant-listings-setup' )
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 12 );
	}

	/**
	 * Adds setup link to admin dashboard menu briefly so the page callback is registered.
	 */
	public function admin_menu() {
		add_dashboard_page( __( 'Setup', 'wp-restaurant-listings' ), __( 'Setup', 'wp-restaurant-listings' ), 'manage_options', 'restaurant-listings-setup', array( $this, 'output' ) );
	}

	/**
	 * Removes the setup link from admin dashboard menu so just the handler callback is registered.
	 */
	public function admin_head() {
		remove_submenu_page( 'index.php', 'restaurant-listings-setup' );
	}

	/**
	 * Sends user to the setup page on first activation.
	 */
	public function redirect() {
		// Bail if no activation redirect transient is set
	    if ( ! get_transient( '_restaurant_listings_activation_redirect' ) ) {
			return;
	    }

	    if ( ! current_user_can( 'manage_options' ) ) {
	    	return;
	    }

		// Delete the redirect transient
		delete_transient( '_restaurant_listings_activation_redirect' );

		// Bail if activating from network, or bulk, or within an iFrame
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) || defined( 'IFRAME_REQUEST' ) ) {
			return;
		}

		if ( ( isset( $_GET['action'] ) && 'upgrade-plugin' == $_GET['action'] ) && ( isset( $_GET['plugin'] ) && strstr( $_GET['plugin'], 'wp-restaurant-listings.php' ) ) ) {
			return;
		}

		wp_redirect( admin_url( 'index.php?page=restaurant-listings-setup' ) );
		exit;
	}

	/**
	 * Enqueues scripts for setup page.
	 */
	public function admin_enqueue_scripts() {
		wp_enqueue_style( 'restaurant_listings_setup_css', RESTAURANT_LISTING_PLUGIN_URL . '/assets/css/setup.css', array( 'dashicons' ), RESTAURANT_LISTING_VERSION );
	}

	/**
	 * Creates a page.
	 *
	 * @param  string $title
	 * @param  string $content
	 * @param  string $option
	 */
	public function create_page( $title, $content, $option ) {
		$page_data = array(
			'post_status'    => 'publish',
			'post_type'      => 'page',
			'post_author'    => 1,
			'post_name'      => sanitize_title( $title ),
			'post_title'     => $title,
			'post_content'   => $content,
			'post_parent'    => 0,
			'comment_status' => 'closed'
		);
		$page_id = wp_insert_post( $page_data );

		if ( $option ) {
			update_option( $option, $page_id );
		}
	}

	/**
	 * Displays setup page.
	 */
	public function output() {
		$step = ! empty( $_GET['step'] ) ? absint( $_GET['step'] ) : 1;

		if ( 3 === $step && ! empty( $_POST ) ) {
			if ( false == wp_verify_nonce( $_REQUEST[ 'setup_wizard' ], 'step_3' ) )
				wp_die( 'Error in nonce. Try again.', 'wp-restaurant-listings' );
			$create_pages    = isset( $_POST['wp-restaurant-listings-create-page'] ) ? $_POST['wp-restaurant-listings-create-page'] : array();
			$page_titles     = $_POST['wp-restaurant-listings-page-title'];
			$pages_to_create = array(
				'submit_restaurant_form'    => '[submit_restaurant_form]',
				'restaurant_dashboard'      => '[restaurant_dashboard]',
				'restaurants'               => '[restaurants]',
				'restaurants_locator'       => '[restaurants_locator]'
			);

			foreach ( $pages_to_create as $page => $content ) {
				if ( ! isset( $create_pages[ $page ] ) || empty( $page_titles[ $page ] ) ) {
					continue;
				}
				$this->create_page( sanitize_text_field( $page_titles[ $page ] ), $content, 'restaurant_listings_' . $page . '_page_id' );
			}
		}
		?>
		<div class="wrap wp_restaurant_listing wp_restaurant_listings_addons_wrap">
			<h2><?php _e( 'WP Restaurant Listings Setup', 'wp-restaurant-listings' ); ?></h2>

			<ul class="wp-restaurant-listings-setup-steps">
				<li class="<?php if ( $step === 1 ) echo 'wp-restaurant-listings-setup-active-step'; ?>"><?php _e( '1. Introduction', 'wp-restaurant-listings' ); ?></li>
				<li class="<?php if ( $step === 2 ) echo 'wp-restaurant-listings-setup-active-step'; ?>"><?php _e( '2. Page Setup', 'wp-restaurant-listings' ); ?></li>
				<li class="<?php if ( $step === 3 ) echo 'wp-restaurant-listings-setup-active-step'; ?>"><?php _e( '3. Done', 'wp-restaurant-listings' ); ?></li>
			</ul>

			<?php if ( 1 === $step ) : ?>

				<h3><?php _e( 'Welcome to the Setup Wizard!', 'wp-restaurant-listings' ); ?></h3>

				<p><?php _e( 'Thanks for installing <em>WP Restaurant Listings</em>! Let\'s get your site ready to accept restaurant listings.', 'wp-restaurant-listings' ); ?></p>
				<p><?php _e( 'This setup wizard will walk you through the process of creating the pages for restaurant submissions, management, and listings.', 'wp-restaurant-listings' ); ?></p>
				<p><?php printf( __( 'If you\'d prefer to skip this and set up your pages manually, our %sdocumentation%s will walk you through each step.', 'wp-restaurant-listings' ), '<a href="https://wpdrift.com/docs/restaurants/">', '</a>' ); ?></p>

				<p class="submit">
					<a href="<?php echo esc_url( add_query_arg( 'step', 2 ) ); ?>" class="button button-primary"><?php _e( 'Start setup', 'wp-restaurant-listings' ); ?></a>
					<a href="<?php echo esc_url( add_query_arg( 'skip-restaurant-listings-setup', 1, admin_url( 'index.php?page=restaurant-listings-setup&step=3' ) ) ); ?>" class="button"><?php _e( 'Skip setup. I will set up the plugin manually.', 'wp-restaurant-listings' ); ?></a>
				</p>

			<?php endif; ?>
			<?php if ( 2 === $step ) : ?>

				<h3><?php _e( 'Page Setup', 'wp-restaurant-listings' ); ?></h3>

				<p><?php _e( 'WP Restaurant Listings, users can post, manage, and browse restaurant listings right on your website. Tell us which of these common pages you\'d like your site to have and we\'ll create and configure them for you.', 'wp-restaurant-listings' ); ?></p>
				<p><?php printf( __( '(These pages are created using %1$sshortcodes%2$s, which we take care of in this step. If you\'d like to build these pages yourself or want to add one of these options to an existing page on your site, you can skip this step and take a look at %4$sshortcode documentation%2$s for detailed instructions.)', 'wp-restaurant-listings' ), '<a href="http://codex.wordpress.org/Shortcode" title="What is a shortcode?" target="_blank" class="help-page-link">', '</a>', '<a href="http://codex.wordpress.org/Pages" target="_blank" class="help-page-link">', '<a href="https://wpdrift.com/docs/restaurants/shortcode-reference/" target="_blank" class="help-page-link">' ); ?></p>

				<form action="<?php echo esc_url( add_query_arg( 'step', 3 ) ); ?>" method="post">
				<?php wp_nonce_field( 'step_3', 'setup_wizard' ); ?>
					<table class="wp-restaurant-listings-shortcodes widefat">
						<thead>
							<tr>
								<th>&nbsp;</th>
								<th><?php _e( 'Page Title', 'wp-restaurant-listings' ); ?></th>
								<th><?php _e( 'Page Description', 'wp-restaurant-listings' ); ?></th>
								<th><?php _e( 'Content Shortcode', 'wp-restaurant-listings' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td><input type="checkbox" checked="checked" name="wp-restaurant-listings-create-page[submit_restaurant_form]" /></td>
								<td><input type="text" value="<?php echo esc_attr( _x( 'Post a Restaurant', 'Default page title (wizard)', 'wp-restaurant-listings' ) ); ?>" name="wp-restaurant-listings-page-title[submit_restaurant_form]" /></td>
								<td>
									<p><?php _e( 'Creates a page that allows users to post new restaurants directly from a page on your website, instead of requiring them to log in to an admin area. If you\'d rather not allow this -- for example, if you want users to use the admin dashboard only -- you can uncheck this setting.', 'wp-restaurant-listings' ); ?></p>
								</td>
								<td><code>[submit_restaurant_form]</code></td>
							</tr>
							<tr>
								<td><input type="checkbox" checked="checked" name="wp-restaurant-listings-create-page[restaurant_dashboard]" /></td>
								<td><input type="text" value="<?php echo esc_attr( _x( 'Restaurant Dashboard', 'Default page title (wizard)', 'wp-restaurant-listings' ) ); ?>" name="wp-restaurant-listings-page-title[restaurant_dashboard]" /></td>
								<td>
									<p><?php _e( 'Creates a page that allows users to manage their restaurant listings directly from a page on your website, instead of requiring them to log in to an admin area. If you want to manage all restaurant listings from the admin dashboard only, you can uncheck this setting.', 'wp-restaurant-listings' ); ?></p>
								</td>
								<td><code>[restaurant_dashboard]</code></td>
							</tr>
							<tr>
								<td><input type="checkbox" checked="checked" name="wp-restaurant-listings-create-page[restaurants]" /></td>
								<td><input type="text" value="<?php echo esc_attr( _x( 'Restaurants', 'Default page title (wizard)', 'wp-restaurant-listings' ) ); ?>" name="wp-restaurant-listings-page-title[restaurants]" /></td>
								<td><?php _e( 'Creates a page where visitors can browse, search, and filter restaurant listings.', 'wp-restaurant-listings' ); ?></td>
								<td><code>[restaurants]</code></td>
							</tr>
                            <tr>
								<td><input type="checkbox" checked="checked" name="wp-restaurant-listings-create-page[restaurants_locator]" /></td>
								<td><input type="text" value="<?php echo esc_attr( _x( 'Restaurants Locator', 'Default page title (wizard)', 'wp-restaurant-listings' ) ); ?>" name="wp-restaurant-listings-page-title[restaurants_locator]" /></td>
								<td><?php _e( 'This page allows users to locate near by restaurants on the front-end of your site.', 'wp-restaurant-listings' ); ?></td>
								<td><code>[restaurants_locator]</code></td>
							</tr>
						</tbody>
						<tfoot>
							<tr>
								<th colspan="4">
									<input type="submit" class="button button-primary" value="Create selected pages" />
									<a href="<?php echo esc_url( add_query_arg( 'step', 3 ) ); ?>" class="button"><?php _e( 'Skip this step', 'wp-restaurant-listings' ); ?></a>
								</th>
							</tr>
						</tfoot>
					</table>
				</form>

			<?php endif; ?>
			<?php if ( 3 === $step ) : ?>

				<h3><?php _e( 'You\'re ready to start using WP Restaurant Listings!', 'wp-restaurant-listings' ); ?></h3>

				<p><?php _e( 'Wondering what to do now? Here are some of the most common next steps:', 'wp-restaurant-listings' ); ?></p>

				<ul class="wp-restaurant-listings-next-steps">
					<li><a href="<?php echo admin_url( 'edit.php?post_type=restaurant_listings&page=restaurant-listings-settings' ); ?>"><?php _e( 'Tweak your settings', 'wp-restaurant-listings' ); ?></a></li>
					<li><a href="<?php echo admin_url( 'post-new.php?post_type=restaurant_listings' ); ?>"><?php _e( 'Add a restaurant using the admin dashboard', 'wp-restaurant-listings' ); ?></a></li>

					<?php if ( $permalink = restaurant_listings_get_permalink( 'restaurants' ) ) : ?>
						<li><a href="<?php echo esc_url( $permalink ); ?>"><?php _e( 'View submitted restaurant listings', 'wp-restaurant-listings' ); ?></a></li>
					<?php else : ?>
						<li><a href="https://wpdrift.com/docs/restaurants/shortcode-reference/#section-1"><?php _e( 'Add restaurant listings to a page using the [restaurants] shortcode', 'wp-restaurant-listings' ); ?></a></li>
					<?php endif; ?>

					<?php if ( $permalink = restaurant_listings_get_permalink( 'submit_restaurant_form' ) ) : ?>
						<li><a href="<?php echo esc_url( $permalink ); ?>"><?php _e( 'Add a restaurant via the front-end', 'wp-restaurant-listings' ); ?></a></li>
					<?php else : ?>
						<li><a href="https://wpdrift.com/docs/restaurants/the-restaurant-submission-form/"><?php _e( 'Learn to use the front-end restaurant submission board', 'wp-restaurant-listings' ); ?></a></li>
					<?php endif; ?>

					<?php if ( $permalink = restaurant_listings_get_permalink( 'restaurant_dashboard' ) ) : ?>
						<li><a href="<?php echo esc_url( $permalink ); ?>"><?php _e( 'View the restaurant dashboard', 'wp-restaurant-listings' ); ?></a></li>
					<?php else : ?>
						<li><a href="https://wpdrift.com/docs/restaurants/the-restaurant-dashboard/"><?php _e( 'Learn to use the front-end restaurant dashboard', 'wp-restaurant-listings' ); ?></a></li>
					<?php endif; ?>
				</ul>

				<p><?php printf( __( 'If you need help, you can find more detail in our %1$ssupport documentation%2$s or post your question on the %3$sWP Restaurant Listings support forums%2$s.', 'wp-restaurant-listings' ), '<a href="https://wpdrift.com/docs/restaurants/">', '</a>', '<a href="https://wordpress.org/support/plugin/wp-restaurant-listings">' ); ?></p>

				<div class="wp-restaurant-listings-support-the-plugin">
					<h3><?php _e( 'Support WP Restaurant Listings\'s Ongoing Development', 'wp-restaurant-listings' ); ?></h3>
					<p><?php _e( 'There are lots of ways you can support open source software projects like this one: contributing code, fixing a bug, assisting with non-English translation, or just telling your friends about WP Restaurant Listings to help spread the word. We appreciate your support!', 'wp-restaurant-listings' ); ?></p>
					<ul>
						<li class="icon-review"><a href="https://wordpress.org/support/view/plugin-reviews/wp-restaurant-listings#postform"><?php _e( 'Leave a positive review', 'wp-restaurant-listings' ); ?></a></li>
						<li class="icon-localization"><a href="https://translate.wordpress.org/projects/wp-plugins/wp-restaurant-listings"><?php _e( 'Contribute a localization', 'wp-restaurant-listings' ); ?></a></li>
						<li class="icon-code"><a href="https://github.com/wpdrift/WP-Restaurant-Listings"><?php _e( 'Contribute code or report a bug', 'wp-restaurant-listings' ); ?></a></li>
						<li class="icon-forum"><a href="https://wordpress.org/support/plugin/wp-restaurant-listings"><?php _e( 'Help other users on the forums', 'wp-restaurant-listings' ); ?></a></li>
					</ul>
				</div>

			<?php endif; ?>
		</div>
		<?php
	}
}

WP_Restaurant_Listings_Setup::instance();
