# Heart Care Foundation Comilla — website + admin panel

PHP 8.1+ · MySQL 5.7+/8 or MariaDB 10.3+ · Apache (mod_rewrite recommended).
The original public design is preserved. This package adds notices, year-wise projects, documents/reports,
a photo gallery, a mobile-friendly admin panel, security hardening and an optional installable-app (PWA) mode.

> Check the current deployment and `TEST_REPORT.md` before going live; test migrations and uploads on a backup or staging database first.

## 1. Requirements
* PHP **8.1 or newer** with extensions: `pdo_mysql`, `mbstring`, `dom` (HTML sanitizer), `gd` (image resizing; recommended), `fileinfo` (upload checks), `exif` (optional, fixes rotated phone photos).
* `php.ini`: `upload_max_filesize = 12M`, `post_max_size = 128M` for several article images in one form (many shared hosts use smaller defaults; raise both limits or uploads will be rejected).
* Folders writable by the web server: `assets/uploads/` (and `assets/uploads/docs/`), `storage/logs/`. The PHP/Apache service account must be able to create and write files there; the account and permission steps vary by operating system and hosting provider.

## 2. Fresh install (XAMPP or hosting)
1. Copy the folder to `htdocs/heartcare` (or your web root).
2. Create an empty database `heartcare` (utf8mb4). Select it and import **`sql/install.sql`**, then apply `sql/migrations/008_gallery_media.sql` to add the curated video entries. The installer includes the shared media-library tables.
3. Local settings: copy `config/config.local.php.example` to `config/config.local.php` and set DB host/port/name/user/password. Set `APP_URL` to the public origin (for example `https://www.example.org`); `BASE_PATH` is added to canonical and social URLs automatically.
   If you do nothing, defaults match the original XAMPP setup (`127.0.0.1:3307`, user `root`, empty password, URL prefix `/heartcare`).
   Installing at a domain root? set `BASE_PATH` to `''`.
4. Open `/heartcare/admin/setup.php`, create the first administrator (password 10+ characters), then **delete `admin/setup.php`**.
5. Log in at `/heartcare/admin/`.

## 3. Upgrading an existing database
Do **not** import `install.sql`. Back up your database, then import migrations in order:
`001_add_content_modules.sql`, followed by `003_dynamic_news.sql`, `004_wordpress_import.sql`, `005_add_event_news_url.sql`, `006_smart_home_cards.sql`, `007_publications.sql`, `008_gallery_media.sql`, `009_heart_health_card_visual.sql`, and `010_media_library.sql`. If needed, apply optional index migration 002 before 007.
Migration 003 adds optional SEO fields, an authored-language marker, normalized article tags, image records, and slug redirects; it drops no existing content.
Migration 004 adds nullable WordPress source IDs to news and events so the CSV importer can safely update records on repeat runs.
Migration 005 adds a nullable `related_news_url` to events and is safe to re-run; existing events remain unchanged and continue linking to their event detail pages. On an existing installation, back up the database and apply this migration before deploying the updated Admin form.
Migration 006 extends the existing home-card records and adds normalized category, statistics, CTA, media, style, and animation tables. It preserves current card IDs, text, image paths, links, and publication status. Apply it before deploying the homepage card manager.
Migration 007 renames `health_topics` to `publications` without changing the existing IDs or content, then adds optional subtitle, PDF, and external publication URL fields. Apply it before deploying the Publications menu and Admin form. Existing `/heart-health` URLs redirect to their Publications equivalents.
Migration 008 extends the existing Gallery items with photo albums, categories, and YouTube video IDs, then seeds the supplied video list idempotently. It preserves current photo records and uploaded files. One repeated entry is merged: the provided final URL reused the hypertension video ID, so the matching World Heart Day video uses the verified ID `dSTF2YgGTRE` already supplied for that title. Apply this migration after backing up the database and before deploying the gallery Admin form.
Migration 009 switches the Heart Health homepage card to a matching branded SVG image visual while preserving its text, link, and other card settings.
Migration 010 adds the shared Media Library index. Uploads use a safe allowlist of common images, documents, archives, audio and video; active web files and executables are rejected. The library limit is 25 MB per file, while existing document and publication fields retain their 10 MB limit.
Rollback safely by restoring the prior source version; the additive columns/tables may remain unused. Do not drop them unless you have confirmed there is no new content to preserve.

## 4. Go-live checklist
* `DEBUG` → `false` in `config/config.local.php`; use a dedicated DB user (not `root`) with a password.
* Delete `admin/setup.php`. Serve over HTTPS.
* Replace the placeholder seed content (phone `+880 1700 000000`, sample news/events, "people screened" figures) with real, verified content in the admin.
* Have a cardiologist review every health article.
* Run the checklist in `MOBILE_TESTING_CHECKLIST.md` and the runtime tests in `TEST_REPORT.md`.

## 5. What the admin can manage (all from a phone)
News & articles · Notices (pin, expiry, attachment) · Events (past events archive appears automatically) ·
Projects by year · Documents & reports (PDF/DOC/DOCX/XLS/XLSX ≤ 10 MB, category, year, related project) ·
Gallery · Publications (thumbnail, PDF and international URL) · Pages · Home/Featured cards · Team · Messages, membership/volunteer/partner requests ·
Donations (mark pending/paid/failed) · Newsletter (CSV export) · Site settings (contact details, homepage video,
leadership message, PWA switch) · Users and roles · My account.

The **Media Library** indexes existing uploads and lets content managers upload reusable files once. Existing image, attachment and video fields can select compatible library assets while keeping their current storage references and per-field limits.
The **Photos & Videos** manager supports photo albums and YouTube cards. A YouTube player is loaded only after a visitor chooses Play; image uploads continue to use the normal validated upload workflow.

The **Homepage card manager** controls the homepage Mission, Programs & Impact cards, including bilingual copy,
categories/tags, statistics, multiple CTAs, order, featured/published/archive state, and optional schedules.
Appearance controls use allowlisted themes, colors, gradients, patterns, local images or MP4/WebM video
(24 MB maximum), and CSS-based motion. Video is muted, plays only while visible, and falls back to its poster;
reduced-motion preferences disable animations. The current implementation does not load a Lottie player or
execute administrator-supplied SVG/CSS/JavaScript.

Roles: **Administrator** everything · **Editor** content + inbox · **Translator** can edit existing content only.

Social profile URLs are managed in **Admin → Site settings**. Only configured profile links are displayed in the top bar and footer; generic platform homepages are hidden. The footer link groups become collapsible on mobile.
The footer also displays the provided partner logo assets in an automatically scrolling strip; the animation pauses on hover/focus and respects reduced-motion settings. The World Health Organization logo and World Heart Federation membership badge are distinct, and are labelled separately.

## Public GitHub repository safety
Keep `config/config.local.php` and WordPress export CSV files out of Git. The local config contains database credentials, and the supplied WordPress CSV includes commenter names, email addresses, and IP addresses. `.gitignore` excludes both files. Keep a required CSV in a private backup and pass its path to the importer locally, for example:
`php scripts/import-wordpress-content.php /private/path/news-events-content.csv --dry-run`
Remove `--dry-run` to import. Do not publish database dumps, logs, or private credentials.

## 6. Deliberate limits (not done)
* `mission`, `about` and `team` public pages are the original designed templates (English only, hard-coded). They were left
  untouched to preserve the design; they do not read from the database. Bangla versions need your translations.
* No payment gateway: donations are recorded as *pending* for manual verification.
* Documents are public once published (no private/members-only files).
* The desktop header navigation is unchanged; Notices/Projects/Documents/Gallery appear in the mobile menu and the footer.
* Optional PWA: caches public static files and shows an offline page only. It is OFF until enabled in Site settings; it needs HTTPS.
  The app icons are a simple generated heart — replace `assets/icons/*.png` with your official logo.

## 7. Automatic article pages (news / free camps / activities)
Saving an article in **Admin → News & articles → + New article** is all that is needed. No file is created and nothing else has to be edited:
* the article gets its own page at `/en/<title-slug>.php` (also `/bn/...`) with the normal site header and footer — e.g. title "Free Heart Camp" → `/en/free-heart-camp.php`;
* it appears at once as a card (photo, date, title, short summary, "Read more") on the homepage (up to 3 latest eligible articles) and on `/en/news` or `/en/all-news.php`;
* the web address comes from the English title automatically; to choose it yourself open "Web address (optional)" in the form;
* the old address `/en/news/<slug>` keeps working. If an administrator changes an article slug, the old direct URL redirects to the new one. Words already used by the site (news, all-news, events, donate…) cannot be used as an article address; "-2" is added automatically.
* choose the article's original language; the interface language switch does not generate a duplicate translation;
* add multiple article images, arrange or remove saved images, select existing tags, and add tags when permitted;
* optional SEO title, description, and keywords feed the page metadata and social-sharing preview.
Edit the article later in the admin and its page and cards update immediately. News with a future publication date or unpublished status stays hidden until eligible.

## Image upload troubleshooting
Image uploads are stored on the computer running the website, not on the computer used to open the admin panel. If an upload reports that the image could not be saved, check that the web server's PHP/Apache account can write to `assets/uploads/` (and `assets/uploads/docs/` for documents), and that the server has free disk space. On the XAMPP setup at `/opt/lampp/htdocs/heartcare`, Apache runs as `daemon`; the folder owner can grant that account access with `setfacl -m u:daemon:rwx /opt/lampp/htdocs/heartcare/assets/uploads`. On other machines, use the actual PHP/Apache service account and project path. Avoid making upload folders writable by every system user. PHP's `upload_max_filesize` and `post_max_size` must also allow the upload; supported image formats are JPG, PNG, and WebP, up to the configured `MAX_IMAGE_BYTES` limit. These server permissions and PHP limits cannot be changed for every hosting environment by application code; configure them on the server or ask the hosting provider.
