<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    ACP
 * @subpackage ACP/admin/partials
 */

?>

<div>
	<h2><?php esc_html_e( 'Using the Admin Command Palette', 'acp' ); ?></h2>

	<ol>
		<li><?php esc_html_e( '"Shift+Shift" activates the Admin Command Palette modal window.', 'acp' ); ?></li>
		<li><?php esc_html_e( 'Click the overlay or ESC to close the modal window.', 'acp' ); ?></li>
		<li><?php esc_html_e( 'Use ":pt=" to filter by post type, e.g. ":pt=page pirates"', 'acp' ); ?></li>
		<li><?php esc_html_e( 'Use ":t=" to filter by taxonomy, e.g. ":t=category sandwiches"', 'acp' ); ?></li>
		<li><?php esc_html_e( 'Use ":u" to search for users, e.g. ":u Steve"', 'acp' ); ?></li>
		<li><?php esc_html_e( 'Use ":am" to search for admin menu pages, e.g. ":am Dashboard"', 'acp' ); ?></li>
		<li><?php esc_html_e( 'Use "-" to do a negative search.', 'acp' ); ?></li>
		<li><?php esc_html_e( 'Cycle through results using the TAB key.', 'acp' ); ?></li>
		<li>
			<?php esc_html_e( 'Use "/" to send a command. Available commands are:', 'acp' ); ?>
			<ol>
				<li><?php esc_html_e( '"/ap": Activate an inactive plugin.', 'acp' ); ?></li>
				<li><?php esc_html_e( '"/dp": Deactivate an inactive plugin.', 'acp' ); ?></li>
			</ol>
		</li>

	</ol>
</div>
