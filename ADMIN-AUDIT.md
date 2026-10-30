# Admin Media Library and Panel Audit

## Summary

A shared Media Library is available at **Admin → Content → Media Library**. It indexes supported files already stored under `assets/uploads/`, accepts new uploads, and provides a picker for compatible existing content fields. Content records continue to store their existing relative paths; no unrelated content tables or public routes were changed for this feature.

The upload allowlist covers common raster images, PDF and Office documents, text/CSV, ZIP/TAR/GZIP archives, and common audio/video formats. Executables, scripts, SVG, HTML, and other active web formats are not accepted. The library limit is 25 MiB per file, subject to PHP/server limits. Existing document/publication fields retain their 10 MiB per-file limit.

## Admin areas reviewed

| Area | Media support / existing behavior |
|---|---|
| News & articles | Cover image and multiple article images can be uploaded or selected from the library. Existing ordering and removal controls remain. |
| Notices | Attachment can be uploaded or selected; the existing notice attachment path/name fields remain. |
| Events | Event photo can be uploaded or selected. |
| Projects | Project photo can be uploaded or selected. |
| Documents & reports | Supported document can be uploaded or selected; the existing 10 MiB field limit and document metadata are retained. |
| Photos & Videos | Gallery photos can be uploaded or selected. Gallery video entries remain YouTube-based, as the current data model has no local-video gallery field. |
| Publications | Cover image and PDF can be uploaded or selected; PDF-only and 10 MiB field restrictions remain. |
| Pages | Existing page editor contains text/SEO fields only; there is no page media column to attach to. |
| Homepage & people | Home card images and MP4/WebM video assets, featured-card images, team photos, and the leadership photo support the picker. |
| Inbox, Messages, Members, Volunteers, Partners, Donations, Newsletter | These are submission/inbox workflows without content media fields. They were not given unrelated attachment fields; existing submission behavior is unchanged. |
| Site settings | Leadership photo can use a library image. |

The panel has separate scripts for the dashboard, content manager, home-card manager, settings, submissions, users, and the media library; navigation still uses the existing role and permission checks. Media Library access requires the existing `content` permission. Upload POST actions use the existing CSRF mechanism.

## Safety and compatibility controls

- Server-side checks use the file extension plus detected MIME or signature validation; uploaded content is not trusted based on the browser-supplied MIME type.
- Image uploads are checked as image data. Office archives are checked as ZIP containers and macro-enabled files are rejected.
- New stored names are random; the original filename is retained as metadata.
- Upload storage remains inside `assets/uploads/`; the library directory denies execution of script-like extensions, and the uploads parent already disables directory listing and script execution.
- Library selections are resolved server-side, checked for an existing file inside the uploads directory, and checked against the destination field's allowed kind/extensions.
- Replacing or removing a content reference does not delete library-managed files that may be shared by other content.
- Existing upload paths are indexed without renaming or moving source files.
- The Media Library schema exists in the upgrade migration and both fresh-install SQL definitions.

## Verification completed

- PHP syntax checks passed for the new Media Library helper/page and edited admin PHP files. PHP reports a pre-existing duplicate `zip` module-load warning in this environment; it did not prevent linting.
- `node --check assets/js/admin-media-picker.js` passed.
- The shared helper returned indexed images, resolved an existing image selection, and rejected an executable extension in direct CLI checks.
- Existing uploads were indexed locally; the initial scan found 67 image assets.
- Unauthenticated requests to both `admin/media.php` and `admin/content.php` redirected to the existing admin login.
- The local PHP CLI has `pdo_mysql`, `mysqli`, and `zip`; `fileinfo` is not loaded. The library has a signature-based fallback for supported formats, and the homepage-card video upload was updated to use the same validator instead of directly constructing `finfo`.
- Local PHP upload limits are 50 MiB for both `upload_max_filesize` and `post_max_size`; the application-level Media Library cap is 25 MiB per file.

## Not verified / follow-up

- An authenticated admin session was not available for browser-driven upload, picker, save, re-edit, and delete/replacement workflow testing. Unauthenticated routing was checked, but this does not replace end-to-end testing as an admin.
- Visual testing at desktop/tablet/mobile sizes and browser console inspection were not possible in the authenticated admin UI during this run.
- The Pages editor has no media field, Partners is an inbox submission rather than a managed partner CRUD section, and gallery videos remain YouTube-only. Adding those capabilities requires a product/data-model decision and is outside this integration.
- Run one authenticated staging test for each field family (image, multiple images, document/PDF, home-card video) before production deployment.
