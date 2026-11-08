<?php
/**
 * Runs when the plugin is deleted from the Plugins screen.
 *
 * By default NOTHING is removed, so deleting the plugin never destroys a
 * library's collection. To wipe issues, titles and settings on uninstall, add
 * this to wp-config.php BEFORE deleting the plugin:
 *
 *     define( 'NEWSPAPER_ARCHIVE_REMOVE_DATA', true );
 *
 * PDF files in the Media Library are never deleted.
 *
 * @package NewspaperArchive
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_transient( 'newspaper_archive_years' );

if ( ! defined( 'NEWSPAPER_ARCHIVE_REMOVE_DATA' ) || true !== NEWSPAPER_ARCHIVE_REMOVE_DATA ) {
	return;
}

$newspaper_archive_ids = get_posts(
	array(
		'post_type'      => 'newspaper_issue',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
);
foreach ( $newspaper_archive_ids as $newspaper_archive_id ) {
	wp_delete_post( $newspaper_archive_id, true );
}

register_taxonomy( 'newspaper_title', 'newspaper_issue' );
$newspaper_archive_terms = get_terms(
	array(
		'taxonomy'   => 'newspaper_title',
		'hide_empty' => false,
		'fields'     => 'ids',
	)
);
if ( ! is_wp_error( $newspaper_archive_terms ) ) {
	foreach ( $newspaper_archive_terms as $newspaper_archive_term ) {
		wp_delete_term( $newspaper_archive_term, 'newspaper_title' );
	}
}
