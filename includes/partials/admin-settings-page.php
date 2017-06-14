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

// Set up data
$post_types = get_post_types( array(), 'objects' );
$taxonomies = get_taxonomies( array(), 'objects' );

$included_post_types = get_option( 'acp_included_post_types' );
$included_taxonomies = get_option( 'acp_included_taxonomies' );

// Nav menu items are not editable like other post types
unset( $post_types['nav_menu_item'] );
unset( $post_types['revision'] );

?>

<div>

	<h2>Admin Command Palette Settings</h2>

	<form action="options.php" method="post">

		<?php settings_fields( 'acp_options' ); ?>

		<?php
		/*//////////////////////////////////////////////////////////////////////////////
		//  General Settings  /////////////////////////////////////////////////////////
		////////////////////////////////////////////////////////////////////////////*/
		?>

		<table class="form-table">
			<tbody>
			<?php // Included Post Types ?>
			<tr>
				<th scope="row">
					<label>Included Post Types</label>
				</th>
				<td>

					<?php
					foreach ( $post_types as $post_type_slug => $post_type ) {

						$checked = '';

						if ( '' === $included_post_types || empty( $included_post_types ) ) {

							if ( 'page' === $post_type_slug || 'post' === $post_type_slug ) {
								$checked = 'checked';
							}
						} else {
							if ( isset( $included_post_types[ $post_type_slug ] ) && '1' === $included_post_types[ $post_type_slug ] ) {
								$checked = 'checked';
							}
						}

						// Add count number to label
						$post_type_counts      = wp_count_posts( $post_type->name, 'readable' );
						$post_type_count       = $post_type_counts->publish;
						$post_type_count_label = 'Published';

						if ( 'attachment' === $post_type_slug ) {
							$post_type_count       = $post_type_counts->inherit;
							$post_type_count_label = 'Uploaded';
						}
						?>
						<p><label>
								<input type="checkbox" name="acp_included_post_types[<?php echo esc_attr( $post_type_slug ); ?>]" value="1" <?php echo esc_attr( $checked ); ?> />
								<?php echo esc_html( $post_type->labels->name ); ?>
								<?php if ( '' !== $post_type_count && '' !== $post_type_count_label ) { ?>
									<em class="count">(<?php echo esc_html( $post_type_count . ' ' . $post_type_count_label ); ?>)</em>
								<?php } ?>
							</label></p>
					<?php } ?>
				</td>
			</tr>

			<?php // Included Taxonomies ?>
			<tr>
				<th scope="row">
					<label>Included Taxonomies</label>
				</th>
				<td>
					<?php
					foreach ( $taxonomies as $taxonomy_slug => $taxonomy ) {

						$checked = '';

						if ( '' === $included_taxonomies || empty( $included_taxonomies ) ) {

							if ( 'category' === $taxonomy_slug || 'post_tag' === $taxonomy_slug ) {
								$checked = 'checked';
							}
						} else {

							if ( isset( $included_taxonomies[ $taxonomy_slug ] ) && '1' === $included_taxonomies[ $taxonomy_slug ] ) {
								$checked = 'checked';
							}
						}

						// Add count number to label
						$taxonomy_count = wp_count_terms( $taxonomy_slug );

						?>
						<p><label>
								<input type="checkbox" name="acp_included_taxonomies[<?php echo esc_attr( $taxonomy_slug ); ?>]" value="1" <?php echo esc_attr( $checked ); ?> />
								<?php echo esc_html( $taxonomy->labels->name ); ?>
								<em class="count">(<?php echo esc_html( $taxonomy_count ); ?>)</em>
							</label></p>
					<?php } ?>
				</td>
			</tr>

			</tbody>
		</table>

		<p>
			<input class="button button-primary" name="Submit" type="submit" value="<?php esc_attr_e( 'Save Changes' ); ?>"/>
		</p>

	</form>
</div>
