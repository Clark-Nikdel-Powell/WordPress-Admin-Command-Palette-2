<?php
namespace ACP;
/**
 * Admin Command Palette
 *
 *
 * @package   Admin Command Palette
 * @author    Josh Nederveld
 * @license   GPL-3.0
 *
 * @wordpress-plugin
 * Plugin Name:       Admin Command Palette
 * Description:       React-powered WordPress Admin live search.
 * Version:           2.0.0
 * Author:            jhned
 * Text Domain:       acp
 * License:           GPL-3.0
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.txt
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'ADMIN_COMMAND_PALETTE_VERSION', '2.0.0' );


require_once( plugin_dir_path( __FILE__ ) . 'includes/class-plugin.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/class-admin.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/class-search.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/class-data-template.php' );

/**
 * Initialize Plugin
 *
 * @since 0.8.0
 */
function admin_command_palette_init() {
	$acp = Plugin::get_instance();
	$acp_admin = Admin::get_instance();
	$acp_search = Search::get_instance();
}
add_action( 'plugins_loaded', 'ACP\admin_command_palette_init' );

/**
 * Register activation and deactivation hooks
 */
register_activation_hook( __FILE__, array( 'ACP', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'ACP', 'deactivate' ) );

