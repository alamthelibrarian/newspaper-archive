=== Newspaper Archive ===
Contributors: alamthelibrarian
Tags: library, newspaper, archive, pdf, digital collection
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.2.0
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
* Translations: English and Indonesian (id_ID).

= Shortcode =

`[newspaper_archive]`

Options: `per_page="12"`, `title="slug-of-newspaper-title"`, `show_filter="yes"` (or `"no"` to hide the filter form).

= Archives =

The collection archive is available at `/newspaper-issues/` and each title at `/newspaper-title/{slug}/`. Slugs can be changed with the `newspaper_archive_collection_slug`, `newspaper_archive_issue_slug` and `newspaper_archive_title_slug` filters.

= Members-only files: important note =

Members-only issues are delivered through the plugin's access check. The PDF still lives in the normal Media Library folder, so anyone who knows that exact file URL could open it. For strictly confidential material, restrict direct access to the PDF files at the web server level.

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

= 0.2.0 =
* First public release: unique function prefixes, translation-ready (English source, Indonesian included), members-only PDF endpoint, REST-exposed meta, Dublin Core tags, safe uninstall, sortable date column.

== Upgrade Notice ==

= 0.2.0 =
First public release.
