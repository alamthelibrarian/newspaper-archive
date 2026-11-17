<?php
/**
 * Storage for members-only PDF files.
 *
 * Loaded by the main plugin file and by uninstall.php, so it must not register
 * hooks or depend on anything defined in the main plugin file.
 *
 * @package NewspaperArchive
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Initialize the WordPress filesystem abstraction.
 *
 * @return WP_Filesystem_Base|false Filesystem instance.
 */
function newspaper_archive_filesystem() {
	global $wp_filesystem;
	if ( ! function_exists( 'WP_Filesystem' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
	if ( ! WP_Filesystem() ) {
		return false;
	}
	return $wp_filesystem;
}

/**
 * Ensure the private PDF directory has access-denial files for Apache and IIS.
 *
 * Nginx does not read .htaccess; its server configuration must deny this
 * directory separately (documented in SECURITY.md).
 *
 * @return string|false Absolute private directory, or false on failure.
 */
function newspaper_archive_private_dir() {
	$uploads = wp_upload_dir();
	if ( ! empty( $uploads['error'] ) || empty( $uploads['basedir'] ) ) {
		return false;
	}
	$directory = trailingslashit( $uploads['basedir'] ) . 'newspaper-archive-private';
	if ( ! is_dir( $directory ) && ! wp_mkdir_p( $directory ) ) {
		return false;
	}
	$filesystem = newspaper_archive_filesystem();
	if ( ! $filesystem ) {
		return false;
	}
	$rules       = "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nOrder Allow,Deny\nDeny from all\n</IfModule>\nOptions -Indexes\n";
	$deny        = trailingslashit( $directory ) . '.htaccess';
	$web_config  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
		'<configuration><system.webServer><authorization><deny users="*" /></authorization></system.webServer></configuration>';
	$config_path = trailingslashit( $directory ) . 'web.config';
	$index       = trailingslashit( $directory ) . 'index.php';
	if ( ( ! file_exists( $deny ) && ! $filesystem->put_contents( $deny, $rules, FS_CHMOD_FILE ) ) ||
		( ! file_exists( $config_path ) && ! $filesystem->put_contents( $config_path, $web_config, FS_CHMOD_FILE ) ) ||
		( ! file_exists( $index ) && ! $filesystem->put_contents( $index, "<?php\n// Silence is golden.\n", FS_CHMOD_FILE ) )
	) {
		return false;
	}
	return $directory;
}

/**
 * Check whether another issue already uses this attachment.
 *
 * @param int $attachment_id Attachment ID.
 * @param int $exclude_post_id Issue ID to exclude.
 * @return bool
 */
function newspaper_archive_attachment_in_use( $attachment_id, $exclude_post_id = 0 ) {
	$args = array(
		'post_type'      => 'newspaper_issue',
		'post_status'    => array( 'publish', 'private', 'draft', 'pending', 'future' ),
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'post__not_in'   => array( absint( $exclude_post_id ) ),
		'meta_key'       => '_newspaper_archive_pdf_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- one-row attachment ownership check.
		'meta_value'     => absint( $attachment_id ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- one-row attachment ownership check.
	);
	return (bool) get_posts( $args );
}

/**
 * Whether an uploads-relative path is safe to join to the uploads directory.
 *
 * @param string $relative Uploads-relative path.
 * @return bool
 */
function newspaper_archive_is_safe_relative_path( $relative ) {
	return '' !== $relative
		&& 0 !== strpos( $relative, '/' )
		&& ! preg_match( '/^[a-zA-Z]:/', $relative )
		&& ! preg_match( '/(^|\\/)\\.\\.(\\/|$)/', $relative );
}

/**
 * Delete the preview images WordPress generated for a PDF.
 *
 * WordPress renders the first page of a PDF into JPEG previews stored next to
 * the original upload. Those images would keep the cover of a members-only
 * issue publicly reachable, so they are removed while the PDF is protected and
 * regenerated when it becomes public again.
 *
 * @param int    $attachment_id Attachment ID.
 * @param string $relative      Uploads-relative path of the PDF's public location.
 * @return bool Whether no preview image remains on disk.
 */
function newspaper_archive_remove_previews( $attachment_id, $relative ) {
	$metadata = wp_get_attachment_metadata( $attachment_id );
	if ( ! is_array( $metadata ) || empty( $metadata['sizes'] ) || ! is_array( $metadata['sizes'] ) ) {
		return true;
	}
	$uploads    = wp_upload_dir();
	$filesystem = newspaper_archive_filesystem();
	if ( ! $filesystem || ! empty( $uploads['error'] ) || empty( $uploads['basedir'] ) ) {
		return false;
	}
	$directory = dirname( $relative );
	$directory = trailingslashit( $uploads['basedir'] ) . ( '.' === $directory ? '' : $directory );
	$remaining = array();
	foreach ( $metadata['sizes'] as $size => $data ) {
		if ( empty( $data['file'] ) ) {
			continue;
		}
		$file = trailingslashit( $directory ) . wp_basename( $data['file'] );
		if ( file_exists( $file ) ) {
			$filesystem->delete( $file );
		}
		if ( file_exists( $file ) ) {
			$remaining[ $size ] = $data;
		}
	}
	$metadata['sizes'] = $remaining;
	wp_update_attachment_metadata( $attachment_id, $metadata );
	update_post_meta( $attachment_id, '_newspaper_archive_previews_removed', 1 );
	return empty( $remaining );
}

/**
 * Regenerate the preview images of a PDF that has returned to public storage.
 *
 * @param int    $attachment_id Attachment ID.
 * @param string $file          Absolute path of the PDF.
 */
function newspaper_archive_restore_previews( $attachment_id, $file ) {
	if ( ! get_post_meta( $attachment_id, '_newspaper_archive_previews_removed', true ) ) {
		return;
	}
	if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
	}
	$metadata = wp_generate_attachment_metadata( $attachment_id, $file );
	if ( is_array( $metadata ) ) {
		wp_update_attachment_metadata( $attachment_id, $metadata );
	}
	delete_post_meta( $attachment_id, '_newspaper_archive_previews_removed' );
}

/**
 * Move an attachment into or out of private storage as its issue access changes.
 *
 * The original uploads-relative path is retained in attachment metadata so a
 * public PDF can be restored to its normal Media Library URL.
 *
 * @param int    $attachment_id Attachment ID.
 * @param string $access        'members' or 'public'.
 * @return bool Whether the requested storage transition succeeded.
 */
function newspaper_archive_set_file_access( $attachment_id, $access ) {
	$attachment_id = absint( $attachment_id );
	$uploads       = wp_upload_dir();
	if ( ! $attachment_id || ! empty( $uploads['error'] ) || empty( $uploads['basedir'] ) ) {
		return false;
	}
	$filesystem = newspaper_archive_filesystem();
	if ( ! $filesystem ) {
		return false;
	}
	$base_dir = wp_normalize_path( untrailingslashit( $uploads['basedir'] ) );
	$relative = wp_normalize_path( (string) get_post_meta( $attachment_id, '_wp_attached_file', true ) );
	$original = wp_normalize_path( (string) get_post_meta( $attachment_id, '_newspaper_archive_original_file', true ) );

	if ( 'members' === $access ) {
		if ( $original && 0 === strpos( $relative, 'newspaper-archive-private/' ) ) {
			// Already protected; files moved by 0.3.0 may still have public previews.
			$directory = newspaper_archive_private_dir();
			return $directory
				&& newspaper_archive_is_safe_relative_path( $original )
				&& is_readable( get_attached_file( $attachment_id ) )
				&& newspaper_archive_remove_previews( $attachment_id, $original );
		}
		if ( ! newspaper_archive_is_safe_relative_path( $relative ) ) {
			return false;
		}
		$source = trailingslashit( $base_dir ) . $relative;
		$real   = realpath( $source );
		if ( ! $real || 0 !== strpos( wp_normalize_path( $real ), trailingslashit( $base_dir ) ) || ! is_readable( $real ) ) {
			return false;
		}
		$directory = newspaper_archive_private_dir();
		if ( ! $directory || ! newspaper_archive_remove_previews( $attachment_id, $relative ) ) {
			return false;
		}
		$filename = $attachment_id . '-' . sanitize_file_name( basename( $relative ) );
		$target   = trailingslashit( $directory ) . $filename;
		if ( file_exists( $target ) && wp_normalize_path( $real ) !== wp_normalize_path( $target ) ) {
			$filename = $attachment_id . '-' . wp_generate_password( 8, false ) . '-' . sanitize_file_name( basename( $relative ) );
			$target   = trailingslashit( $directory ) . $filename;
		}
		if ( ! $filesystem->move( $real, $target, false ) ) {
			return false;
		}
		update_post_meta( $attachment_id, '_newspaper_archive_original_file', $relative );
		update_post_meta( $attachment_id, '_wp_attached_file', 'newspaper-archive-private/' . $filename );
		return true;
	}

	if ( ! $original ) {
		return true;
	}
	if ( ! newspaper_archive_is_safe_relative_path( $original ) ) {
		return false;
	}
	$target     = trailingslashit( $base_dir ) . $original;
	$source     = trailingslashit( $base_dir ) . $relative;
	$target_dir = dirname( $target );
	if ( ! is_dir( $target_dir ) && ! wp_mkdir_p( $target_dir ) ) {
		return false;
	}
	if ( file_exists( $source ) ) {
		if ( file_exists( $target ) || ! $filesystem->move( $source, $target, false ) ) {
			return false;
		}
	} elseif ( ! file_exists( $target ) ) {
		return false;
	}
	update_post_meta( $attachment_id, '_wp_attached_file', $original );
	delete_post_meta( $attachment_id, '_newspaper_archive_original_file' );
	newspaper_archive_restore_previews( $attachment_id, $target );
	return true;
}

/**
 * Move every protected PDF back to its normal Media Library location.
 *
 * Used on uninstall when NEWSPAPER_ARCHIVE_RELEASE_FILES is true. This makes
 * members-only PDFs publicly reachable again at their original URLs.
 *
 * @return int[] IDs of attachments that could not be restored.
 */
function newspaper_archive_release_all_files() {
	$failed = array();
	$ids    = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_key'       => '_newspaper_archive_original_file', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- one-off uninstall task.
		)
	);
	foreach ( $ids as $id ) {
		if ( ! newspaper_archive_set_file_access( $id, 'public' ) ) {
			$failed[] = $id;
		}
	}
	return $failed;
}
