=== Newspaper Archive ===
Contributors: alamthelibrarian
Tags: library, newspaper, archive, pdf, digital collection
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage and display a library's digital newspaper collection (PDF): issue metadata, search, title and year filters, and a built-in PDF reader.

== Description ==

Newspaper Archive helps libraries and archives publish scanned or born-digital newspaper issues on WordPress.

* Custom post type for newspaper issues and a "Newspaper Titles" taxonomy.
* Metadata per issue: publication date, edition, page count, PDF file and access level (public or members only).
* Public listing with search and filters by title and year via the `[newspaper_archive]` shortcode.
* Single-issue page with an embedded PDF reader and download button.
* Members-only issues are streamed through a PHP endpoint that checks the visitor's login.
* Dublin Core meta tags on each issue page.
* Fields exposed through the REST API.
* English is the default interface language. An Indonesian translation source file (`id_ID.po`) is included; its compiled translation is not bundled.

= Shortcode =

`[newspaper_archive]`

Options: `per_page="12"`, `title="slug-of-newspaper-title"`, `show_filter="yes"` (or `"no"` to hide the filter form).

= Archives =

The collection archive is available at `/newspaper-issues/` and each title at `/newspaper-title/{slug}/`. Slugs can be changed with the `newspaper_archive_collection_slug`, `newspaper_archive_issue_slug` and `newspaper_archive_title_slug` filters.

= Members-only files: important note =

Public PDFs remain in the normal Media Library and keep their usual WordPress URLs. When a members-only issue is saved, its PDF is moved into a protected uploads subdirectory and served through the plugin after a login check. Apache and IIS denial files are created automatically. Nginx administrators must add a server rule for `/wp-content/uploads/newspaper-archive-private/`; see the repository's SECURITY.md for details. Existing members-only issues must be saved again after updating so their PDFs are moved into protected storage.

== Installation ==

1. Upload the `newspaper-archive` folder to `/wp-content/plugins/`, or install the zip from Plugins > Add New > Upload Plugin.
2. Activate the plugin.
3. Go to Newspaper Archive > Add Issue, fill in the metadata and attach a PDF.
4. Add the `[newspaper_archive]` shortcode to a page.

== Frequently Asked Questions ==

= Does it OCR my scans? =

Not yet. Full-text search of OCR output is on the roadmap.

= What happens to my data if I delete the plugin? =

Nothing is deleted by default. To remove issues and titles on uninstall, define `NEWSPAPER_ARCHIVE_REMOVE_DATA` as `true` in wp-config.php before deleting the plugin. PDFs in the Media Library are never removed.

== Changelog ==

= 0.3.0 =
* Members-only PDFs are moved to protected storage and served after access checks; public PDFs retain normal Media Library URLs.
* Added Apache/IIS denial files, Nginx configuration guidance, and a one-issue-per-attachment rule.

= 0.2.0 =
* First public release: unique function prefixes, English source strings, Indonesian translation source, members-only PDF endpoint, REST-exposed meta, Dublin Core tags, safe uninstall, sortable date column.

== Upgrade Notice ==

= 0.3.0 =
Save existing members-only issues again to migrate their PDFs into protected storage. Nginx servers require an additional deny rule.
