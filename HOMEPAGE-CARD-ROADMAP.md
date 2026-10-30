# Homepage Smart Card System — Audit and Roadmap

> **Latest audit: 2026-10-30.** This report supersedes earlier assumptions below
> where the current implementation has changed. This pass made no database or
> production changes.

## Current State

### Frontend

- The site is a server-rendered PHP application; `index.php` routes requests and shared PHP templates render pages. There is no SPA framework.
- `views/home.php` composes the homepage. The smart-card partial renders records from `home_cards`; the earlier localized “Who We Are / What We Do / What's Our Goal” copy still exists in `lang/en.php` and `lang/bn.php`, but is no longer rendered.
- The homepage also renders distinct featured cards, vision/statistics, events, news, leadership, video, observatory, publications, donation, and newsletter sections. These sections must remain in place.
- CSS is in `assets/css/style.css` with responsive overrides in `assets/css/responsive.css`. Current tokens include red `#D7263D`, charcoal, teal, neutral surfaces, Inter, and Hind Siliguri.
- `assets/js/main.js` uses vanilla JavaScript for navigation, sliders, counters, card reveal/video behavior, and other progressive enhancements. Reduced-motion handling and keyboard-focus styling exist.
- The Publications preview is already compact on mobile; current homepage cards use a horizontal slider there, while this request specifies a stacked mobile layout.

### Backend and CMS

- `includes/functions.php` queries card records and child data using PDO. These public read functions currently do not catch missing-table/schema errors, so a partially migrated database can fail homepage rendering.
- `views/partials/home-cards.php` escapes text and attributes, allowlists visual/motion values, and renders local media and CTAs. A legacy parent-row fallback is not yet implemented.
- `admin/home-cards.php` provides a dedicated manager with content, media, style, animation, statistics, buttons, schedule, archive, and ordering controls. It requires authenticated content permission and CSRF validation; `admin/includes.php` defines admin/editor/translator permissions.
- Card video upload is restricted to MP4/WebM and 24 MB. Existing image upload/storage validation is reused. Admin writes use the current schema; they are not safe to invoke against a database missing migration 006.
- Publications is a separate content type using the renamed `publications` table, with optional subtitle, thumbnail, PDF, and external URL fields. Migration 007 is additive except for the guarded table rename.

### Database and content

- Local inspection confirms `home_cards`, all six child/lookup tables (`home_card_categories`, `home_card_buttons`, `home_card_media`, `home_card_stats`, `home_card_styles`, `home_card_animations`), and `publications` exist.
- The local database contains three active smart cards: “Heart Health”, “Our Impact”, and “Our Community”. Their existing images and database values were inspected but not changed. Child records exist for categories, buttons, media, styles, and animations; statistics are currently empty.
- The localized approved “Who We Are / What We Do / What's Our Goal” text remains in the translation files but does not match the currently visible smart-card titles/bodies. This is a content-preservation gap; do not overwrite database rows or claim the texts are equivalent.
- Migrations 006 (smart cards) and 007 (Publications) are already applied locally. No new table is required for the planned visual/mobile refinements or public-query fallback.
- Uploaded media is server-side content. A full production schema, backup state, and uploaded-media backup could not be inspected from this local workspace.

## Problems

1. The localized approved intro copy is not visible on the current homepage even though it remains in both translation files.
2. Public card queries directly reference migration-006 tables/columns without graceful, logged fallback; a schema mismatch can break this homepage section.
3. Mobile smart cards are swipeable rather than stacked, contrary to the current mobile requirement.
4. Existing global colors differ from the requested palette; apply the new palette only to the homepage to avoid unintended changes to unrelated pages.
5. Do not invent impact statistics, replace card records, drop tables, or run database migrations as part of this visual/content-preservation pass.
6. Admin create/edit/media flows and production permissions cannot be validated against a production system from this workspace.

## Opportunities

- Reuse existing localized intro copy, smart-card records, child data, Admin workflow, and CSS/JS patterns without rewriting database content.
- Add a compact, localized intro presentation so the previously approved copy is visible again while keeping the Admin-managed card section intact.
- Make public card reads tolerate absent optional smart-card schema with server-side logging and a legacy parent-table query; show the localized intro if no smart cards can be read.
- Keep the requested palette scoped to homepage styles, use a single-column mobile stack, and preserve the current Publications preview, mobile navigation, reduced-motion behavior, and other homepage sections.
- Continue rendering card statistics only from stored data; do not seed example numbers.

## New Architecture

1. **Content**: retain the existing smart-card schema and rows. Render the translation-backed approved intro copy separately; do not repurpose or overwrite existing Admin-managed card content without an explicit backed-up content migration.
2. **Queries**: add a logged PDO read helper and compatible legacy parent-card query. Optional child data should degrade to empty presentation data, not a fatal error.
3. **Presentation**: keep server-rendered markup and current content escaping/allowlists. Stack smart cards on mobile; preserve desktop responsiveness and reduced-motion behavior.
4. **Visual system**: use the requested red/neutral palette through homepage-scoped tokens. Leave global styles and unrelated pages unchanged.
5. **Media**: retain current local uploads and static fallbacks. Do not add a dependency, arbitrary SVG/HTML, remote video player, or Lottie integration in this pass.

## Database Changes

- No migration is needed for this pass: all smart-card and Publications tables/columns were confirmed locally.
- Do not write, delete, rename, or backfill database rows. No production data was accessed or changed.
- Keep migrations 006/007 as the existing schema history. Before deployment elsewhere, verify schema and back up the database and uploaded media; apply only the already reviewed migrations if the target is behind.
- Never drop tables or change card/publication content as part of this design update.

## Backend Changes

- Keep current published/schedule/archive filtering and URL/media allowlists.
- Catch and log PDO exceptions for public homepage card reads, retry the basic legacy parent query when the enhanced query is unavailable, and return a non-fatal empty result if no compatible parent schema exists.
- Keep Admin persistence and permissions unchanged; handle query/schema failures with logging and an explicit Admin error state. Admin writes still require the complete migration-006 schema.
- Do not change image/video upload behavior or delete uploaded files.

## Frontend Changes

- Render published smart cards, plus the existing bilingual intro copy that is currently hidden.
- Use the requested homepage palette, concise spacing, accessible controls, and a stacked mobile card layout; keep all existing sections and Publications behavior.
- Darken the requested secondary text slightly to `#59636E` on white because `#6B7280` does not meet WCAG AA contrast for normal-size text.
- Do not claim Lighthouse or WCAG scores without measurements.

## Admin Changes

- Keep the existing manager and its role/CSRF checks unchanged; it already exposes the card content, media, appearance, motion, statistics, CTA, schedule, and archive controls.
- Catch and log missing-schema PDO errors before they reach the browser; show a clear Admin availability message rather than a fatal error.
- Admin writes require the complete migration-006 schema. Production CRUD/media workflows remain unverified.

## Migration Plan

1. This local pass made no database changes; before any target deployment, back up its database and uploaded-media directory.
2. Check target migration status. Do not rerun or apply migrations if the required schema is already present; if behind, validate migrations 006/007 on staging and compare existing IDs/content before deployment.
3. Deploy code only after syntax, query-fallback, responsive, and English/Bangla rendering checks pass.
4. Verify the homepage and Admin permissions on staging. Admin CRUD/media operations require the complete smart-card schema.
5. Do not alter production content or apply a schema migration as part of this visual pass.
6. Roll back by reverting application code; do not remove schema or restore data unless an independently reviewed recovery requires it.

## Implementation Plan

1. Complete and record this audit before code edits.
2. Restore visible localized intro copy without modifying existing database records.
3. Harden public homepage card reads with logged, compatible fallbacks.
4. Apply scoped color and mobile stacking refinements without changing unrelated page behavior.
5. Run PHP/JS syntax checks, targeted rendering checks, and responsive/accessibility inspections; do not claim unrun Lighthouse scores.
6. Document that there is no new migration and list target-environment backup/schema verification steps.

## Audit Limits and Acceptance Notes

- This audit inspected the checked-out PHP templates, includes, Admin configuration/handlers, CSS/JS, SQL definitions, and the local database’s card records. It did not inspect a production server, analytics, or unprovided stakeholder-approved statistics.
- Lighthouse scores are measured deployment outcomes, not code guarantees. Establish a baseline and measure the deployed site; do not claim a score without running Lighthouse against the target environment.
- “Lottie”, remote video, and arbitrary uploaded illustrations require reviewed dependencies/assets. No new third-party runtime is assumed by this roadmap.
