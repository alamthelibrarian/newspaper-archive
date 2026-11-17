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
 * Members-only PDFs live in a protected uploads subdirectory that only this
 * plugin can serve. They stay there after uninstall, so they are not exposed,
 * but they are no longer reachable from the site. To move them back to their
 * normal Media Library locations (making them public), also define:
 *
 *     define( 'NEWSPAPER_ARCHIVE_RELEASE_FILES', true );
 *
 * @package NewspaperArchive
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once __DIR__ . '/includes/storage.php';

delete_transient( 'newspaper_archive_years' );
wp_clear_scheduled_hook( 'newspaper_archive_migrate' );
delete_option( 'newspaper_archive_storage_version' );
delete_option( 'newspaper_archive_migration_cursor' );
delete_option( 'newspaper_archive_migration_failures' );

if ( defined( 'NEWSPAPER_ARCHIVE_RELEASE_FILES' ) && true === NEWSPAPER_ARCHIVE_RELEASE_FILES ) {
	newspaper_archive_release_all_files();
}

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
