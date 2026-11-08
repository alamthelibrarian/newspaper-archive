# Security Policy

## Supported versions

Only the latest stable release receives security fixes.

## Protection for members-only PDF files

The plugin stores members-only issue PDFs in a `newspaper-archive-private` directory inside the WordPress uploads directory. It creates access-denial files for Apache (`.htaccess`) and IIS (`web.config`). Public PDFs are not moved and continue to use their normal WordPress Media Library URLs.

**Nginx does not read `.htaccess`.** On Nginx servers, administrators must add a rule to the virtual host/server configuration. Adjust the path prefix if your WordPress uploads location differs:

```nginx
location ^~ /wp-content/uploads/newspaper-archive-private/ {
    deny all;
    return 403;
}
```

Reload the Nginx configuration after making this change. If WordPress uses a custom uploads path, multisite, an alias, or a CDN, adapt the rule to the actual public URL of the uploads directory. Test the direct URL of a members-only PDF while logged out; it must return 403 or 404, not the PDF contents.

For Apache installations, ensure that `AllowOverride` permits access rules in the uploads directory. If `.htaccess` rules are not applied by the server, members-only PDFs may remain directly accessible. For environments requiring stronger confidentiality, storage outside the document root is a stronger option and must be configured by the server administrator.

After updating the plugin, open and save each existing members-only issue again so its PDF is moved from the public Media Library location into protected storage. Back up your files first. Also check caches/CDNs and remove any old public copies that may have been retained.

## Reporting a vulnerability

Please **do not** open a public issue. Use one of these channels:

1. The **Report a vulnerability** feature on this repository's GitHub *Security* tab (private vulnerability reporting), or
2. Email saiful@alam.web.id with the subject `[SECURITY] newspaper-archive`.

Include a description, reproduction steps, and the potential impact. We aim to respond within 7 days and will disclose fixes after a release is available.
