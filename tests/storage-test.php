<?php
/**
 * Standalone regression test for includes/storage.php with stubbed WordPress functions.
 *
 * Run: php tests/storage-test.php
 */
define( 'ABSPATH', __DIR__ . '/' );
define( 'FS_CHMOD_FILE', 0644 );
$GLOBALS['meta'] = array();
$GLOBALS['base'] = sys_get_temp_dir() . '/na-test-' . getmypid() . '/uploads';
function wp_upload_dir() { return array( 'basedir' => $GLOBALS['base'], 'error' => false ); }
function get_post_meta( $id, $k, $s ) { return $GLOBALS['meta'][ $id ][ $k ] ?? ''; }
function update_post_meta( $id, $k, $v ) { $GLOBALS['meta'][ $id ][ $k ] = $v; return true; }
function delete_post_meta( $id, $k ) { unset( $GLOBALS['meta'][ $id ][ $k ] ); return true; }
function wp_normalize_path( $p ) { return str_replace( '\\', '/', $p ); }
function trailingslashit( $p ) { return rtrim( $p, '/\\' ) . '/'; }
function untrailingslashit( $p ) { return rtrim( $p, '/\\' ); }
function wp_mkdir_p( $d ) { return is_dir( $d ) || mkdir( $d, 0777, true ); }
function sanitize_file_name( $f ) { return preg_replace( '/[^A-Za-z0-9._-]/', '', $f ); }
function wp_generate_password( $n ) { return substr( md5( mt_rand() ), 0, $n ); }
function wp_basename( $p ) { return basename( $p ); }
function absint( $v ) { return abs( (int) $v ); }
function get_attached_file( $id ) { return $GLOBALS['base'] . '/' . get_post_meta( $id, '_wp_attached_file', true ); }
function wp_get_attachment_metadata( $id ) { return $GLOBALS['meta'][ $id ]['_wp_attachment_metadata'] ?? false; }
function wp_update_attachment_metadata( $id, $m ) { $GLOBALS['meta'][ $id ]['_wp_attachment_metadata'] = $m; }
function wp_generate_attachment_metadata( $id, $file ) {
	$GLOBALS['regenerated'][] = $file;
	file_put_contents( dirname( $file ) . '/' . pathinfo( $file, PATHINFO_FILENAME ) . '-pdf.jpg', 'jpg' );
	return array( 'sizes' => array( 'full' => array( 'file' => pathinfo( $file, PATHINFO_FILENAME ) . '-pdf.jpg' ) ) );
}
function get_posts( $a ) { return array_keys( array_filter( $GLOBALS['meta'], function ( $m ) use ( $a ) { return isset( $m[ $a['meta_key'] ] ); } ) ); }
class FS { function put_contents( $f, $c ) { return false !== file_put_contents( $f, $c ); } function move( $a, $b ) { return rename( $a, $b ); } function delete( $f ) { return unlink( $f ); } }
function WP_Filesystem() { $GLOBALS['wp_filesystem'] = new FS(); return true; }
require dirname( __DIR__ ) . '/includes/storage.php';

$fails = 0;
function check( $label, $cond ) { global $fails; echo ( $cond ? 'PASS ' : 'FAIL ' ) . $label . "\n"; $fails += $cond ? 0 : 1; }

$base = $GLOBALS['base'];
mkdir( "$base/2026/11", 0777, true );
file_put_contents( "$base/2026/11/koran.pdf", '%PDF' );
file_put_contents( "$base/2026/11/koran-pdf.jpg", 'jpg' );
file_put_contents( "$base/2026/11/koran-pdf-150x150.jpg", 'jpg' );
$GLOBALS['meta'][7] = array(
	'_wp_attached_file'       => '2026/11/koran.pdf',
	'_wp_attachment_metadata' => array( 'sizes' => array( 'full' => array( 'file' => 'koran-pdf.jpg' ), 'thumbnail' => array( 'file' => 'koran-pdf-150x150.jpg' ) ) ),
);

check( 'public->members succeeds', newspaper_archive_set_file_access( 7, 'members' ) );
check( 'pdf moved into private dir', is_file( "$base/newspaper-archive-private/7-koran.pdf" ) && ! is_file( "$base/2026/11/koran.pdf" ) );
check( 'previews deleted', ! is_file( "$base/2026/11/koran-pdf.jpg" ) && ! is_file( "$base/2026/11/koran-pdf-150x150.jpg" ) );
check( 'preview metadata cleared', array() === wp_get_attachment_metadata( 7 )['sizes'] );
check( 'deny files written', is_file( "$base/newspaper-archive-private/.htaccess" ) && is_file( "$base/newspaper-archive-private/web.config" ) );
check( 'members again is idempotent', newspaper_archive_set_file_access( 7, 'members' ) && is_file( "$base/newspaper-archive-private/7-koran.pdf" ) );

check( 'members->public succeeds', newspaper_archive_set_file_access( 7, 'public' ) );
check( 'pdf restored', is_file( "$base/2026/11/koran.pdf" ) && '2026/11/koran.pdf' === get_post_meta( 7, '_wp_attached_file', true ) );
check( 'previews regenerated', is_file( "$base/2026/11/koran-pdf.jpg" ) && ! get_post_meta( 7, '_newspaper_archive_previews_removed', true ) );
check( 'public again is no-op', newspaper_archive_set_file_access( 7, 'public' ) );

// 0.3.0 state: file already private, previews still public.
newspaper_archive_set_file_access( 7, 'members' );
file_put_contents( "$base/2026/11/koran-pdf.jpg", 'jpg' );
$GLOBALS['meta'][7]['_wp_attachment_metadata'] = array( 'sizes' => array( 'full' => array( 'file' => 'koran-pdf.jpg' ) ) );
check( 'migration removes leftover 0.3.0 previews', newspaper_archive_set_file_access( 7, 'members' ) && ! is_file( "$base/2026/11/koran-pdf.jpg" ) );

// Path traversal is rejected.
$GLOBALS['meta'][8] = array( '_wp_attached_file' => '../secret.pdf' );
check( 'traversal rejected', ! newspaper_archive_set_file_access( 8, 'members' ) );
$GLOBALS['meta'][9] = array( '_wp_attached_file' => 'C:/x.pdf' );
check( 'drive path rejected', ! newspaper_archive_set_file_access( 9, 'members' ) );
unset( $GLOBALS['meta'][8], $GLOBALS['meta'][9] );

// Uninstall release.
check( 'release_all restores everything', array() === newspaper_archive_release_all_files() && is_file( "$base/2026/11/koran.pdf" ) );

exec( 'rm -rf ' . escapeshellarg( dirname( $base ) ) );
exit( $fails ? 1 : 0 );
