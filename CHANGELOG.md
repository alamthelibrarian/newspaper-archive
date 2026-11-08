# Changelog

All notable changes to this project are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and this project adheres to [Semantic Versioning](https://semver.org/).

## [0.3.0] - 2026-11-09

### Security
- Move members-only issue PDFs into protected storage and serve them through an access-checked endpoint.
- Keep public PDFs in the normal WordPress Media Library with their usual URLs.
- Add Apache/IIS access-denial files, Nginx configuration guidance, and one-issue-per-attachment enforcement.
- Restore the original file location when an issue is changed from members-only to public.

## [0.2.0] - 2026-11-08

First public release.

### Added
- `newspaper_issue` custom post type, `newspaper_title` taxonomy, and issue metadata (date, edition, page count, PDF, and access level).
- `[newspaper_archive]` shortcode with search, title/year filters, and pagination.
- PHP endpoint for members-only issues.
- Dublin Core meta tags, REST API metadata, and sortable publication-date admin column.
- English source strings and Indonesian translation source.
- Safe `uninstall.php` behavior (data is retained unless `NEWSPAPER_ARCHIVE_REMOVE_DATA` is set to `true`).
