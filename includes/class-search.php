<?php
namespace ACP;

class Search {

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

		// Register the REST API route.
		add_action( 'rest_api_init', [ $this, 'register_rest_route' ], 20 );
	}

	/**
	 * Register the REST API route.
	 */
	public function register_rest_route() {

		register_rest_route( $this->plugin_slug . '/v1', 'search', [
			'methods'  => \WP_REST_Server::READABLE,
			'callback' => [ $this, 'determine_command_type' ],
		] );
	}

	/**
	 * determine_command_type
	 *
	 * This is the entry point from the JavaScript. The React app passes the request back to this function,
	 * where we then figure out what type of command type we're dealing with. Most of the time it'll be a
	 * search, but there are special commands for activating/deactivating plugins.
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function determine_command_type( \WP_REST_Request $request ) {

		$this->command_raw = $request->get_param( 'command' );

		if ( empty( $this->command_raw ) ) {
			return new \WP_Error( 'no_command_found', __( 'A command was not found.', 'acp' ) );
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
		}

		$this->clean_search_keyword();

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

				$results[] = new Data_Template( $title, $post_type, $subtitle, $url );
			}

			$total_count = intval( $search_query->found_posts );
		}

		$this->return_data = [
			'results' => $results,
			'count'   => $total_count,
			'args'    => $query_args,
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
	 * Parses the search keyword for post types, taxonomies or users.
	 * ":pt" for post type, e.g. ":pt=page".
	 * ":t" for taxonomy, e.g. ":t=category".
	 * ":u" for user, e.g. ":u". No equal sign required.
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
}
