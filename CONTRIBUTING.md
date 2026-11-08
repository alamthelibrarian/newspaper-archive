# Contributing

Thank you for your interest in contributing! Bug reports, feature suggestions, translations, documentation, and code contributions are welcome.

## Code of conduct

Be respectful and constructive. Critique code and ideas, not people. Harassment is not tolerated.

## Reporting bugs or requesting features

Open an [issue](../../issues) using the available template. Include your WordPress version, PHP version, active theme, reproduction steps, and expected result. **Do not report security vulnerabilities through public issues**; see [SECURITY.md](SECURITY.md).

## Development setup

```bash
git clone https://github.com/alamthelibrarian/newspaper-archive.git
cd newspaper-archive
composer install        # Install PHP_CodeSniffer and WordPress Coding Standards
composer lint           # Run php -l on all PHP files
composer cs             # Run coding standards checks
```

Place the plugin folder in `wp-content/plugins/` in a local WordPress environment (for example, wp-env, LocalWP, or Docker).

## Coding guidelines

- Follow the [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/).
- Prefix all global functions, constants, and hooks with `newspaper_archive_` / `NEWSPAPER_ARCHIVE_`.
- Escape output (`esc_html`, `esc_attr`, `esc_url`) and sanitize input; verify nonces and capabilities.
- Write interface strings in English using the `newspaper-archive` text domain, then provide translations in `languages/`.
- Update `CHANGELOG.md` for every user-visible change.

## Translations

The `languages/newspaper-archive.pot` file is the translation template. Copy it to `newspaper-archive-<locale>.po`, translate it (for example, with Poedit), and compile it to `.mo`:

```bash
msgfmt -o languages/newspaper-archive-id_ID.mo languages/newspaper-archive-id_ID.po
```

## Pull requests

1. Fork the repository and create a branch from `main` (`fix/short-description` or `feat/short-description`).
2. Keep changes small and focused; use one pull request per objective.
3. Ensure CI passes, then explain what changed and why in the pull request description.

By contributing, you agree that your contributions will be licensed under GPL-2.0-or-later.
