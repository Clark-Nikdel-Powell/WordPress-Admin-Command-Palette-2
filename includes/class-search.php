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

	public $results_max = 10;
	public $command_raw = '';
	public $command_type = '';
	public $action_name = '';
	public $filter_name = '';
	public $content_types = [];
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

		if ( false !== strpos( $this->command_raw, ':' ) ) {
			$this->command_type = 'filtered_search';
			$this->determine_filter();
			$this->run_search_queries();
		}

		if ( '' === $this->command_type ) {
			$this->command_type   = 'search';
			$this->search_keyword = $this->command_raw;
			$this->run_search_queries();
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

				$plugin_links[] = new Data_Template( $plugin_array['Name'], 'plugin', '', $url );
			}
			if ( 'deactivate_plugin' === $this->action_name && is_plugin_active( $plugin_file ) ) {
				$url = str_replace( '&amp;', '&', wp_nonce_url( 'plugins.php?action=deactivate&amp;plugin=' . $plugin_file . '&amp;plugin_status=all&amp;paged=1&amp;s=', 'deactivate-plugin_' . $plugin_file ) );

				$plugin_links[] = new Data_Template( $plugin_array['Name'], 'plugin', '', $url );
			}
		}

		$this->return_data = [
			'results' => $plugin_links,
			'count'   => count( $plugin_links ),
		];
	}

	/*
	 * determine_filter
	 *
	 * Figures out which filtered search we're using.
	 * ":pt" for post type, e.g. ":pt=page".
	 * ":t" for taxonomy, e.g. ":t=category".
	 * ":u" for user, e.g. ":u". No equal sign required.
	 * ":am" for admin menu, e.g. ":am". No equal sign required.
	 */
	public function determine_filter() {

		// e.g., ":pt=page what" or ":pt=page,post what"
		if ( false !== strpos( $this->command_raw, ':pt=' ) ) {
			$this->filter_name = ':pt=';
			$this->get_search_content_types();
		}
		if ( false !== strpos( $this->command_raw, ':t=' ) ) {
			$this->filter_name = ':t=';
			$this->get_search_content_types();
		}
		if ( false !== strpos( $this->command_raw, ':u' ) ) {
			$this->filter_name = ':u';
		}
		if ( false !== strpos( $this->command_raw, ':am' ) ) {
			$this->filter_name = ':am';
		}

		if ( '' !== $this->filter_name ) {
			$this->clean_search_keyword();
		}
	}

	/**
	 * get_search_content_types
	 *
	 * Parses the search keyword for post types, taxonomies or users.
	 */
	public function get_search_content_types() {

		$content_types = [];

		/**
		 * E.g., ":pt=page,post what is love?" becomes:
		 * array(
		 *  :pt=page,post,
		 *  what,
		 *  is,
		 *  love?,
		 * )
		 */
		$search_keyword_arr = explode( ' ', $this->command_raw );

		foreach ( $search_keyword_arr as $search_word ) {

			// Match found for :pt=page,post.
			if ( false !== strpos( $search_word, $this->filter_name ) ) {

				// Now shortened to page,post.
				$content_types_str = str_replace( $this->filter_name, '', $search_word );

				// E.g., "page,post" or just "page".
				if ( false !== strpos( ',', $content_types_str ) ) {

					// Now "page,post" becomes array(page, post).
					$content_types_arr = explode( ',', $content_types_str );

					// Added with a foreach in case there are multiple filters in the raw command.
					// E.g., ":pt=page :pt=post what is love?"
					foreach ( $content_types_arr as $content_type ) {
						$content_types[] = $content_type;
					}
				} else {
					// E.g., page.
					$content_types[] = $content_types_str;
				}
			}
		}

		$this->content_types = $content_types;
	}

	/**
	 * Remove filters from the search keyword.
	 * E.g., input is ":pt=page,post what is love?" for a post-type search.
	 * Output is "what is love?".
	 */
	public function clean_search_keyword() {

		$search_keyword = $this->command_raw;

		// Replace the filter name first. e.g., ":pt=".
		if ( '' !== $this->filter_name ) {
			$search_keyword = str_replace( $this->filter_name, '', $search_keyword );
		}

		// Replace any content types. e.g., "page,post".
		if ( ! empty( $this->content_types ) ) {

			foreach ( $this->content_types as $content_type ) {
				$search_keyword = str_replace( [ $content_type, $content_type . ',' ], '', $search_keyword );
			}
		}

		$this->search_keyword = trim( $search_keyword );
	}

	/**
	 * run_search_queries
	 *
	 * Builds search queries to return data based on the search.
	 */
	public function run_search_queries() {

		if ( ':am' === $this->filter_name || 'search' === $this->command_type ) {
			$this->run_admin_menu_pages_query();
		}
		if ( ':u' === $this->filter_name || 'search' === $this->command_type ) {
			$this->run_user_query();
		}
		if ( ':t=' === $this->filter_name || 'search' === $this->command_type ) {
			$this->run_taxonomy_term_query();
		}
		if ( ':pt=' === $this->filter_name || 'search' === $this->command_type ) {
			$this->run_posts_query();
		}

		// Only reorder/cap results if we have a search keyword. Searches like ":am" can return all admin pages without a search keyword.
		if ( '' !== $this->search_keyword ) {

			$local_search_keyword = $this->search_keyword;

			// Now do some sorting with the Levenshtein function...
			usort( $this->return_data['results'], function ( $a, $b ) use ( $local_search_keyword ) {
				$lev_a = levenshtein( $local_search_keyword, $a->title );
				$lev_b = levenshtein( $local_search_keyword, $b->title );

				return $lev_a === $lev_b ? 0 : ( $lev_a > $lev_b ? 1 : - 1 );
			} );

			// And then cap the results at 10...
			$this->return_data['results'] = array_slice( $this->return_data['results'], 0, $this->results_max, true );
		}
	}

	public function run_admin_menu_pages_query() {

		$admin_menu_pages = get_transient( 'acp_admin_pages' );

		if ( ! empty( $admin_menu_pages ) ) {

			// Only filter if we have a search keyword, otherwise return all the admin pages.
			if ( '' !== $this->search_keyword ) {

				$local_search_keyword = $this->search_keyword;

				$callback = function ( $admin_menu_page_obj ) use ( $local_search_keyword ) {

					$strpos_check = stripos( $admin_menu_page_obj->title, $local_search_keyword );

					if ( false !== $strpos_check ) {
						return true;
					}
				};

				$admin_menu_pages = array_filter( $admin_menu_pages, $callback );
			}

			$this->return_data['results'] = array_merge( $this->return_data['results'], $admin_menu_pages );
			$this->return_data['count']   = count( $this->return_data['results'] );
		}
	}

	public function run_user_query() {

		$query_args = [
			'search' => '*' . esc_attr( $this->search_keyword ) . '*',
			'number' => $this->results_max,
		];

		$users_search_query = new \WP_User_Query( $query_args );

		if ( ! empty( $users_search_query->results ) ) {

			foreach ( $users_search_query->results as $user ) {

				$title       = $user->data->display_name;
				$object_type = 'user';
				$subtitle    = '';
				$url         = get_edit_user_link( $user->data->ID );

				$this->return_data['results'][] = new Data_Template( $title, $object_type, $subtitle, $url );
			}

			$this->return_data['count'] += $users_search_query->total_users;
		}
	}

	public function run_taxonomy_term_query() {

		$query_args = [
			'search' => $this->search_keyword,
			'number' => $this->results_max,
		];

		if ( ':t=' === $this->filter_name && ! empty( $this->content_types ) ) {
			$query_args['taxonomy'] = $this->content_types;
		} else {
			$query_args['taxonomy'] = get_taxonomies( [
				'public' => true,
			] );
		}

		$taxonomy_term_search_query = new \WP_Term_Query( $query_args );

		if ( ! empty( $taxonomy_term_search_query->terms ) ) {

			foreach ( $taxonomy_term_search_query->terms as $term ) {

				$taxonomy_obj         = get_taxonomy( $term->taxonomy );
				$taxonomy_post_object = '';

				if ( ! empty( $taxonomy_obj->object_type ) ) {

					if ( is_array( $taxonomy_obj->object_type ) ) {
						$taxonomy_post_object = $taxonomy_obj->object_type[0];
					} else {
						$taxonomy_post_object = $taxonomy_obj->object_type;
					}
				}

				$title       = $term->name;
				$object_type = $term->taxonomy;
				$subtitle    = '';
				$url         = get_edit_term_link( $term->term_id, $term->taxonomy, $taxonomy_post_object );

				$this->return_data['results'][] = new Data_Template( $title, $object_type, $subtitle, $url );
			}

			$this->return_data['count'] += count( $taxonomy_term_search_query->terms );
		}
	}

	public function run_posts_query() {

		$query_args = [
			's'                      => $this->search_keyword,
			'posts_per_page'         => $this->results_max,
			'update_post_meta_cache' => true,
		];

		if ( ':pt=' === $this->filter_name && ! empty( $this->content_types ) ) {
			$query_args['post_type'] = $this->content_types;
		}

		$posts_search_query = new \WP_Query( $query_args );

		if ( ! empty( $posts_search_query->posts ) ) {

			foreach ( $posts_search_query->posts as $result_post ) {

				$title       = $result_post->post_title;
				$object_type = $result_post->post_type;
				$subtitle    = '';
				$url         = get_edit_post_link( $result_post->ID, 'noencode' );

				if ( is_post_type_hierarchical( $result_post->post_type ) ) {
					$subtitle = $this->get_hierarchical_subtitle( $result_post );
				}

				$this->return_data['results'][] = new Data_Template( $title, $object_type, $subtitle, $url );
			}

			$this->return_data['count'] += $posts_search_query->found_posts;
		}
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
}
