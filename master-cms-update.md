# MASTER IMPLEMENTATION PROMPT — HEARTCARE WEBSITE MODULAR CMS UPGRADE

## Role

Act as a Senior Full-Stack **PHP** Developer, Database Migration Specialist, **CMS** Architect, UI/UX Engineer, QA Engineer, and DevOps Engineer.

Your task is to audit, improve, test, and upgrade my existing Heartcare Foundation website into a secure, modern, modular **CMS** with a WordPress-like administration experience, while preserving the existing website design, content, URLs, database records, and working features.

This is an existing project. Do **NOT** rebuild it from scratch or replace its architecture without first understanding the current codebase.

## 1. Existing Environment

Website project path:

`/opt/lampp/htdocs/heartcare`

Production/local website:

`[http://**192**.**168**.0.**152**/heartcare/`](http://**192**.**168**.0.**152**/heartcare/`)

Database:
- Database name: `heartcare`
- Database engine: MariaDB 10.4.32
- Database host: `**127**.0.0.1`
- Database port: `**3307**`
- **PHP** **CLI** version: 8.4.24
- Web server: **XAMPP** Apache
- Existing application: **PHP** + MySQL/MariaDB
- Existing database connection: `config/config.php` and `config/database.php`

Current database has 21 tables, including:

`news`, `news_images`, `news_slug_redirects`, `news_tags`, `tags`, `notices`, `events`, `projects`, `documents`, `gallery_items`, `publications`, `pages`, `team`, `settings`, `featured_cards`, `home_cards`, `submissions`, `newsletter`, `donations`, `users`, and `login_attempts`.

Current database credentials/configuration are environment-specific. Read the existing configuration securely. Never print, expose, or commit passwords, tokens, or private configuration values.

## 2. Existing Backup

A database export was created at:

`/opt/lampp/backup/heartcare/heartcare_20261025_143247.sql`

The file exists and its header identifies the `heartcare` database. Its footer contains a `Dump completed on` marker.

Do not assume the backup is fully restorable merely because the file exists. Validate:
- File readability and nonzero size.
- **SQL** dump completeness.
- Expected table definitions and data statements.
- Whether the dump can be restored into an isolated staging database.
- Table counts and important record counts before and after restoration.

Never overwrite or delete this backup. Keep a separate copy before any backup-related operation.

## 3. ABSOLUTE SAFETY RULES

These requirements override all implementation convenience:

## Do not modify the production database during audit or initial testing.

## Do not run `sql/install.sql`, `sql/schema.sql`, or any migration against production. ## Do not drop, truncate, rename, or recreate production tables. ## Do not reset the MySQL root password or alter either MySQL instance. ## Do not modify `/www/server/data` or the separate MySQL service on port 3306. ## Use the existing XAMPP MariaDB instance on port 3307 for the Heartcare project. ## Never assume a migration has or has not been applied just from its filename. ## Never automatically deploy changes to the live website. ## Never overwrite user-uploaded images, documents, or existing content. ## Never disable authentication, authorization, CSRF protection, or security checks to make tests pass. ## Never expose database credentials, personal data, form submissions, or passwords in logs. ## Do not make destructive changes without explicit approval. ## If a task requires production database modification, stop and explain the proposed change, its risk, rollback method, and required approval. ## Do not claim a test, backup, migration, or deployment succeeded unless you have actually verified it.

## 4. Phase One — Read-Only Project Audit

Inspect the entire project before writing code.

Review:
- Directory structure and entry points.
- `config/config.php` and `config/database.php`.
- Authentication and session management.
- Existing admin panel.
- **PHP** controllers and shared includes.
- Frontend views and layout templates.
- **CSS**, JavaScript, assets, images, and upload directories.
- **SQL** schema, installation scripts, and migration files.
- **URL** routing and existing URLs.
- Bangla and English language support.
- Existing **CRUD** functionality.
- Existing **SEO**, metadata, pagination, search, and image handling.
- User roles and permissions.
- Error handling, logging, security, and validation.
- Existing tests and dependency configuration.

Produce an audit report containing:
- Current architecture.
- Existing working features.
- Broken or incomplete features.
- Security risks.
- Database/schema discrepancies.
- Missing functionality.
- Compatibility concerns.
- A phased implementation plan.
- Files likely to require changes.

Do not modify files during the initial audit.

## 5. Phase Two — Backup and Isolated Staging

Before implementation:

## Validate the existing SQL backup.

## Confirm the actual database connection points to `heartcare` on port 3307. ## Record the current table list, column definitions, indexes, and important row counts. ## Create a separate staging environment with a distinct database, such as `heartcare_staging`. ## Confirm that the staging database name is not `heartcare`. ## Restore the validated backup into staging only. ## Compare table counts and important record counts between production and staging. ## Test staging independently from the production website. ## Ensure staging cannot send real emails, process real donations, or submit data to production services.

If you cannot create or restore staging safely, stop and provide the exact error. Do not work around it by modifying production.

## 6. Phase Three — Migration Audit

Inspect these files carefully:

- `sql/schema.sql`
- `sql/install.sql`
- `sql/migrations/001_add_content_modules.sql`
- `sql/migrations/002_optional_indexes.sql`
- `sql/migrations/003_dynamic_news.sql`

Current observations that must be verified:

- The live database currently contains 21 tables.
- `news.content_language` exists.
- `news.idx_news_list` exists on `(status, published_at, id)`.
- The **SEO** columns exist in several content tables.
- The tables `news_images`, `tags`, `news_tags`, and `news_slug_redirects` exist.
- Indexes expected from migration **002** appear to be missing:
    - `idx_news_slug_en`
    - `idx_events_list`
    - `idx_topics_slug_en`
    - `idx_pages_slug_en`
    - `idx_sub_inbox`
- The existing `idx_news_list` has a different column definition from the two-column index described in migration **002**.

These observations are not proof of migration history. Verify the actual schema and every relevant migration statement.

For every migration:
- Check all referenced tables and columns.
- Check for duplicate indexes and constraints.
- Check MariaDB 10.4 compatibility.
- Check repeatability and partial-failure behavior.
- Identify existing data that could violate new constraints.
- Check whether rollback is feasible.
- Generate a migration report.

Do not run the migrations until they have been reviewed and tested against staging.

If a migration is defective, create a corrected migration or documented repair plan. Preserve the original migration file unless a change is explicitly approved.

## 7. Phase Four — WordPress-Like Modular CMS

Improve the existing application into a maintainable modular **CMS** without installing WordPress and without unnecessarily replacing the **PHP** architecture.

### Content modules

Implement or complete administration for:

- News and Articles.
- Notices and Announcements.
- Events.
- Projects.
- Documents and Reports.
- Gallery and Albums.
- Health Topics.
- Static Pages.
- Team and Leadership.
- Home Page Content.
- Featured Cards and Home Cards.
- Website Settings.
- Newsletter subscriptions.
- Contact and other form submissions.
- Donation records, respecting the existing business workflow.

Each module should support only the operations appropriate to its content type.

### Content management capabilities

Where appropriate, provide:
- Create, view, edit, publish, unpublish, archive, and delete.
- Draft and published states.
- Search, filtering, sorting, and pagination.
- Bangla and English content.
- Slug generation and uniqueness validation.
- **SEO** metadata.
- Featured image and gallery image management.
- Image alt text.
- Publication date and author information.
- Preview before publishing.
- Input validation and clear error messages.
- Confirmation before destructive actions.
- Safe handling of existing content and uploaded media.

Do not invent new database columns or relationships without checking the actual schema and documenting the reason.

## 8. Administration Dashboard and UX

Create a professional, responsive admin interface that fits the existing Heartcare branding.

Requirements:
- Dashboard with useful content statistics.
- Clear navigation by module.
- Consistent create/edit forms.
- Responsive desktop, tablet, and mobile layout.
- Readable Bangla and English typography.
- Loading, empty, success, and error states.
- Accessible forms and controls.
- Confirmation for irreversible actions.
- Clear validation feedback.
- Appropriate table filters and pagination.

Preserve the existing public website's design and working functionality. Do not redesign the public site unnecessarily.

## 9. Security Requirements

Audit and improve:
- Authentication and session security.
- Role-based authorization.
- Password hashing using modern **PHP** password APIs.
- **CSRF** protection for state-changing requests.
- **PDO** prepared statements.
- Output escaping to prevent **XSS**.
- **HTML** sanitization for rich-text content.
- File upload validation and safe file naming.
- **MIME** and extension verification.
- Upload size limits.
- Protection against path traversal.
- Login throttling and brute-force protection.
- Secure error handling.
- Protection of configuration files and backup files.
- Appropriate cookie settings and **HTTPS** deployment considerations.

Do not make existing functionality inaccessible while adding security. If an existing weakness requires a behavior change, document it and test it.

Never store passwords in plain text or expose secrets in source control.

## 10. SEO, URLs, and Performance

Preserve existing working URLs whenever possible.

Review:
- Meta titles and descriptions.
- Canonical URLs.
- **XML** sitemap.
- Robots directives.
- Open Graph metadata.
- Structured data where appropriate.
- Slug uniqueness.
- Redirect handling when a published slug changes.
- Pagination URLs.
- Image optimization and alt text.
- Database query efficiency.
- Appropriate indexes based on actual query patterns.

Do not add duplicate indexes or modify **URL** patterns without documenting the effect.

## 11. Code Quality and Compatibility

- Follow the project's existing conventions unless there is a clear reason to improve them.
- Use reusable **PHP** components and shared validation logic.
- Avoid duplicated **CRUD** implementations.
- Keep database access separate from presentation where practical.
- Avoid unnecessary new dependencies.
- Do not require Composer packages unless their benefits and server compatibility are justified.
- Check compatibility with the actual **PHP** and MariaDB versions.
- Preserve environment-specific configuration outside public web access.
- Do not commit `.env`, local configuration, backup files, or secrets.

Review the existing `**DEBUG**=true` setting and recommend a safe production configuration. Do not silently change live configuration.

## 12. Testing Requirements

Run all applicable tests in staging:

### Database

- Schema validation.
- Migration execution and repeatability.
- Foreign-key and index validation.
- Record count comparison.
- **CRUD** operations.
- Rollback or recovery rehearsal where feasible.

### Backend

- **PHP** syntax checks.
- Authentication and authorization tests.
- **CSRF** tests.
- Validation tests.
- Upload tests.
- Error-handling tests.
- **SQL** injection and **XSS** checks.

### Frontend

- Desktop and mobile layout.
- Bangla and English rendering.
- Forms and validation.
- Navigation and existing URLs.
- Images and galleries.
- Search, pagination, and filtering.
- Browser console and network errors.

### Regression

Verify that existing public pages, content, admin login, and current functionality continue to work.

Do not mark a test as passed if it was not executed. Clearly distinguish:
- Passed.
- Failed.
- Not tested.
- Blocked by environment.

## 13. Implementation Workflow

Work in small, reviewable phases:

## Read-only audit.

## Backup validation. ## Isolated staging setup. ## Migration review and staging tests. ## Security and architecture improvements. ## CMS module implementation. ## Automated and manual testing. ## Final audit and deployment plan. ## Production deployment only after my explicit approval.

After each phase, report:
- What was inspected or changed.
- Files created or modified.
- Database changes made in staging.
- Tests run and their actual results.
- Outstanding issues.
- Backup and rollback instructions.
- The next proposed action.

Do not claim completion based on code generation alone.

## 14. Immediate Task

Start with the current project state. Do not restart the entire process blindly.

First: ## Read the current project files and previous audit report, if available. ## Verify the backup file at `/opt/lampp/backup/heartcare/heartcare_20261025_143247.sql`. ## Check the backup's table definitions, data statements, and completion marker. ## Verify whether a staging database already exists. ## Confirm that any database restore target is separate from production. ## Report the findings and propose the next safe step.

Do not run migrations, alter production tables, change database credentials, or deploy changes during this immediate task.

## 15. Final Deliverables

Produce:
- Project audit report.
- Database schema and migration audit.
- **CMS** architecture and implementation plan.
- List of files created and modified.
- Security review.
- Test report with actual results.
- Staging setup and restore instructions.
- Migration and rollback documentation.
- Production deployment checklist.

Begin by inspecting the existing project and its current state. Explain the plan before making any changes. Preserve all existing production data and wait for explicit approval before production deployment.
