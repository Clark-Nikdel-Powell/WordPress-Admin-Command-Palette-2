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

	public $search_keyword_raw = '';
	public $post_types = array();
	public $search_keyword = '';

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
			'callback' => [ $this, 'get_search_results' ],
		] );

		register_rest_route( $this->plugin_slug . '/v1', 'help', [
			'methods'  => WP_REST_Server::READABLE,
			'callback' => [ $this, 'get_help_data' ],
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

			wp_localize_script( $this->plugin_slug . '-admin-script', 'acp_object', array(
					'api_nonce'      => wp_create_nonce( 'wp_rest' ),
					'api_search_url' => site_url( '/wp-json/' . $this->plugin_slug . '/v1/search/' ),
					'api_help_url'   => site_url( '/wp-json/' . $this->plugin_slug . '/v1/help/' ),
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

	public function get_search_post_types() {

		$post_types         = [];
		$search_keyword_arr = explode( ' ', $this->search_keyword_raw );

		foreach ( $search_keyword_arr as $search_word ) {

			if ( false !== strpos( $search_word, ':' ) ) {
				$post_types[] = str_replace( ':', '', $search_word );
			}
		}

		$this->post_types = $post_types;
	}

	public function clean_search_keyword() {

		$search_keyword = $this->search_keyword_raw;

		if ( ! empty( $this->post_types ) ) {

			foreach ( $this->post_types as $post_type ) {
				$search_keyword = str_replace( ':' . $post_type, '', $search_keyword );
			}
		}

		$this->search_keyword = $search_keyword;
	}

	public function get_search_results( WP_REST_Request $request ) {

		$this->search_keyword_raw = $request->get_param( 'search' );

		if ( empty( $this->search_keyword_raw ) ) {
			return new WP_Error( 'no_search_keyword', __( 'A search keyword was not found.', 'acp' ) );
		}

		if ( false !== strpos( $this->search_keyword_raw, ':' ) ) {
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

		$search_query = new \WP_Query( $query_args );

		if ( ! empty( $search_query->posts ) ) {

			foreach ( $search_query->posts as $result_post ) {
				$result_post->edit_url = get_edit_post_link( $result_post->ID, 'noencode' );
			}

			$results     = $search_query->posts;
			$total_count = intval( $search_query->found_posts );
		} else {
			$results     = [];
			$total_count = 0;
		}

		$return_data = [
			'results' => $results,
			'count'   => $total_count,
			'args'    => $query_args,
		];

		$response = new \WP_REST_Response( $return_data );

		return $response;
	}

	public function get_help_data() {

		$post_types_arr = array();

		$args = [
			'public' => true,
		];

		$post_types = get_post_types( $args );

		foreach ( $post_types as $post_type_name ) {
			$post_types_arr[] = $post_type_name;
		}

		$return_data = [
			'postTypes' => $post_types_arr,
		];

		$response = new \WP_REST_Response( $return_data );

		return $response;
	}
}
