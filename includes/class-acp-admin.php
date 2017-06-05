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

		register_rest_route( $this->plugin_slug . '/v1', 'data', [
			'methods'  => WP_REST_Server::READABLE,
			'callback' => [ $this, 'get_search_results' ],
		] );
	}

	public function parse_post_type_search_option( $search_keyword_raw ) {


	}

	public function get_search_results( WP_REST_Request $request ) {

		$search_keyword_raw = $request->get_param( 'search' );

		if ( empty( $search_keyword_raw ) ) {
			return new WP_Error( 'no_search_keyword', __( 'A search keyword was not found.', 'acp' ) );
		}

		$post_type_statement = "AND post_type != 'revision'";

		if ( 0 === strpos( $search_keyword_raw, ':' ) ) {

			$first_space_pos = strpos( $search_keyword_raw, ' ' );
			$post_types_str  = substr( $search_keyword_raw, 1, $first_space_pos - 1 );
			$search_keyword  = substr( $search_keyword_raw, $first_space_pos + 1 );

			if ( false !== strpos( $post_types_str, ',' ) ) {

				$post_types_arr = explode( ',', $post_types_str );

				foreach ( $post_types_arr as $post_type_index => $post_type ) {
					$post_types_arr[ $post_type_index ] = "post_type = '$post_type'";
				}

				$post_type_statement = ' AND ( ' . implode( ' OR ', $post_types_arr ) . ' )';
			} else {
				$post_type_statement = " AND post_type = '$post_types_str'";
			}
		} else {
			$search_keyword = $search_keyword_raw;
		}

		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT ID, post_title, post_type FROM {$wpdb->prefix}posts WHERE post_title LIKE %s $post_type_statement LIMIT 10",
			'%' . $wpdb->esc_like( $search_keyword ) . '%'
		);

		$results = $wpdb->get_results( $query );

		foreach ( $results as $result ) {
			$result->edit_url = get_edit_post_link( $result->ID, 'noencode' );
		}

		$total_count_arr = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, post_title, post_type FROM {$wpdb->prefix}posts WHERE post_title LIKE %s $post_type_statement",
				'%' . $wpdb->esc_like( $search_keyword ) . '%'
			)
		);

		$total_count = count( $total_count_arr );

		$return_data = [
			'results' => $results,
			'count'   => $total_count,
		];

		$response = new \WP_REST_Response( $return_data );

		return $response;
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
					'api_nonce' => wp_create_nonce( 'wp_rest' ),
					'api_url'   => site_url( '/wp-json/' . $this->plugin_slug . '/v1/data/' ),
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
}
