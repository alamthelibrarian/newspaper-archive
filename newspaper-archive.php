<?php
/**
 * Plugin Name:       Newspaper Archive
 * Plugin URI:        https://github.com/alamthelibrarian/newspaper-archive
 * Description:       Manage and display a library's digital newspaper collection (PDF): issue metadata, search, title and year filters, and a built-in PDF reader.
 * Version:           0.2.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Muhammad Saiful Alam
 * Author URI:        https://alam.web.id
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       newspaper-archive
 * Domain Path:       /languages
 *
 * @package NewspaperArchive
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NEWSPAPER_ARCHIVE_VERSION', '0.2.0' );
define( 'NEWSPAPER_ARCHIVE_FILE', __FILE__ );
define( 'NEWSPAPER_ARCHIVE_URL', plugin_dir_url( __FILE__ ) );

// ------------------------------------------------------------------
// 1. Helpers
// ------------------------------------------------------------------

/**
 * Read one of the plugin's meta values for an issue.
 *
 * @param int    $post_id Issue ID.
 * @param string $key     Short key: date, edition, pages, pdf_id, access.
 * @return mixed
 */
function newspaper_archive_meta( $post_id, $key ) {
	return get_post_meta( $post_id, '_newspaper_archive_' . $key, true );
}

/**
 * Sanitize a Y-m-d date; returns '' when invalid.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function newspaper_archive_sanitize_date( $value ) {
	$value = sanitize_text_field( (string) $value );
	if ( preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m ) && checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) ) {
		return $value;
	}
	return '';
}

/**
 * Sanitize the access level.
 *
 * @param mixed $value Raw value.
 * @return string 'members' or 'public'.
 */
function newspaper_archive_sanitize_access( $value ) {
	return 'members' === $value ? 'members' : 'public';
}

/**
 * Whether the current visitor may read/download the PDF of an issue.
 *
 * @param int $post_id Issue ID.
 * @return bool
 */
function newspaper_archive_can_access( $post_id ) {
	$level = newspaper_archive_meta( $post_id, 'access' );
	$can   = ( 'members' !== $level ) || is_user_logged_in();

	/**
	 * Filter whether the visitor can access an issue's PDF.
	 *
	 * @param bool $can     Default decision.
	 * @param int  $post_id Issue ID.
	 */
	return (bool) apply_filters( 'newspaper_archive_can_access', $can, $post_id );
}

/**
 * URL used to open or download an issue's PDF.
 *
 * Public issues link straight to the media file. Members-only issues go
 * through a PHP endpoint that checks access before streaming the file.
 *
 * @param int  $post_id  Issue ID.
 * @param bool $download Whether to force a download.
 * @return string
 */
function newspaper_archive_file_url( $post_id, $download = false ) {
	$pdf_id = (int) newspaper_archive_meta( $post_id, 'pdf_id' );
	if ( ! $pdf_id ) {
		return '';
	}
	if ( 'members' === newspaper_archive_meta( $post_id, 'access' ) ) {
		$args = array( 'newspaper_archive_pdf' => $post_id );
		if ( $download ) {
			$args['newspaper_archive_dl'] = 1;
		}
		return add_query_arg( $args, home_url( '/' ) );
	}
	$url = wp_get_attachment_url( $pdf_id );
	return $url ? $url : '';
}

/**
 * Distinct publication years (cached).
 *
 * @return string[]
 */
function newspaper_archive_get_years() {
	$years = get_transient( 'newspaper_archive_years' );
	if ( false === $years ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$years = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT YEAR(pm.meta_value) FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = %s AND pm.meta_value <> '' AND p.post_type = %s AND p.post_status = 'publish' ORDER BY 1 DESC",
				'_newspaper_archive_date',
				'newspaper_issue'
			)
		);
		$years = is_array( $years ) ? array_filter( $years ) : array();
		set_transient( 'newspaper_archive_years', $years, 12 * HOUR_IN_SECONDS );
	}
	return $years;
}

/**
 * Drop the cached year list.
 */
function newspaper_archive_flush_cache() {
	delete_transient( 'newspaper_archive_years' );
}
add_action( 'save_post_newspaper_issue', 'newspaper_archive_flush_cache' );
add_action( 'deleted_post', 'newspaper_archive_flush_cache' );
add_action( 'transition_post_status', 'newspaper_archive_flush_cache' );

// ------------------------------------------------------------------
// 2. Translations
// ------------------------------------------------------------------

/**
 * Load the plugin translations.
 */
function newspaper_archive_load_textdomain() {
	load_plugin_textdomain( 'newspaper-archive', false, dirname( plugin_basename( NEWSPAPER_ARCHIVE_FILE ) ) . '/languages' );
}
add_action( 'init', 'newspaper_archive_load_textdomain', 0 );

// ------------------------------------------------------------------
// 3. Post type, taxonomy and meta
// ------------------------------------------------------------------

/**
 * Register the post type, taxonomy and meta fields.
 */
function newspaper_archive_register() {
	register_post_type(
		'newspaper_issue',
		array(
			'labels'       => array(
				'name'               => __( 'Newspaper Archive', 'newspaper-archive' ),
				'singular_name'      => __( 'Newspaper Issue', 'newspaper-archive' ),
				'add_new'            => __( 'Add Issue', 'newspaper-archive' ),
				'add_new_item'       => __( 'Add Newspaper Issue', 'newspaper-archive' ),
				'edit_item'          => __( 'Edit Newspaper Issue', 'newspaper-archive' ),
				'new_item'           => __( 'New Newspaper Issue', 'newspaper-archive' ),
				'view_item'          => __( 'View Newspaper Issue', 'newspaper-archive' ),
				'all_items'          => __( 'All Issues', 'newspaper-archive' ),
				'search_items'       => __( 'Search Issues', 'newspaper-archive' ),
				'not_found'          => __( 'No newspaper issues found.', 'newspaper-archive' ),
				'not_found_in_trash' => __( 'No newspaper issues found in Trash.', 'newspaper-archive' ),
			),
			'public'       => true,
			'has_archive'  => apply_filters( 'newspaper_archive_collection_slug', 'newspaper-issues' ),
			'rewrite'      => array( 'slug' => apply_filters( 'newspaper_archive_issue_slug', 'newspaper-issue' ) ),
			'menu_icon'    => 'dashicons-media-document',
			'supports'     => array( 'title', 'editor', 'thumbnail' ),
			'show_in_rest' => true,
		)
	);

	register_taxonomy(
		'newspaper_title',
		'newspaper_issue',
		array(
			'labels'            => array(
				'name'          => __( 'Newspaper Titles', 'newspaper-archive' ),
				'singular_name' => __( 'Newspaper Title', 'newspaper-archive' ),
				'add_new_item'  => __( 'Add Newspaper Title', 'newspaper-archive' ),
				'edit_item'     => __( 'Edit Newspaper Title', 'newspaper-archive' ),
				'search_items'  => __( 'Search Newspaper Titles', 'newspaper-archive' ),
				'all_items'     => __( 'All Newspaper Titles', 'newspaper-archive' ),
			),
			'hierarchical'      => true,
			'public'            => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => apply_filters( 'newspaper_archive_title_slug', 'newspaper-title' ) ),
		)
	);

	$auth   = function () {
		return current_user_can( 'edit_posts' );
	};
	$fields = array(
		'date'    => array( 'string', 'newspaper_archive_sanitize_date' ),
		'edition' => array( 'string', 'sanitize_text_field' ),
		'pages'   => array( 'integer', 'absint' ),
		'pdf_id'  => array( 'integer', 'absint' ),
		'access'  => array( 'string', 'newspaper_archive_sanitize_access' ),
	);
	foreach ( $fields as $key => $def ) {
		register_post_meta(
			'newspaper_issue',
			'_newspaper_archive_' . $key,
			array(
				'type'              => $def[0],
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => $def[1],
				'auth_callback'     => $auth,
			)
		);
	}
}
add_action( 'init', 'newspaper_archive_register' );

register_activation_hook(
	NEWSPAPER_ARCHIVE_FILE,
	function () {
		newspaper_archive_register();
		flush_rewrite_rules();
	}
);
register_deactivation_hook( NEWSPAPER_ARCHIVE_FILE, 'flush_rewrite_rules' );

// ------------------------------------------------------------------
// 4. Admin: meta box, columns, sorting
// ------------------------------------------------------------------

/**
 * Add the issue metadata box.
 */
function newspaper_archive_add_metabox() {
	add_meta_box( 'newspaper_archive_meta', __( 'Issue Metadata', 'newspaper-archive' ), 'newspaper_archive_metabox_html', 'newspaper_issue', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'newspaper_archive_add_metabox' );

/**
 * Render the metadata box.
 *
 * @param WP_Post $post Current post.
 */
function newspaper_archive_metabox_html( $post ) {
	wp_nonce_field( 'newspaper_archive_save', 'newspaper_archive_nonce' );
	$date    = newspaper_archive_meta( $post->ID, 'date' );
	$edition = newspaper_archive_meta( $post->ID, 'edition' );
	$pages   = newspaper_archive_meta( $post->ID, 'pages' );
	$pdf_id  = (int) newspaper_archive_meta( $post->ID, 'pdf_id' );
	$access  = newspaper_archive_meta( $post->ID, 'access' );
	$access  = 'members' === $access ? 'members' : 'public';
	$pdf     = $pdf_id ? get_attached_file( $pdf_id ) : '';
	?>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><label for="newspaper_archive_date"><?php esc_html_e( 'Publication date', 'newspaper-archive' ); ?></label></th>
			<td><input type="date" id="newspaper_archive_date" name="newspaper_archive_date" value="<?php echo esc_attr( $date ); ?>"></td>
		</tr>
		<tr>
			<th scope="row"><label for="newspaper_archive_edition"><?php esc_html_e( 'Edition / number', 'newspaper-archive' ); ?></label></th>
			<td><input type="text" id="newspaper_archive_edition" name="newspaper_archive_edition" class="regular-text" value="<?php echo esc_attr( $edition ); ?>"></td>
		</tr>
		<tr>
			<th scope="row"><label for="newspaper_archive_pages"><?php esc_html_e( 'Number of pages', 'newspaper-archive' ); ?></label></th>
			<td><input type="number" min="0" id="newspaper_archive_pages" name="newspaper_archive_pages" value="<?php echo esc_attr( $pages ); ?>"></td>
		</tr>
		<tr>
			<th scope="row"><label for="newspaper_archive_access"><?php esc_html_e( 'Access', 'newspaper-archive' ); ?></label></th>
			<td>
				<select id="newspaper_archive_access" name="newspaper_archive_access">
					<option value="public" <?php selected( $access, 'public' ); ?>><?php esc_html_e( 'Public (read and download)', 'newspaper-archive' ); ?></option>
					<option value="members" <?php selected( $access, 'members' ); ?>><?php esc_html_e( 'Members only (login required)', 'newspaper-archive' ); ?></option>
				</select>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'PDF file', 'newspaper-archive' ); ?></th>
			<td>
				<input type="hidden" id="newspaper_archive_pdf_id" name="newspaper_archive_pdf_id" value="<?php echo esc_attr( $pdf_id ); ?>">
				<span id="newspaper_archive_pdf_name"><?php echo $pdf ? esc_html( basename( $pdf ) ) : esc_html__( 'No file selected', 'newspaper-archive' ); ?></span><br>
				<button type="button" class="button" id="newspaper_archive_pick"><?php esc_html_e( 'Select or upload PDF', 'newspaper-archive' ); ?></button>
				<button type="button" class="button" id="newspaper_archive_remove"><?php esc_html_e( 'Remove', 'newspaper-archive' ); ?></button>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Load the media picker script on the issue edit screen.
 *
 * @param string $hook Admin page hook.
 */
function newspaper_archive_admin_assets( $hook ) {
	$screen = get_current_screen();
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || ! $screen || 'newspaper_issue' !== $screen->post_type ) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_script( 'newspaper-archive-admin', NEWSPAPER_ARCHIVE_URL . 'assets/admin.js', array( 'jquery' ), NEWSPAPER_ARCHIVE_VERSION, true );
	wp_localize_script(
		'newspaper-archive-admin',
		'newspaperArchive',
		array(
			'title'  => __( 'Select newspaper PDF', 'newspaper-archive' ),
			'button' => __( 'Use this file', 'newspaper-archive' ),
			'none'   => __( 'No file selected', 'newspaper-archive' ),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'newspaper_archive_admin_assets' );

/**
 * Save the metadata box.
 *
 * @param int $post_id Post ID.
 */
function newspaper_archive_save( $post_id ) {
	if ( ! isset( $_POST['newspaper_archive_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['newspaper_archive_nonce'] ) ), 'newspaper_archive_save' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	update_post_meta( $post_id, '_newspaper_archive_date', newspaper_archive_sanitize_date( sanitize_text_field( wp_unslash( $_POST['newspaper_archive_date'] ?? '' ) ) ) );
	update_post_meta( $post_id, '_newspaper_archive_edition', sanitize_text_field( wp_unslash( $_POST['newspaper_archive_edition'] ?? '' ) ) );
	update_post_meta( $post_id, '_newspaper_archive_pages', absint( $_POST['newspaper_archive_pages'] ?? 0 ) );
	update_post_meta( $post_id, '_newspaper_archive_access', newspaper_archive_sanitize_access( sanitize_text_field( wp_unslash( $_POST['newspaper_archive_access'] ?? 'public' ) ) ) );

	// Only accept a PDF attachment the user is allowed to use.
	$pdf_id = absint( $_POST['newspaper_archive_pdf_id'] ?? 0 );
	if ( $pdf_id && ( 'application/pdf' !== get_post_mime_type( $pdf_id ) || ! current_user_can( 'read_post', $pdf_id ) ) ) {
		$pdf_id = 0;
	}
	update_post_meta( $post_id, '_newspaper_archive_pdf_id', $pdf_id );
}
add_action( 'save_post_newspaper_issue', 'newspaper_archive_save' );

add_filter(
	'manage_newspaper_issue_posts_columns',
	function ( $columns ) {
		$columns['newspaper_archive_date']    = __( 'Publication date', 'newspaper-archive' );
		$columns['newspaper_archive_edition'] = __( 'Edition', 'newspaper-archive' );
		return $columns;
	}
);

add_action(
	'manage_newspaper_issue_posts_custom_column',
	function ( $column, $post_id ) {
		if ( 'newspaper_archive_date' === $column ) {
			$date = newspaper_archive_meta( $post_id, 'date' );
			echo $date ? esc_html( wp_date( get_option( 'date_format' ), strtotime( $date . ' 12:00:00' ) ) ) : '&mdash;';
		} elseif ( 'newspaper_archive_edition' === $column ) {
			$edition = newspaper_archive_meta( $post_id, 'edition' );
			echo $edition ? esc_html( $edition ) : '&mdash;';
		}
	},
	10,
	2
);

add_filter(
	'manage_edit-newspaper_issue_sortable_columns',
	function ( $columns ) {
		$columns['newspaper_archive_date'] = 'newspaper_archive_date';
		return $columns;
	}
);

/**
 * Default ordering: newest publication date first.
 *
 * @param WP_Query $query Query.
 */
function newspaper_archive_order_queries( $query ) {
	if ( ! $query->is_main_query() ) {
		return;
	}
	if ( is_admin() ) {
		if ( 'newspaper_archive_date' === $query->get( 'orderby' ) ) {
			$query->set( 'meta_key', '_newspaper_archive_date' );
			$query->set( 'orderby', 'meta_value' );
		}
		return;
	}
	if ( $query->is_post_type_archive( 'newspaper_issue' ) || $query->is_tax( 'newspaper_title' ) ) {
		$query->set( 'meta_key', '_newspaper_archive_date' );
		$query->set( 'orderby', 'meta_value' );
		$query->set( 'order', 'DESC' );
	}
}
add_action( 'pre_get_posts', 'newspaper_archive_order_queries' );

// ------------------------------------------------------------------
// 5. Secure PDF endpoint (members-only issues)
// ------------------------------------------------------------------

/**
 * Stream an issue's PDF after checking access (used for members-only issues).
 */
function newspaper_archive_serve_pdf() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only file delivery, access is checked below.
	if ( ! isset( $_GET['newspaper_archive_pdf'] ) ) {
		return;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$post_id = absint( wp_unslash( $_GET['newspaper_archive_pdf'] ) );
	$post    = $post_id ? get_post( $post_id ) : null;

	if ( ! $post || 'newspaper_issue' !== $post->post_type ) {
		wp_die( esc_html__( 'File not found.', 'newspaper-archive' ), '', array( 'response' => 404 ) );
	}
	if ( 'publish' !== $post->post_status && ! current_user_can( 'edit_post', $post_id ) ) {
		wp_die( esc_html__( 'File not found.', 'newspaper-archive' ), '', array( 'response' => 404 ) );
	}
	if ( post_password_required( $post ) ) {
		wp_die( esc_html__( 'This issue is password protected.', 'newspaper-archive' ), '', array( 'response' => 403 ) );
	}
	if ( ! newspaper_archive_can_access( $post_id ) ) {
		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( wp_login_url( get_permalink( $post_id ) ) );
			exit;
		}
		wp_die( esc_html__( 'You do not have access to this file.', 'newspaper-archive' ), '', array( 'response' => 403 ) );
	}

	$pdf_id = (int) newspaper_archive_meta( $post_id, 'pdf_id' );
	$path   = $pdf_id ? get_attached_file( $pdf_id ) : '';
	if ( ! $path || ! is_readable( $path ) || 'application/pdf' !== get_post_mime_type( $pdf_id ) ) {
		wp_die( esc_html__( 'File not found.', 'newspaper-archive' ), '', array( 'response' => 404 ) );
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$download = isset( $_GET['newspaper_archive_dl'] );

	nocache_headers();
	while ( ob_get_level() > 0 ) {
		ob_end_clean();
	}
	header( 'Content-Type: application/pdf' );
	header( 'Content-Disposition: ' . ( $download ? 'attachment' : 'inline' ) . '; filename="' . sanitize_file_name( basename( $path ) ) . '"' );
	header( 'Content-Length: ' . filesize( $path ) );
	header( 'X-Content-Type-Options: nosniff' );
	readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
	exit;
}
add_action( 'template_redirect', 'newspaper_archive_serve_pdf', 1 );

// ------------------------------------------------------------------
// 6. Single issue view and Dublin Core metadata
// ------------------------------------------------------------------

add_filter(
	'the_content',
	function ( $content ) {
		static $running = false;
		if ( $running || ! is_singular( 'newspaper_issue' ) || ! is_main_query() || get_the_ID() !== get_queried_object_id() ) {
			return $content;
		}
		$running = true;

		$post_id = get_the_ID();
		$date    = newspaper_archive_meta( $post_id, 'date' );
		$edition = newspaper_archive_meta( $post_id, 'edition' );
		$pages   = newspaper_archive_meta( $post_id, 'pages' );
		$names   = wp_get_post_terms( $post_id, 'newspaper_title', array( 'fields' => 'names' ) );
		$view    = newspaper_archive_file_url( $post_id );

		ob_start();
		echo '<div class="na-single">';
		echo '<dl class="na-meta">';
		if ( $names && ! is_wp_error( $names ) ) {
			echo '<dt>' . esc_html__( 'Newspaper', 'newspaper-archive' ) . '</dt><dd>' . esc_html( implode( ', ', $names ) ) . '</dd>';
		}
		if ( $date ) {
			echo '<dt>' . esc_html__( 'Publication date', 'newspaper-archive' ) . '</dt><dd>' . esc_html( wp_date( 'l, j F Y', strtotime( $date . ' 12:00:00' ) ) ) . '</dd>';
		}
		if ( $edition ) {
			echo '<dt>' . esc_html__( 'Edition', 'newspaper-archive' ) . '</dt><dd>' . esc_html( $edition ) . '</dd>';
		}
		if ( $pages ) {
			echo '<dt>' . esc_html__( 'Pages', 'newspaper-archive' ) . '</dt><dd>' . esc_html( $pages ) . '</dd>';
		}
		echo '</dl>';

		if ( ! $view ) {
			echo '<p class="na-note">' . esc_html__( 'The PDF file is not available yet.', 'newspaper-archive' ) . '</p>';
		} elseif ( ! newspaper_archive_can_access( $post_id ) ) {
			printf(
				'<p class="na-note">%s <a href="%s">%s</a></p>',
				esc_html__( 'This collection is for members only.', 'newspaper-archive' ),
				esc_url( wp_login_url( get_permalink( $post_id ) ) ),
				esc_html__( 'Log in to read it.', 'newspaper-archive' )
			);
		} else {
			printf(
				'<iframe class="na-viewer" src="%s" title="%s"></iframe>',
				esc_url( $view ),
				esc_attr( get_the_title( $post_id ) )
			);
			printf(
				'<p><a class="na-btn" href="%s" target="_blank" rel="noopener">%s</a></p>',
				esc_url( newspaper_archive_file_url( $post_id, true ) ),
				esc_html__( 'Open / download PDF', 'newspaper-archive' )
			);
		}
		echo '</div>';

		$running = false;
		return $content . ob_get_clean();
	}
);

/**
 * Print Dublin Core meta tags on a single issue page.
 */
function newspaper_archive_head_meta() {
	if ( ! is_singular( 'newspaper_issue' ) ) {
		return;
	}
	$post_id = get_queried_object_id();
	$tags    = array(
		'DC.title'      => get_the_title( $post_id ),
		'DC.type'       => 'Text',
		'DC.format'     => 'application/pdf',
		'DC.identifier' => get_permalink( $post_id ),
	);
	$date    = newspaper_archive_meta( $post_id, 'date' );
	if ( $date ) {
		$tags['DC.date'] = $date;
	}
	$names = wp_get_post_terms( $post_id, 'newspaper_title', array( 'fields' => 'names' ) );
	if ( $names && ! is_wp_error( $names ) ) {
		$tags['DC.source'] = implode( ', ', $names );
	}
	echo "\n<!-- Newspaper Archive -->\n";
	foreach ( $tags as $name => $value ) {
		printf( '<meta name="%s" content="%s">' . "\n", esc_attr( $name ), esc_attr( $value ) );
	}
}
add_action( 'wp_head', 'newspaper_archive_head_meta' );

// ------------------------------------------------------------------
// 7. Shortcode [newspaper_archive]
// Attributes: per_page="12" title="title-slug" show_filter="yes|no"
// ------------------------------------------------------------------

/**
 * Render the [newspaper_archive] shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function newspaper_archive_shortcode( $atts ) {
	$a = shortcode_atts(
		array(
			'per_page'    => 12,
			'title'       => '',
			'show_filter' => 'yes',
		),
		$atts,
		'newspaper_archive'
	);

	$own_keys = array( 'na_q', 'na_title', 'na_year', 'na_page' );

	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- public, read-only search form.
	$keyword     = isset( $_GET['na_q'] ) ? sanitize_text_field( wp_unslash( $_GET['na_q'] ) ) : '';
	$title_slug  = isset( $_GET['na_title'] ) ? sanitize_title( wp_unslash( $_GET['na_title'] ) ) : sanitize_title( $a['title'] );
	$filter_year = isset( $_GET['na_year'] ) ? absint( $_GET['na_year'] ) : 0;
	$paged       = isset( $_GET['na_page'] ) ? max( 1, absint( $_GET['na_page'] ) ) : 1;
	// phpcs:enable

	$args = array(
		'post_type'      => 'newspaper_issue',
		'post_status'    => 'publish',
		'posts_per_page' => max( 1, (int) $a['per_page'] ),
		'paged'          => $paged,
		'meta_key'       => '_newspaper_archive_date', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		'orderby'        => 'meta_value',
		'order'          => 'DESC',
	);
	if ( '' !== $keyword ) {
		$args['s'] = $keyword;
	}
	if ( '' !== $title_slug ) {
		$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			array(
				'taxonomy' => 'newspaper_title',
				'field'    => 'slug',
				'terms'    => $title_slug,
			),
		);
	}
	if ( $filter_year ) {
		// Dates are stored as Y-m-d, so plain string comparison orders and filters them correctly on any database.
		$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			array(
				'key'     => '_newspaper_archive_date',
				'value'   => array( $filter_year . '-01-01', $filter_year . '-12-31' ),
				'compare' => 'BETWEEN',
			),
		);
	}
	$query = new WP_Query( $args );

	$show_filter = ! in_array( strtolower( (string) $a['show_filter'] ), array( 'tidak', 'no', 'false', '0' ), true );
	$base_url    = remove_query_arg( $own_keys );

	ob_start();
	echo '<div class="na-wrap">';

	if ( $show_filter ) {
		$titles = get_terms(
			array(
				'taxonomy'   => 'newspaper_title',
				'hide_empty' => true,
			)
		);
		printf( '<form class="na-filter" method="get" action="%s">', esc_url( $base_url ) );

		// Keep unrelated query args (needed with plain permalinks, e.g. ?page_id=5).
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		foreach ( $_GET as $key => $value ) {
			if ( in_array( $key, $own_keys, true ) || ! is_scalar( $value ) ) {
				continue;
			}
			printf( '<input type="hidden" name="%s" value="%s">', esc_attr( sanitize_key( $key ) ), esc_attr( sanitize_text_field( wp_unslash( (string) $value ) ) ) );
		}

		printf( '<label class="na-sr" for="na_q">%s</label>', esc_html__( 'Search', 'newspaper-archive' ) );
		printf( '<input type="search" id="na_q" name="na_q" placeholder="%s" value="%s">', esc_attr__( 'Search title or keyword', 'newspaper-archive' ), esc_attr( $keyword ) );

		if ( $titles && ! is_wp_error( $titles ) && '' === sanitize_title( $a['title'] ) ) {
			printf( '<label class="na-sr" for="na_title">%s</label>', esc_html__( 'Newspaper', 'newspaper-archive' ) );
			echo '<select id="na_title" name="na_title"><option value="">' . esc_html__( 'All newspapers', 'newspaper-archive' ) . '</option>';
			foreach ( $titles as $term ) {
				printf( '<option value="%s"%s>%s</option>', esc_attr( $term->slug ), selected( $title_slug, $term->slug, false ), esc_html( $term->name ) );
			}
			echo '</select>';
		}

		printf( '<label class="na-sr" for="na_year">%s</label>', esc_html__( 'Year', 'newspaper-archive' ) );
		echo '<select id="na_year" name="na_year"><option value="">' . esc_html__( 'All years', 'newspaper-archive' ) . '</option>';
		foreach ( newspaper_archive_get_years() as $option_year ) {
			printf( '<option value="%s"%s>%s</option>', esc_attr( $option_year ), selected( $filter_year, (int) $option_year, false ), esc_html( $option_year ) );
		}
		echo '</select>';
		printf( '<button type="submit">%s</button></form>', esc_html__( 'Search', 'newspaper-archive' ) );
	}

	if ( $query->have_posts() ) {
		echo '<div class="na-grid">';
		while ( $query->have_posts() ) {
			$query->the_post();
			$post_id = get_the_ID();
			$date    = newspaper_archive_meta( $post_id, 'date' );
			$edition = newspaper_archive_meta( $post_id, 'edition' );
			$names   = wp_get_post_terms( $post_id, 'newspaper_title', array( 'fields' => 'names' ) );

			echo '<a class="na-card" href="' . esc_url( get_permalink() ) . '">';
			if ( has_post_thumbnail() ) {
				echo wp_kses_post( get_the_post_thumbnail( $post_id, 'medium', array( 'loading' => 'lazy' ) ) );
			} else {
				echo '<span class="na-noimg">PDF</span>';
			}
			echo '<strong>' . esc_html( get_the_title() ) . '</strong>';
			if ( $names && ! is_wp_error( $names ) ) {
				echo '<small>' . esc_html( implode( ', ', $names ) ) . '</small>';
			}
			if ( $date ) {
				echo '<small>' . esc_html( wp_date( 'j F Y', strtotime( $date . ' 12:00:00' ) ) ) . '</small>';
			}
			if ( $edition ) {
				/* translators: %s: edition number or name. */
				echo '<small>' . esc_html( sprintf( __( 'Edition %s', 'newspaper-archive' ), $edition ) ) . '</small>';
			}
			echo '</a>';
		}
		echo '</div>';

		$keep = array();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		foreach ( $_GET as $key => $value ) {
			if ( is_scalar( $value ) && ! in_array( $key, $own_keys, true ) ) {
				$keep[ sanitize_key( $key ) ] = sanitize_text_field( wp_unslash( (string) $value ) );
			}
		}
		$keep  = array_merge(
			$keep,
			array_filter(
				array(
					'na_q'     => $keyword,
					'na_title' => ( '' === sanitize_title( $a['title'] ) ) ? $title_slug : '',
					'na_year'  => $filter_year ? $filter_year : '',
				)
			)
		);
		$links = paginate_links(
			array(
				'base'      => esc_url_raw( add_query_arg( 'na_page', '%#%', remove_query_arg( 'na_page', add_query_arg( $keep, $base_url ) ) ) ),
				'format'    => '',
				'current'   => $paged,
				'total'     => $query->max_num_pages,
				'prev_text' => esc_html__( '&laquo; Previous', 'newspaper-archive' ),
				'next_text' => esc_html__( 'Next &raquo;', 'newspaper-archive' ),
			)
		);
		if ( $links ) {
			echo '<nav class="na-pagination" aria-label="' . esc_attr__( 'Pagination', 'newspaper-archive' ) . '">' . wp_kses_post( $links ) . '</nav>';
		}
	} else {
		echo '<p class="na-note">' . esc_html__( 'No matching issues found.', 'newspaper-archive' ) . '</p>';
	}

	wp_reset_postdata();
	echo '</div>';
	return ob_get_clean();
}
add_shortcode( 'newspaper_archive', 'newspaper_archive_shortcode' );

// ------------------------------------------------------------------
// 8. Front-end styles
// ------------------------------------------------------------------

add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_style( 'newspaper-archive', NEWSPAPER_ARCHIVE_URL . 'assets/style.css', array(), NEWSPAPER_ARCHIVE_VERSION );
	}
);
