# Pilot Hotel Deployment and Security Plan

Status: Planning only. No application code or deployment configuration is changed by this document.

This document records what must be decided and completed before publishing the Pilot Hotel Laravel application as a public website. The application handles reservation and guest contact data, so it must not be treated as a static demo deployment.

## 1. Current application inventory

- Framework: Laravel 12, PHP 8.2 or newer.
- Frontend: Blade, Tailwind CSS, Vite.
- Data: Eloquent reservations, payments, booking history, no-show policy, and staff sessions.
- External service: Firebase Realtime Database synchronization for reservation changes.
- Public entry point: `public/index.php`; Vercel entry point: `api/index.php`.
- Current local defaults: SQLite, database sessions, database cache, database queue, debug logging.
- Existing migrations include reservation, payment, checkout, room, and optional-contact changes.
- `public/build` is generated output and is ignored by Git.
- `.env` is ignored by Git; `.env.example` contains placeholders only.

## 2. Release blockers found in the current code

These must be resolved before a public production launch:

1. **Authentication is not real authentication.**
   `StaffSessionController@login` currently accepts any non-empty username and password and then sets `staff_authenticated=true`. Anyone who reaches the login form can enter the staff portal. Real staff accounts, password hashing, authorization, rate limiting, and account recovery are required.

2. **The app contains guest personal data.**
   Guest names, phone numbers, email addresses, reservation notes, payment information, and staff activity need access control, retention rules, backups, and a privacy policy appropriate to the jurisdiction where the hotel operates.

3. **Production runtime has not been selected.**
   Vercel can invoke the PHP entry point, but this application also needs persistent database storage, sessions, cache, migrations, logs, and potentially queue/scheduler execution. Vercel alone is not the recommended target for the complete Laravel application.

4. **Production database and persistent services are not configured.**
   SQLite is suitable for local development but is not the preferred production database for a multi-user hosted application. Database-backed sessions, cache, and queues require a reliable persistent database and, for multiple instances, a shared database or Redis service.

5. **Firebase credentials are server secrets.**
   `FIREBASE_DATABASE_AUTH` must never be exposed to browser JavaScript, committed to Git, placed in a public file, or copied into a client-side Vite variable. Realtime Database rules must restrict access appropriately.

6. **Deployment and recovery procedures are not verified.**
   A production backup, restore test, migration procedure, health check, error monitoring, and rollback procedure are required before launch.

## 3. Recommended hosting decision

### Recommended: PHP-capable managed host

Use Railway, Render, a managed Laravel host, or a VPS with:

- PHP 8.2+ and required extensions
- Composer 2
- Node.js/npm during build, or prebuilt Vite assets
- MySQL/PostgreSQL, preferably managed
- HTTPS with automatic certificate renewal
- Persistent environment variables/secrets
- Persistent storage for only the files that truly need it
- A supported way to run migrations, queue workers, scheduled tasks, and logs

The web root must point to `public`, not the repository root. Only `public/index.php` should be directly web-accessible.

### Not recommended as the only host: Vercel

The repository includes a Vercel PHP function and rewrite, but that does not solve persistent database, session, cache, queue, scheduler, storage, or operational requirements. Vercel could host static assets or a separate frontend while the Laravel backend runs elsewhere, but it should not be approved as the complete production plan without verifying every required service.

## 4. Sensitive data rules

### Never commit

- `.env` or any production environment file
- `APP_KEY`
- Firebase server tokens or service-account credentials
- Database passwords and connection URLs containing passwords
- SMTP/API keys, cloud access keys, webhook signing secrets, and OAuth client secrets
- Production database dumps, guest exports, logs, session files, and uploaded private documents

The repository currently ignores `.env`, `vendor`, `node_modules`, generated `public/build`, and several local runtime files. This must be checked again before every commit and before creating a public repository.

### Store secrets

Use the hosting provider's encrypted environment-variable/secret store. Set production values there, not in source files. Restrict access to the smallest number of administrators and rotate credentials if they were ever pasted into chat, screenshots, commits, browser code, or public logs.

### Safe production baseline

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-real-domain.example
APP_KEY=<generated-once-and-stored-as-a-secret>
LOG_LEVEL=warning
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

The actual database, Firebase, mail, and cache values must be entered only in the host secret manager. The values above are placeholders, not values to copy literally.

### Browser exposure check

Only variables intentionally prefixed for Vite/browser use may be exposed to the client. Never put Firebase server authentication values, database passwords, or private API credentials in `VITE_*` variables or JavaScript bundles.

## 5. Required security work before launch

1. Replace the demo login with Laravel authentication using users stored in the database and passwords hashed by Laravel.
2. Add staff roles/permissions so front-desk users cannot automatically access every administrative action.
3. Add login throttling, session regeneration, logout invalidation, password reset/recovery, and an administrator account setup process.
4. Review authorization on every reservation, payment, report, room, policy, and export route.
5. Keep CSRF protection enabled for state-changing web requests.
6. Add validation and authorization tests for login, reservation creation, payment recording, checkout, exports, and no-show actions.
7. Use HTTPS-only cookies and confirm the reverse proxy is trusted/configured correctly.
8. Do not display guest data in unauthenticated pages, public URLs, browser local storage, client-side logs, or analytics payloads.
9. Review Firebase rules so clients cannot read or write reservation data directly unless that access is explicitly intended.
10. Redact tokens, passwords, full guest records, and payment details from logs and error reports.
11. Add dependency and secret scanning in the repository/CI pipeline.
12. Confirm the application does not store full card numbers or CVV. Use a compliant payment provider if online card payment is added.

## 6. Production data and privacy decisions

Before launch, the owner must decide and document:

- What guest information is collected and why.
- Who can view, edit, export, or delete it.
- How long reservations, payment records, logs, and backups are retained.
- How a guest requests correction or deletion where applicable.
- Where the database and backups are geographically hosted.
- Whether Firebase is necessary, and whether it receives the same personal data as the main database.
- Whether a privacy notice, consent wording, terms, and cookie notice are required.
- Whether staff activity and exports are audited.

Use `N/A` only for missing optional contact values in the interface. It is not a substitute for removing unnecessary personal data from storage or logs.

## 7. Deployment sequence after approval

1. Choose the hosting provider, domain, database, email provider, and Firebase policy.
2. Create a separate production database; do not upload the local SQLite file as the production database.
3. Configure production secrets in the host secret manager.
4. Configure the web root to `public` and enable HTTPS.
5. Install dependencies with production settings:
   - `composer install --no-dev --optimize-autoloader`
   - `npm ci`
   - `npm run build`
6. Run migrations with `php artisan migrate --force` after taking a backup.
7. Run `php artisan optimize` only after environment values are correct.
8. Configure the queue worker if queued jobs are used and configure the scheduler if scheduled tasks are added.
9. Configure log collection and error monitoring without collecting secrets or unnecessary guest data.
10. Verify login, logout, session expiry, reservation creation, editing, payment, checkout, reports, Firebase failure behavior, and mobile layout.
11. Confirm no debug page, stack trace, `.env`, source map, database file, storage directory, or repository metadata is publicly accessible.
12. Perform a backup and restore test before accepting real reservations.

## 8. Verification checklist

### Application

- [ ] Real authentication and authorization are enabled.
- [ ] Unauthenticated users cannot access dashboard, reservations, reports, rooms, exports, or payments.
- [ ] Invalid input returns a user-safe validation message.
- [ ] Optional email/phone values save successfully and display `N/A`.
- [ ] Reservation codes remain unique under deletion and concurrent requests.
- [ ] Firebase failure does not expose credentials or break the local reservation transaction.

### Infrastructure

- [ ] Production is using a managed MySQL/PostgreSQL database or an explicitly approved alternative.
- [ ] `APP_ENV=production`, `APP_DEBUG=false`, and a real `APP_KEY` are configured.
- [ ] HTTPS and secure cookies are verified.
- [ ] Database, session, cache, queue, mail, and storage services are persistent and operational.
- [ ] Automated backups exist and a restore has been tested.
- [ ] Health checks and error alerts are configured.
- [ ] Domain DNS and TLS certificate are verified.

### Privacy and secrets

- [ ] No secret appears in Git history, public assets, logs, screenshots, or browser requests.
- [ ] Production secrets are stored only in the host secret manager.
- [ ] Firebase rules and credentials were reviewed.
- [ ] Privacy notice and retention policy are approved.
- [ ] Staff access and export permissions are approved.

## 9. Planned code/config changes, pending approval

No implementation is authorized by this document. Once approved, the likely work will be split into reviewable changes:

1. Authentication and authorization.
2. Production environment/configuration and hosting setup.
3. Database/session/cache/queue production configuration.
4. Secret and logging hardening.
5. Security and feature tests.
6. Deployment pipeline, monitoring, backups, and rollback documentation.

Each change should be tested locally, reviewed, and committed separately. Production credentials must never be included in those commits.

## 10. Current repository note

At the time this plan was written, the latest commit was `Fix reservation creation and optional contacts`. The working tree also contained a whitespace-only modification in `config/cache.php`; that change should be reviewed and either discarded or intentionally committed separately. Do not mix that unrelated change into the production security work without understanding it.

## Approval questions

Before implementation begins, approve or answer these decisions:

1. Which hosting provider will run the Laravel backend?
2. Which production database will be used: MySQL or PostgreSQL?
3. Should Firebase synchronization remain enabled in production?
4. Who are the staff users, and what roles do they need?
5. What country/jurisdiction and guest-data retention rules apply?
6. Which email provider should send password resets and operational mail?
7. Should the current demo reservation data be deleted before launch?
8. Should deployment automation and a staging environment be added?
