<?php
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


require_once( plugin_dir_path( __FILE__ ) . 'includes/class-acp.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/class-acp-admin.php' );

/**
 * Initialize Plugin
 *
 * @since 0.8.0
 */
function admin_command_palette_init() {
	$wpr = ACP::get_instance();
	$wpr_admin = ACP_Admin::get_instance();
}
add_action( 'plugins_loaded', 'admin_command_palette_init' );

/**
 * Register activation and deactivation hooks
 */
register_activation_hook( __FILE__, array( 'ACP', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'ACP', 'deactivate' ) );

