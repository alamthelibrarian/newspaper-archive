# Newspaper Archive

A WordPress plugin for libraries and archives to publish and manage digital newspaper collections (PDF). It provides issue metadata, search, title and year filters, public or members-only access, and a built-in PDF reader.

[![CI](https://github.com/alamthelibrarian/newspaper-archive/actions/workflows/ci.yml/badge.svg)](https://github.com/alamthelibrarian/newspaper-archive/actions/workflows/ci.yml)
[![License: GPL v2+](https://img.shields.io/badge/license-GPL--2.0--or--later-blue.svg)](LICENSE)

## Features

- **Newspaper Issue** custom post type and **Newspaper Titles** taxonomy.
- Issue metadata: publication date, edition/number, page count, PDF attachment, and access level (public or members only).
- `[newspaper_archive]` shortcode with search, title and year filters, cover images, and pagination.
- Individual issue pages with an embedded PDF reader and download link.
- Members-only PDFs stored in a protected directory and delivered through an access-checked PHP endpoint.
- Dublin Core metadata on issue pages and issue fields exposed through the REST API.
- Translation-ready, with English as the original interface language and an Indonesian translation source included.

## Installation

1. Download the latest release ZIP from [GitHub Releases](../../releases).
2. In WordPress, go to **Plugins → Add New → Upload Plugin**, select the ZIP, and activate it.
3. Open **Newspaper Archive → Add Issue**, enter the metadata, and select a PDF. The featured image is used as the issue cover.
4. Add newspaper names under **Newspaper Archive → Newspaper Titles**.
5. Create a page and add the `[newspaper_archive]` shortcode.

Requirements: WordPress 6.0+ and PHP 7.4+.

## Shortcode

```
[newspaper_archive]
[newspaper_archive per_page="20" title="suara-merdeka" show_filter="no"]
```

| Attribute | Default | Description |
|---|---:|---|
| `per_page` | `12` | Number of issues per page |
| `title` | empty | Newspaper title slug used to filter the collection |
| `show_filter` | `yes` | Set to `no` to hide the search and filter form |

The collection archive is available at `/newspaper-issues/`, and each newspaper title has an archive at `/newspaper-title/{slug}/`. These slugs can be changed with the `newspaper_archive_collection_slug`, `newspaper_archive_issue_slug`, and `newspaper_archive_title_slug` filters.

## Member-only PDF protection

- **Public issues:** PDFs remain in the WordPress Media Library and retain their normal WordPress URLs. Public reading and downloads work as usual.
- **Members-only issues:** when an issue is saved, its PDF is moved to `wp-content/uploads/newspaper-archive-private/` (or the configured uploads directory) and served through the plugin after a login check.
- A PDF attachment can be assigned to only one issue, preventing public and members-only access settings from conflicting.
- If the server cannot create the protected directory or move the file, the plugin will not attach that PDF as a members-only file. Ensure the PHP process has the required filesystem permissions.
- **Nginx requires an additional server rule** because it does not read `.htaccess`. See [SECURITY.md](SECURITY.md) for the configuration example.
- After updating, the plugin re-checks every members-only issue in small background (WP-Cron) batches and moves any PDF still in the public Media Library into protected storage. An admin notice lists issues it could not protect. Back up the site before updating.
- WordPress's generated preview images of a members-only PDF (the rendered first page) are deleted while the PDF is protected, and regenerated when it becomes public again.
- Attaching a PDF requires permission to edit that attachment, because a members-only issue moves the file out of its public location.
- The `newspaper_archive_can_access` filter is available to customize application-level access checks.

This protection depends on correct web-server configuration. Administrators should also review backups, CDN caches, and any previously exposed public copies of a PDF.

## Uninstalling

By default, uninstalling the plugin does **not** delete issue or title data. To delete them as well, add the following to `wp-config.php` before uninstalling:

```php
define( 'NEWSPAPER_ARCHIVE_REMOVE_DATA', true );
```

PDF files in the Media Library are never deleted by the uninstall routine.

Members-only PDFs stay in the protected directory after uninstall, so they are not exposed, but nothing on the site can serve them any more. To move them back to their original Media Library locations during uninstall (which makes them public), also add:

```php
define( 'NEWSPAPER_ARCHIVE_RELEASE_FILES', true );
```

## Development roadmap

- Full-text search using OCR output.
- Bulk import from CSV or PDF folders, with automatic cover generation.
- Calendar-based browsing by publication date.
- Dublin Core/MARC metadata export and a page-by-page PDF.js reader.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md). For security reports, see [SECURITY.md](SECURITY.md).

## License

[GPL-2.0-or-later](LICENSE).
