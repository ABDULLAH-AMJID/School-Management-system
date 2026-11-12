# Project Handover — School Management System

> **Purpose:** This is the canonical onboarding document for any developer or AI agent continuing this repository. Read this file, `README.md`, and the referenced documents before changing code.

## 1. Current project state

- **Repository:** `School-Management-system`
- **Target GitHub repository:** `ABDULLAH-AMJID/School-Management-system`
- **Branch:** `main`
- **Architecture:** Laravel modular monolith with a replaceable local Node.js WhatsApp sidecar
- **Current source baseline before this handover:** `701fe93`
- **Last full automated validation:** **124 tests passed, 523 assertions**
- **Last validated formatter:** Laravel Pint passed
- **Current delivery state:** source repository is clean and prepared for GitHub; generated dependencies, local secrets, local database, and caches were intentionally removed.
- **Production declaration:** functionally substantial, but **not yet declared final production-ready**. Real-host installation, UAT, real QR-linked WhatsApp delivery, screenshots, and the remaining state-machine remediation are still required.

## 2. Product decisions that must not be changed silently

1. Single school, not SaaS/multi-tenant hosting. School scoping still exists throughout the schema for security and future safety.
2. Local-first deployment on one central school PC/server over LAN/Wi-Fi.
3. Responsive English interface for desktop and mobile.
4. Principal is the primary administrator.
5. Separate role-based access for teachers, cashier, accountant, librarian, receptionist, and other staff.
6. Playgroup through Intermediate, with configurable Punjab Board academic structures and reports.
7. Expected scale: approximately 500–1,500 students.
8. Day-school ERP only; hostel is excluded.
9. No legacy data migration. Deployment starts with a fresh database.
10. Parents/students do not initially receive their own portal.
11. Parent communication is primarily WhatsApp.
12. WhatsApp uses unofficial QR-linked WhatsApp Web automation, not Meta Cloud API.
13. WhatsApp event modes are automatic, approval-required, manual, and disabled.
14. The WhatsApp connector must remain replaceable and clearly risk-labelled. It requires internet and may disconnect, break after upstream changes, or cause account restriction/banning.
15. Finance requires controlled double-entry accounting, approvals, receipts, reversals, payroll settlement, and reconciliation.
16. Sensitive operations use custom RBAC, approvals, tenant/school ownership checks, and mutation audit logs.
17. Backups are encrypted and locally restorable; production should also copy backups to approved cloud/off-site storage.
18. Never place credentials, GitHub tokens, `.env`, connector tokens, live database files, session data, or backup files in Git.

Canonical requirements: `docs/product/REQUIREMENTS.md`.

## 3. Technology baseline

- Laravel **13.42.0**
- PHP **8.4.27** used during development
- Vite **8.3.8**
- SQLite local deployment profile
- Blade-rendered UI with inline/local-first styling and no required hosted fonts
- Laravel queues for background workflows
- Node.js **20+** WhatsApp sidecar using `whatsapp-web.js`, Express, and QR generation
- PHPUnit/Laravel test suite
- Laravel Pint
- GitHub Actions CI and Dependabot

Dependency lock files are committed. `vendor/` and all `node_modules/` are deliberately absent and must be restored after cloning.

## 4. Repository setup after clone/download

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan test
```

For development:

```bash
php artisan serve --host=0.0.0.0 --port=8000
php artisan queue:work
```

Before production/LAN use, follow:

- `docs/LOCAL_DEPLOYMENT.md`
- `docs/HOST_PC_AND_LAN_GUIDE.md`
- `docs/deployment/LOCAL_SERVER.md`
- `docs/RELEASE_CHECKLIST.md`

Deployment diagnostics:

```bash
php artisan school:deployment-check
php artisan school:deployment-check --json
php artisan school:health
php artisan school:health --json
./scripts/security-check.sh
```

## 5. Seeded development login

- **Email:** `principal@school.test`
- **Password:** `password`

This is development-only. Force a strong unique password before live use.

## 6. Implemented capabilities

### Foundation and security

- Authentication and logout
- Forced temporary-password change
- Active/suspended user enforcement
- Custom roles and permissions
- Permission-aware navigation and controllers
- School ownership isolation
- Mutation audit trail and filtered audit export
- Approval inbox scoped by permission
- Security/health/deployment diagnostics
- CI, Dependabot, Composer audit, syntax, formatting, tests, and credential-pattern gates

### Students and guardians

- Admissions and student profiles
- Guardian records and primary/fee contacts
- Student updates
- Withdrawal workflow that closes active enrollments
- Promotion, repeat, graduation, and next-year enrollment creation
- Student register reports/search

### Academics

- Academic years, grade levels, sections, subjects, terms, rooms
- Teaching assignments
- Daily student attendance
- Period/timetable structures and conflict checks
- Versioned timetable publication
- Exams, components, marks entry, absent/exempt classifications
- Publication completeness checks
- Result cards, grading, tabulation exports

### HR and staff

- Staff profiles and accounts
- Leave requests and approval/rejection
- Staff attendance
- Biometric/RFID-ready device/event foundation
- Payroll generation, approval, payslips, accounting accrual, and payment settlement

### Fees and accounting

- Fee structures and installments
- Batch invoice generation
- Discounts/concessions through approval
- Overdue fines and reminders
- Fee collection and printable receipts
- Payment reversals through approval
- Chart of accounts
- Balanced journal entries and lines
- Expense vouchers through approval
- Trial balance and general ledger exports
- Bank reconciliation
- Replaceable online-payment gateway foundation

### Operations

- Library catalogue, copies, loans, returns, and overdue fines
- Vehicles, transport routes/stops, capacity-controlled student assignments
- Inventory items, suppliers, stock movements, and fixed assets
- Notices and events
- Complaints, assignment, resolution, and communication events
- Certificate templates, issuance, verification, and revocation
- Dashboard analytics and reports

### Communications

- Message templates and event policies
- Automatic, approval-required, manual, and disabled modes
- Quiet hours and scheduling
- Consent and opt-out enforcement
- Approval/cancellation flow
- Retry limits and failure logging
- Idempotent event logs
- Attendance, results, complaints, fee receipts/reminders, timetable, leave, and payroll communication hooks
- QR-linked local WhatsApp connector

### Backup and recovery

- Encrypted SQLite backup creation
- SHA-256 checksum verification
- Atomic restore with tamper rejection
- Backup health/readiness checks
- Stale running backups marked failed after one hour

## 7. Important implementation conventions

1. **Authorization:** use `auth()->user()->hasPermission(...)`. Do not replace this with Laravel's native `can(...)` unless the custom RBAC is deliberately integrated with Gates/Policies.
2. **School isolation:** every user-selected related record must be verified as belonging to the authenticated user's school. Never trust a plain `exists` rule for school-owned IDs.
3. **Money:** stored as integer minor units (paisa), not floating point.
4. **Financial mutations:** use database transactions and lock authoritative rows before checking state.
5. **External effects:** use idempotency keys and queue jobs; do not send WhatsApp messages inside a financial transaction.
6. **Dates:** `Request::date()` can return null even when a default argument is supplied; use explicit null coalescing.
7. **Views:** avoid external fonts/CDNs for required functionality because local/LAN and sandbox previews may not have internet.
8. **Dynamic HTML:** escape untrusted values. Global search and security-readiness output were hardened after review.
9. **WhatsApp QR:** only accept validated PNG data URIs and create image elements through DOM APIs; do not insert connector output through raw `innerHTML`.
10. **Queues in tests:** automatic messages can execute synchronously; use `Queue::fake()` or approval-required templates where isolation is needed.
11. **Communication tests:** seeded logs exist, so assert event-specific/new records rather than total global counts.
12. **Git hygiene:** never commit `.env`, SQLite data, `vendor`, `node_modules`, generated views, logs, connector sessions, QR data, backups, or tokens.

## 8. Security defects already found and fixed

### Cross-school isolation

Commit `24728cb` fixed these reviewed defects and added regression tests:

- Promotion could previously accept a destination section from another school.
- Timetable could previously accept a period or room from another school.
- Complaint assignment could previously target a user from another school.

Regression coverage: `tests/Feature/CrossSchoolIsolationTest.php`.

### Dynamic interface rendering

Commit `48becfb`:

- Escaped dynamic global-search values.
- Escaped security-readiness labels/details.
- Replaced unsafe WhatsApp QR HTML insertion with validated DOM construction.

### WhatsApp connector runtime

Commit `07dcd32`:

- Requires a connector token of at least 32 characters.
- Uses timing-safe bearer-token comparison.
- Limits JSON body size and validates phone/message/idempotency data.
- Adds configurable per-minute rate limiting.
- Checks whether recipients are registered.
- Bounds the in-memory idempotency cache.
- Expires QR output after 60 seconds.
- Handles malformed JSON and safer error responses.

### Critical state/concurrency hardening

Commit `2fcce43`:

- Atomically claims due messages before dispatch.
- Atomically claims approved/queued messages before sending.
- Prevents duplicate concurrent WhatsApp sends.
- Preserves `overdue` invoice state after reversal when balance remains past due.
- Recovers backups stuck in `running` for over one hour.

Commit `b9da3fe`:

- Locks library loan/copy return transitions.
- Locks student withdrawal and permits it only from active state.
- Makes leave review compare-and-set atomic.
- Locks payroll approval to prevent duplicate journals.
- Locks exam publication and blocks republishing.
- Locks promotion run, decisions, and source enrollments.

## 9. Validation history

Latest completed full regression run before cleanup:

- **124 tests passed**
- **523 assertions**
- Laravel Pint passed
- Node connector syntax had previously passed
- Composer audit previously reported no known advisories

Earlier strict review also completed:

- PHP syntax lint over 227 PHP files at that point
- 107-route inventory
- No duplicate route names
- Blade view compilation
- Configuration caching and clearing
- Fresh isolated SQLite migration and seed
- SQLite `integrity=ok`
- Zero SQLite foreign-key violations
- TODO/debug/raw-query/unscoped-validation scans

After cleanup, local PHP runtime and `vendor` were intentionally removed. Restore dependencies before rerunning validation.

## 10. Known incomplete or unproven work

Do not describe these as completed until verified:

1. **Real WhatsApp test:** QR linking and actual message delivery have not been proven on the final host with Node, Chromium, internet, and a real number.
2. **Host-PC installation:** no final school computer has been purchased/configured, so fresh-machine deployment and LAN tests remain pending.
3. **UAT:** role-based user acceptance testing on actual desktop/mobile devices remains pending. Use `docs/USER_ACCEPTANCE_TEST_PLAN.md`.
4. **Screenshots:** professional repository screenshots still need to be captured from the final polished running system.
5. **GitHub publication:** repository has not yet been pushed to the target GitHub account.
6. **Cloud/off-site backup destination:** encrypted local backup exists; final approved cloud/off-site copy procedure needs host-specific configuration.
7. **Online payment provider:** foundation exists, but no approved Pakistani provider is configured. Current safe state is `configuration_required`.
8. **Biometric/RFID ingestion:** schema/foundation exists; device-specific ingestion and complete event processing are not implemented.
9. **Remaining state-machine formalization:** see the next section.
10. **Final release gate:** complete release checklist, security gate, migration rehearsal, restore drill, queue/cron setup, and failure-flow verification on the target host.

## 11. Implicit state-machine remediation status

The full audit is in `docs/IMPLICIT_STATE_MACHINE_AUDIT.md`. It identified **24 primary implicit state machines** and **10 state-like classifications/switches**.

The most dangerous race conditions have been addressed in messaging, reversal status calculation, backups, library returns, student withdrawal, leave review, payroll approval, exam publication, and promotion completion.

However, the broader remediation is **not complete**. Recommended next steps:

### P0

- Introduce backed PHP enums or equivalent canonical state definitions by bounded context.
- Centralize legal transitions instead of raw `update(['status' => ...])` calls.
- Canonicalize external online-payment statuses and implement signed/verified webhook processing.
- Add explicit reject/cancel/void/correction states where business requirements need them.
- Add transition-history records with from/to, actor, reason, correlation ID, and timestamp.

### P1

- Add stale recovery for messages stuck in `sending` after worker death.
- Record restore attempts as requested/running/succeeded/failed.
- Complete attendance-device event processing (`pending → processed/failed/retry`).
- Add database-level constraints/unique indexes for critical one-time transitions where portable.
- Expand concurrency tests using a production-like database if deployment moves beyond SQLite.

### P2

- Define correction/reopen workflows for published exams/results.
- Add attendance day/period close or approval if required operationally.
- Extend library copy states for lost/damaged/written-off/reserved.
- Formalize staff employment and fixed-asset lifecycles.

Do not perform a blind global status refactor. Migrate one bounded context at a time with tests and data compatibility.

## 12. Important files and directories

- `README.md` — public project overview and setup
- `SECURITY.md` — vulnerability and operational security policy
- `HANDOVER.md` — this continuation document
- `app/` — Laravel application code
- `database/migrations/` — complete fresh schema
- `database/seeders/DatabaseSeeder.php` — demo/setup data and roles
- `routes/web.php` — web route inventory
- `tests/Feature/` — business workflow and regression coverage
- `whatsapp-connector/` — replaceable Node QR connector
- `.github/workflows/ci.yml` — CI quality/security gates
- `scripts/security-check.sh` — local release gate
- `docs/IMPLICIT_STATE_MACHINE_AUDIT.md` — lifecycle audit
- `docs/RELEASE_CHECKLIST.md` — release criteria
- `docs/USER_ACCEPTANCE_TEST_PLAN.md` — role/device acceptance plan
- `docs/user-guides/` — principal, teacher, finance, and daily-operation guides
- `docs/security/THREAT_MODEL.md` — threat model
- `docs/architecture/ARCHITECTURE.md` — architecture decisions

## 13. Scheduled/background operations

Review the host scheduler and queue worker for these commands/jobs:

- `school:messages:dispatch`
- failed-message retry command
- fee due reminder generation
- overdue fee fine application
- encrypted backup creation
- queue processing for outbound messages and result/timetable notifications

Exact command classes are in `app/Console/Commands/`. Verify schedule registration and production process supervision before go-live.

## 14. WhatsApp sidecar setup and risks

Location: `whatsapp-connector/`.

Typical setup:

```bash
cd whatsapp-connector
npm install
# Configure a strong CONNECTOR_TOKEN (32+ characters) and connector URL/token in Laravel .env
npm start
```

Never expose the connector directly to the public internet. Restrict it to localhost/LAN as appropriate, protect it with the bearer token, secure the session directory, and use a dedicated school WhatsApp number. The integration is unofficial and may disconnect or lead to number restrictions.

## 15. Previous environment/tooling issues

These are historical development-environment facts, not application defects:

- The sandbox had no system PHP, Composer, or Docker.
- Development used a temporary `/home/user/php-runtime/`, now intentionally deleted.
- `rsync` was unavailable; `cp -a` was used where needed.
- Composer executable had been `/home/user/php-runtime/composer`, not `composer.phar`.
- A previous GitHub token was exposed in conversation. It was never to be stored or reused and should be considered revoked.
- Git identity used during development: `Abdullah Amjid <abdullah-amjid@users.noreply.github.com>`.

## 16. Recommended next-agent workflow

1. Read this file and all documents linked in sections 2 and 12.
2. Inspect `git status` and recent commits; begin only from a clean tree.
3. Restore dependencies and create a local `.env` from `.env.example`.
4. Run migration/seed, Pint, full tests, Composer audit, connector syntax, and `scripts/security-check.sh` before edits.
5. Work through remaining state-machine remediation by bounded context with regression tests.
6. Perform final UI/browser review and capture professional screenshots.
7. Execute the UAT plan and fresh-host deployment rehearsal.
8. Test encrypted backup and restore using a disposable database.
9. Test real QR linking, disconnect/reconnect, retry, opt-out, quiet-hours, and actual delivery on the final host.
10. Complete `docs/RELEASE_CHECKLIST.md` before declaring production-ready.
11. Request GitHub authentication only when publication is the immediate final step; never embed a token in files or commands that may persist.

## 17. Commit chronology

The Git history is intentionally granular. Important recent commits:

- `fad5a2c` — portal smoke coverage and rendering fixes
- `4d38acf` — local-first responsive accessibility
- `e7c0e15` — professional README and security documentation
- `f7aaabc` — staff guides and UAT plan
- `2a4b114` — CI/release security gates
- `24728cb` — cross-school isolation fixes
- `48becfb` — dynamic interface/QR rendering hardening
- `07dcd32` — WhatsApp connector hardening
- `51f11ff` — implicit state-machine audit
- `2fcce43` — critical race-safe transitions
- `b9da3fe` — multi-record lifecycle locking
- `701fe93` — clean GitHub source deliverable

Use `git log --oneline --reverse` for the complete feature-by-feature history from legacy analysis through the current build.

## 18. Definition of done

Do not mark the project fully complete merely because tests pass. Final completion requires all of the following:

- Clean fresh-host installation succeeds from repository instructions.
- Full tests, formatter, audits, syntax checks, and security gate pass.
- Principal, teacher, finance, library, reception, HR, and other configured roles pass UAT.
- Desktop and mobile/LAN access are verified.
- Backup creation and destructive restore rehearsal succeed.
- Queue workers and scheduled commands survive restart.
- WhatsApp real-device flows and documented risks are accepted.
- Remaining P0 lifecycle risks are resolved or formally accepted/documented.
- Screenshots and public repository documentation are complete.
- No secrets, local data, sessions, backups, or generated artifacts are committed.
- Target GitHub repository is published and final clone verification succeeds.
