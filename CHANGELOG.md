# Changelog

All notable changes to this project are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and this project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Security
- Delete WordPress-generated preview images (rendered first page) of members-only PDFs, which previously stayed publicly reachable; regenerate them when a PDF becomes public again.
- Require permission to edit an attachment before it can be attached to an issue, so authors cannot move other users' PDFs.
- Apply storage rules when an issue's PDF or access level is changed through the REST API.
- Add an `Update URI` header so a same-named plugin on WordPress.org cannot be offered as an update.

### Changed
- Members-only issues from earlier versions are migrated to protected storage automatically in background batches, with an admin notice for failures, instead of requiring each issue to be saved again.
- Issue metadata is now actually exposed through the REST API (the post type declares `custom-fields` support).
- New `NEWSPAPER_ARCHIVE_RELEASE_FILES` uninstall option moves protected PDFs back to their Media Library locations.
- Storage functions moved to `includes/storage.php`; added a standalone storage regression test run in CI.

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
