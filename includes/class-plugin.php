<?php
namespace ACP;
/**
 * ACP
 *
 *
 * @package   Admin Command Palette
 * @author    jhned
 * @license   GPL-3.0
 */

/**
 * @package ACP
 */
class Plugin {

	/**
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * plugin file.
	 *
	 * @since    2.0.0
	 * @var      string
	 */
	protected $plugin_slug = 'acp';

	/**
	 * Instance of this class.
	 *
	 * @since    2.0.0
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Setup instance attributes
	 *
	 * @since     2.0.0
	 */
	private function __construct() {
		$this->plugin_version = ADMIN_COMMAND_PALETTE_VERSION;
	}

	/**
	 * Return the plugin slug.
	 *
	 * @since    2.0.0
	 * @return   string Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Return the plugin version.
	 *
	 * @since    2.0.0
	 * @return   string Plugin version.
	 */
	public function get_plugin_version() {
		return $this->plugin_version;
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since    2.0.0
	 */
	public static function activate() {
	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    2.0.0
	 */
	public static function deactivate() {
	}


	/**
	 * Return an instance of this class.
	 *
	 * @since     2.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}
}
