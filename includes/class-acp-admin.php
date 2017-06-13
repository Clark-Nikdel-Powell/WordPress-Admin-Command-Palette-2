<?php
/**
 * ACP
 *
 *
 * @package   Admin Command Palette
 * @author    jhned
 * @license   GPL-3.0
 */

/**
 * @subpackage ACP_Admin
 */
class ACP_Admin {

	/**
	 * Instance of this class.
	 *
	 * @since    0.8.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Plugin basename.
	 *
	 * @since    0.8.0
	 *
	 * @var      string
	 */
	protected $plugin_basename = null;

	public $command_raw = '';
	public $command_type = '';
	public $action_name = '';
	public $post_types = array();
	public $search_keyword = '';
	public $return_data = [
		'results' => [],
		'count'   => 0,
	];

	/**
	 * Return an instance of this class.
	 *
	 * @since     0.8.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) {
			self::$instance = new self;
			self::$instance->do_hooks();
		}

		return self::$instance;
	}

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     0.8.0
	 */
	private function __construct() {
		$plugin            = ACP::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();
		$this->version     = $plugin->get_plugin_version();

		$this->plugin_basename = plugin_basename( plugin_dir_path( realpath( dirname( __FILE__ ) ) ) . $this->plugin_slug . '.php' );
	}


	/**
	 * Handle WP actions and filters.
	 *
	 * @since    0.8.0
	 */
	private function do_hooks() {

		// Register the REST API route.
		add_action( 'rest_api_init', [ $this, 'register_rest_route' ], 20 );

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_styles' ], 20 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ], 20 );

		// Render the markup for the modal
		add_action( 'admin_footer', [ $this, 'display_plugin_form_modal' ], 20 );
	}

	public function register_rest_route() {

		register_rest_route( $this->plugin_slug . '/v1', 'search', [
			'methods'  => WP_REST_Server::READABLE,
			'callback' => [ $this, 'determine_command_type' ],
		] );
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @since     0.8.0
	 */
	public function enqueue_admin_styles() {

		if ( ! is_customize_preview() ) {
			wp_enqueue_style( $this->plugin_slug . '-style', plugins_url( 'assets/css/admin.css', dirname( __FILE__ ) ), array(), $this->version );
		}
	}

	/**
	 * Register and enqueue admin-specific javascript
	 *
	 * @since     0.8.0
	 */
	public function enqueue_admin_scripts() {

		if ( ! is_customize_preview() ) {
			wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'assets/js/admin.js', dirname( __FILE__ ) ), array( 'jquery' ), $this->version );

			$post_types_arr = array();

			$args = [
				'public' => true,
			];

			$post_types = get_post_types( $args );

			foreach ( $post_types as $post_type_name ) {
				$post_types_arr[] = $post_type_name;
			}

			$help_data = [
				'postTypes' => $post_types_arr,
			];

			wp_localize_script( $this->plugin_slug . '-admin-script', 'acp_object', array(
					'api_nonce'      => wp_create_nonce( 'wp_rest' ),
					'api_search_url' => site_url( '/wp-json/' . $this->plugin_slug . '/v1/search/' ),
					'helpData'       => $help_data,
				)
			);
		}
	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    0.8.0
	 */
	public function display_plugin_form_modal() {

		echo '<div id="acp" class="acp"></div>';
	}

	/**
	 * determine_command_type
	 *
	 * This is the entry point from the JavaScript. The React app passes the request back to this function,
	 * where we then figure out what type of command type we're dealing with. Most of the time it'll be a
	 * search, but there are special commands for activating/deactivating plugins.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function determine_command_type( WP_REST_Request $request ) {

		$this->command_raw = $request->get_param( 'command' );

		if ( empty( $this->command_raw ) ) {
			return new WP_Error( 'no_command_found', __( 'A command was not found.', 'acp' ) );
		}

		if ( false !== strpos( $this->command_raw, '/' ) ) {
			$this->command_type = 'action';
			$this->determine_action();
		}

		if ( '' === $this->command_type ) {
			$this->command_type = 'search';
			$this->get_search_results();
		}

		$response = new \WP_REST_Response( $this->return_data );

		return $response;
	}

	/**
	 * determine_action
	 *
	 * Determines the specific action that we're executing. Options are:
	 * "/ap" for "activate plugin"
	 * "/dp" for "deactivate plugin"
	 */
	public function determine_action() {

		if ( '/ap' === $this->command_raw && current_user_can( 'activate_plugins' ) ) {
			$this->action_name = 'activate_plugin';
			$this->get_plugin_links();
		}
		if ( '/dp' === $this->command_raw && current_user_can( 'activate_plugins' ) ) {
			$this->action_name = 'deactivate_plugin';
			$this->get_plugin_links();
		}
	}

	/**
	 * get_plugin_links
	 *
	 * Sets up return data for plugin activation or deactivation links.
	 */
	public function get_plugin_links() {

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins      = get_plugins();
		$plugin_links = [];

		foreach ( $plugins as $plugin_file => $plugin_array ) {

			$url = '';

			if ( 'activate_plugin' === $this->action_name && ! is_plugin_active( $plugin_file ) ) {
				$url = str_replace( '&amp;', '&', wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . $plugin_file . '&amp;plugin_status=all&amp;paged=1&amp;s=', 'activate-plugin_' . $plugin_file ) );

				$plugin_links[] = [
					'title'       => $plugin_array['Name'],
					'object_type' => 'plugin',
					'url'         => $url,
				];
			}
			if ( 'deactivate_plugin' === $this->action_name && is_plugin_active( $plugin_file ) ) {
				$url = str_replace( '&amp;', '&', wp_nonce_url( 'plugins.php?action=deactivate&amp;plugin=' . $plugin_file . '&amp;plugin_status=all&amp;paged=1&amp;s=', 'deactivate-plugin_' . $plugin_file ) );

				$plugin_links[] = [
					'title'       => $plugin_array['Name'],
					'object_type' => 'plugin',
					'url'         => $url,
				];
			}
		}

		$this->return_data = [
			'results' => $plugin_links,
			'count'   => count( $plugin_links ),
		];
	}

	/**
	 * get_search_results
	 *
	 * Builds a search query to return posts data based on the search.
	 */
	public function get_search_results() {

		if ( false !== strpos( $this->command_raw, ':' ) ) {
			$this->get_search_post_types();
			$this->clean_search_keyword();
		}

		$query_args = [
			's'              => $this->search_keyword,
			'posts_per_page' => 10,
		];

		if ( ! empty( $this->post_types ) ) {
			$query_args['post_type'] = $this->post_types;
		}

		$results     = [];
		$total_count = 0;

		$search_query = new \WP_Query( $query_args );
		if ( ! empty( $search_query->posts ) ) {
			$query_results = $search_query->posts;
		} else {
			$query_results = [];
		}

		if ( ! empty( $query_results ) ) {

			foreach ( $query_results as $result_post ) {

				$title     = $result_post->post_title;
				$subtitle  = '';
				$post_type = $result_post->post_type;
				$url       = get_edit_post_link( $result_post->ID, 'noencode' );

				if ( is_post_type_hierarchical( $result_post->post_type ) ) {
					$subtitle = $this->get_hierarchical_subtitle( $result_post );
				}

				$results[] = [
					'title'       => $title,
					'object_type' => $post_type,
					'subtitle'    => $subtitle,
					'url'         => $url,
				];
			}

			$total_count = intval( $search_query->found_posts );
		}

		$admin_pages = $this->get_matching_admin_pages();
		if ( ! empty( $admin_pages ) ) {
			$results = array_merge( $results, $admin_pages );
		}

		$this->return_data = [
			'results' => $results,
			'count'   => $total_count,
		];
	}

	/**
	 * get_hierarchical_subtitle
	 *
	 * Builds an ancestor-driven subtitle for context in hierarchical post results.
	 *
	 * @param $result_post
	 *
	 * @return string
	 */
	public function get_hierarchical_subtitle( $result_post ) {

		$subtitle  = '';
		$ancestors = get_post_ancestors( $result_post->ID );

		if ( ! empty( $ancestors ) ) {

			$ancestor_titles = array();
			$ancestors       = array_reverse( $ancestors );

			foreach ( $ancestors as $ancestor_id ) {
				$ancestor_post     = get_post( $ancestor_id );
				$ancestor_titles[] = $ancestor_post->post_title;
			}
			$context_content = implode( ' / ', $ancestor_titles );

			$subtitle = ' | ' . $context_content;
		}

		return $subtitle;
	}

	/**
	 * get_search_post_types
	 *
	 * Parses the search keyword
	 */
	public function get_search_post_types() {

		$post_types         = [];
		$search_keyword_arr = explode( ' ', $this->command_raw );

		foreach ( $search_keyword_arr as $search_word ) {

			if ( false !== strpos( $search_word, ':' ) ) {
				$post_types[] = str_replace( ':', '', $search_word );
			}
		}

		$this->post_types = $post_types;
	}

	public function clean_search_keyword() {

		$search_keyword = $this->command_raw;

		if ( ! empty( $this->post_types ) ) {

			foreach ( $this->post_types as $post_type ) {
				$search_keyword = str_replace( ':' . $post_type, '', $search_keyword );
			}
		}

		$this->search_keyword = $search_keyword;
	}

	public function get_matching_admin_pages() {

		// TODO: the globals aren't accessible because we're in a REST API call, it thinks we're not in the admin. We'll need to cache them in a transient or an option with an admin hook and reference against that array here.
		$admin_pages = [];

		// Get the admin menu.
		global $menu;

		// Get the admin submenu items.
		global $submenu;

		// Keep these separate so that we don't accidentally modify it.
		$admin_menu_arr    = $menu;
		$admin_submenu_arr = $submenu;

		if ( ! empty( $admin_menu_arr ) ) {

			// Loop through the admin pages and add the data to an array.
			foreach ( $admin_menu_arr as $menu_order => $admin_menu_item ) {

				// If this is a separator, then we don't need it.
				if ( 'wp-menu-separator' === $admin_menu_item[4] ) {
					continue;
				}

				$menu_title = $admin_menu_item[0];
				$menu_url   = $admin_menu_item[2];

				// Clean the title
				$span_position = strpos( $menu_title, ' <span' );

				if ( 0 !== $span_position ) {
					$menu_title = substr( $menu_title, 0, $span_position );
				}

				// Add the admin page to the array
				$template['title']       = $menu_title;
				$template['url']         = $menu_url;
				$template['object_type'] = 'Admin Page';

				$admin_pages[] = $template;
			}
		}

		if ( ! empty( $admin_submenu_arr ) ) {

			// Loop through the admin submenu pages and add the data to an array.
			foreach ( $admin_submenu_arr as $parent_slug => $admin_submenu_items ) {

				// The submenu pages are grouped in sub-arrays under the parent slug, hence the extra loop.
				foreach ( $admin_submenu_items as $menu_order => $admin_submenu_item ) {

					$submenu_title = $admin_submenu_item[0];
					$submenu_url   = $admin_submenu_item[2];

					// When dealing with a submenu URL, if there isn't a .php suffix,
					// then the full URL is built based on the parent slug.
					if ( false === strpos( $submenu_url, '.php' ) ) {
						$submenu_url = $parent_slug . '?page=' . $admin_submenu_item[2];
					}

					// If "Add" is present, we need to append the post type name to the title for context.
					if ( false !== strpos( $submenu_title, 'Add' ) && 0 !== strpos( $submenu_url, 'post_type=' ) ) {

						$equal_position = strpos( $submenu_url, '=' );

						$submenu_post_type_slug = substr( $submenu_url, $equal_position + 1 );

						$submenu_title .= ' ' . ucfirst( $submenu_post_type_slug );
					}

					// Don't include the dashboard twice
					if ( 'index.php' === $submenu_url ) {
						continue;
					}

					// A couple of special cases for title
					if ( 'post-new.php' === $submenu_url ) {
						$submenu_title .= ' Post';
					}
					if ( 'upload.php' === $submenu_url ) {
						continue;
					}
					if ( 'media-new.php' === $submenu_url ) {
						$submenu_title .= ' Attachment';
					}
					if ( 'plugin-install.php' === $submenu_url ) {
						$submenu_title .= ' Plugin';
					}
					if ( 'user-new.php' === $submenu_url ) {
						$submenu_title .= ' User';
					}

					// Clean the title
					$span_position = strpos( $submenu_title, ' <span' );

					if ( 0 !== $span_position ) {
						$submenu_title = substr( $submenu_title, 0, $span_position );
					}

					$template['title'] = $submenu_title;
					$template['url']   = $submenu_url;
					$template['name']  = 'admin-page';

					$admin_pages[] = $template;
				} // End foreach().
			} // End foreach().
		} // End if().

		return $admin_pages;
	}
}
