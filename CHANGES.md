# Change log (compared with heartcare.zip as received)

The original upload is untouched; `config/config.php.bak` was the only file removed (identical to config.php apart from a trailing newline).
All text files were normalised from CRLF to LF line endings.

## Files added (32)
- admin/profile.php
- admin/settings.php
- admin/types.php
- admin/users.php
- assets/css/admin.css
- assets/css/responsive.css
- assets/icons/icon-192.png
- assets/icons/icon-512.png
- assets/icons/icon-maskable-512.png
- assets/js/admin.js
- assets/uploads/.htaccess
- config/config.local.php.example
- includes/forms.php
- includes/media.php
- includes/modules.php
- manifest.webmanifest
- offline.html
- public/images/community-hero-640.webp
- public/images/community-hero.webp
- sql/install.sql
- sql/migrations/001_add_content_modules.sql
- sql/migrations/002_optional_indexes.sql
- sw.js
- views/404.php
- views/documents.php
- views/gallery.php
- views/notice-single.php
- views/notices.php
- views/partials/doc-list.php
- views/partials/notices-strip.php
- views/partials/pagination.php
- views/project-single.php
- views/projects.php

## Files modified (25)
- .htaccess
- admin/content.php
- admin/includes.php
- admin/index.php
- admin/login.php
- admin/logout.php
- admin/setup.php
- admin/submissions.php
- assets/js/main.js
- config/config.php
- includes/footer.php
- includes/functions.php
- includes/header.php
- index.php
- lang/bn.php
- lang/en.php
- views/contact.php
- views/donate.php
- views/events.php
- views/home.php
- views/join.php
- views/news-list.php
- views/news-single.php
- views/partials/leadership.php
- views/partials/vision.php

## Why (summary)
* **Foundation:** config split + dead constants removed (config.php); `404.php` added (missing view caused a fatal error); `.htaccess` hardened; uploads folder cannot execute code.
* **Security:** admin deletes are POST+CSRF with confirm dialog; roles enforced; login throttling; HTML sanitizer for all rich text; public forms use honeypot, throttling and post-redirect-get; upload validation (type, size, content).
* **Content modules:** notices, projects, documents, gallery (tables, admin, public pages) + home notices strip + past events + images on news/events.
* **Admin:** rewritten generic editor (search, pagination, validation, slug generation, image/file upload, rich-text editor), inbox actions, CSV export, settings, users, account.
* **Mobile & accessibility:** responsive.css (public), admin.css (mobile-first admin), accessible menu, focus styles, reduced motion, 44px targets, responsive images (srcset) and a 127 KB / 49 KB WebP hero instead of a 1.8 MB PNG.
* **Wiring:** homepage leadership message and "people screened" figure can be edited in Site settings (falls back to the original text).

## Dynamic publishing upgrade

* Homepage news stays capped at three and excludes future-dated articles; `/en/all-news.php` now aliases the existing paginated news listing.
* News details support authored-language display, multiple ordered images, reusable tags, SEO/social metadata, and redirects after an article slug is changed.
* Events now link to their own shared-layout detail pages; notices with future dates or expired visibility windows are not exposed by their detail URL.
* `sql/migrations/003_dynamic_news.sql` is additive and repeatable on the supported MySQL/MariaDB versions. It was not executed against the live database; apply only after checking the installed schema.
