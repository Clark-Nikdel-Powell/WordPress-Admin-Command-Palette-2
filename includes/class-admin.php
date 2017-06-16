<?php

namespace ACP;
/**
 * ACP
 *
 * @package   Admin Command Palette
 * @author    jhned
 * @license   GPL-3.0
 */

/**
 * @subpackage Admin
 */
class Admin {

	public $admin_pages_transient_name = 'acp_admin_pages';

	/**
	 * Instance of this class.
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Plugin basename.
	 *
	 * @var      string
	 */
	protected $plugin_basename = null;

	/**
	 * Return an instance of this class.
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
	 */
	private function __construct() {
		$plugin            = Plugin::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();
		$this->version     = $plugin->get_plugin_version();

		$this->plugin_basename = plugin_basename( plugin_dir_path( realpath( dirname( __FILE__ ) ) ) . $this->plugin_slug . '.php' );
	}

	/**
	 * Handle WP actions and filters.
	 */
	private function do_hooks() {

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_styles' ], 20 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ], 20 );

		// Render the markup for the modal
		add_action( 'admin_footer', [ $this, 'display_plugin_form_modal' ], 20 );

		// Register the options
		add_action( 'admin_init', [ $this, 'register_options' ], 20 );

		// Add the Options Page
		add_action( 'admin_menu', [ $this, 'add_options_page' ], 20 );

		// Preload Admin Pages as Transient. NOTE: the action priority matters here.
		add_action( 'admin_init', [ $this, 'preload_admin_menu_pages_as_transient' ], 21 );

		// Clear Admin Pages Transient
		add_action( 'activated_plugin', [ $this, 'clear_admin_menu_pages_transient' ], 20 );
		add_action( 'deactivated_plugin', [ $this, 'clear_admin_menu_pages_transient' ], 20 );
		add_action( 'after_switch_theme', [ $this, 'clear_admin_menu_pages_transient' ], 20 );
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 */
	public function enqueue_admin_styles() {

		if ( ! is_customize_preview() ) {
			wp_enqueue_style( $this->plugin_slug . '-style', plugins_url( 'assets/css/admin.css', dirname( __FILE__ ) ), array(), $this->version );
		}
	}

	/**
	 * Register and enqueue admin-specific javascript.
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
	 */
	public function display_plugin_form_modal() {

		echo '<div id="acp" class="acp"></div>';
	}

	public function register_options() {

		register_setting( 'acp_options', 'acp_included_post_types' );
		register_setting( 'acp_options', 'acp_included_taxonomies' );
	}

	public function add_options_page() {

		add_options_page( 'Admin Command Palette', 'Admin Command Palette', 'manage_options', 'acp', 'ACP\options_page' );

		function options_page() {
			include_once( 'partials/admin-settings-page.php' );
		}
	}

	/**
	 * Preloads the admin menu pages, since the $menu and $submenu globals aren't available during a REST API call.
	 *
	 * @return array
	 */
	public function preload_admin_menu_pages_as_transient() {

		$cached_admin_pages = get_transient( $this->admin_pages_transient_name );

		if ( empty( $cached_admin_pages ) ) {
			delete_transient( $this->admin_pages_transient_name );
		} else {
			return $cached_admin_pages;
		}

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

				if ( false !== $span_position ) {
					$menu_title = substr( $menu_title, 0, $span_position );
				}

				// Add the admin page to the array
				$admin_pages[] = new Data_Template( $menu_title, 'Admin Page', '', $menu_url );
			}
		}

		if ( ! empty( $admin_submenu_arr ) ) {

			// Loop through the admin submenu pages and add the data to an array.
			foreach ( $admin_submenu_arr as $parent_slug => $admin_submenu_items ) {

				$submenu_prefix = '';

				if ( 'index.php' === $parent_slug ) {
					$submenu_prefix = 'Dashboard';
				}
				if ( 'upload.php' === $parent_slug ) {
					$submenu_prefix = 'Media';
				}
				if ( 'link-manager.php' === $parent_slug ) {
					$submenu_prefix = 'Links';
				}
				if ( 'edit.php' === $parent_slug ) {
					$submenu_prefix = 'Posts';
				}
				if ( 'edit.php?post_type=page' === $parent_slug ) {
					$submenu_prefix = 'Pages';
				}
				if ( 'themes.php' === $parent_slug ) {
					$submenu_prefix = 'Appearance';
				}
				if ( 'plugins.php' === $parent_slug ) {
					$submenu_prefix = 'Plugins';
				}
				if ( 'users.php' === $parent_slug ) {
					$submenu_prefix = 'Users';
				}
				if ( 'tools.php' === $parent_slug ) {
					$submenu_prefix = 'Tools';
				}
				if ( 'options-general.php' === $parent_slug ) {
					$submenu_prefix = 'Settings';
				}

				if ( '' !== $submenu_prefix ) {
					$submenu_prefix .= ' / ';
				}

				// The submenu pages are grouped in sub-arrays under the parent slug, hence the extra loop.
				foreach ( $admin_submenu_items as $menu_order => $admin_submenu_item ) {

					$submenu_title = $submenu_prefix . $admin_submenu_item[0];
					$submenu_url   = $admin_submenu_item[2];

					// When dealing with a submenu URL, if there isn't a .php suffix,
					// then the full URL is built based on the parent slug.
					if ( false === strpos( $submenu_url, '.php' ) ) {
						$submenu_url = $parent_slug . '?page=' . $admin_submenu_item[2];
					}

					// If "Add" is present, we need to append the post type name to the title for context.
					if ( false !== strpos( $submenu_title, 'Add' ) && 0 !== strpos( $submenu_url, 'post_type=' ) ) {

						$equal_position = strpos( $submenu_url, '=' );

						if ( false !== $equal_position ) {
							$submenu_post_type_slug = substr( $submenu_url, $equal_position + 1 );

							$submenu_title .= ' ' . ucfirst( $submenu_post_type_slug );
						}
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

					if ( false !== $span_position ) {
						$submenu_title = substr( $submenu_title, 0, $span_position );
					}

					$admin_pages[] = new Data_Template( $submenu_title, 'Admin Page', '', $submenu_url );

				} // End foreach().
			} // End foreach().
		} // End if().

		set_transient( $this->admin_pages_transient_name, $admin_pages, 24 * HOUR_IN_SECONDS );

		return $admin_pages;
	}

	public function clear_admin_menu_pages_transient() {
		delete_transient( $this->admin_pages_transient_name );
	}
}
