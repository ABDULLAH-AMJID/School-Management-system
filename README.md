# Edvora School ERP

A modern, local-first, single-school ERP for day schools from Playgroup through Intermediate. Built with Laravel 13 for a central school PC/server and responsive access over the trusted school LAN.

> **Current status:** active pre-release development. Core workflows are implemented and covered by automated tests, but final host-PC, printer, browser, backup-recovery and real WhatsApp QR tests are required before production use.

## Highlights

- Admissions, student profiles, guardians, enrollment, withdrawal, promotion and graduation
- Academic years, terms, classes, sections, subjects, timetable conflict detection and versioned publication
- Daily student attendance, staff attendance, leave and biometric/RFID-ready event storage
- Exams, marks, configurable grading, printable result cards and tabulation exports
- Fee structures, installments, concessions, fines, receipts, reversals and defaulter reporting
- Double-entry accounting, expenses, journals, trial balance, ledgers and bank reconciliation
- HR, payroll accrual, payroll payment, printable salary slips and leave approvals
- Library, transport, inventory, fixed assets, notices, events and complaints
- Verifiable certificates with immutable issued content and revocation
- Custom roles, granular permissions, approval inbox and permission-aware staff portals
- Audit trails, encrypted checksummed backups and atomic local restore
- Configurable WhatsApp policies, templates, schedules, quiet hours, retries and opt-outs
- QR-linked local WhatsApp connector with replaceable provider architecture
- Responsive, keyboard-accessible and print-friendly English interface

## Technology

- PHP 8.4 and Laravel 13
- SQLite for the initial local deployment profile
- Laravel database queue, scheduler and encrypted storage
- Node.js 20+ WhatsApp sidecar using `whatsapp-web.js`
- Server-rendered Blade interface with no runtime CDN or hosted-font dependency

## Local architecture

```text
School desktops and phones
          |
     Trusted LAN
          |
  Laravel ERP :8000 ---- SQLite database
       |       |---- Queue worker
       |       |---- Scheduler
       |       `---- Encrypted backups
       |
 WhatsApp connector :3210 ---- Internet ---- WhatsApp Web
```

The active SQLite database must remain on the host's local SSD—not OneDrive, Dropbox or a network share. Only encrypted backup files should be copied off-device or to cloud storage.

## Development setup

Requirements: PHP 8.4 with PDO SQLite, OpenSSL and Mbstring; Composer; Node.js 20+ for the optional WhatsApp connector.

```bash
git clone https://github.com/ABDULLAH-AMJID/School-Management-system.git
cd School-Management-system
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve --host=0.0.0.0 --port=8000
```

In separate terminals:

```bash
php artisan queue:work --tries=4
php artisan schedule:work
```

Demo development account:

```text
Email: principal@school.test
Password: password
```

Do not use demo credentials in production. Create strong staff passwords and complete the mandatory password-change workflow.

## WhatsApp QR connector

```bash
cd whatsapp-connector
cp .env.example .env
npm install
npm start
```

Set the same long random connector token in Laravel and the connector. Keep port 3210 private to the host or trusted LAN.

**Risk notice:** this integration automates WhatsApp Web and is not the official WhatsApp Cloud API. It requires internet and may disconnect, break following WhatsApp changes, or cause temporary/permanent number restriction. Use a dedicated school number, guardian consent and conservative sending rates. The adapter is intentionally replaceable.

## Operational commands

```bash
php artisan school:health
php artisan school:deployment-check
php artisan school:backup
php artisan school:backup:restore BACKUP_ID
php artisan school:fees:apply-fines
php artisan school:fees:remind
php artisan school:messages:dispatch
php artisan school:messages:retry
```

## Testing

```bash
php artisan migrate:fresh --seed
php artisan test
vendor/bin/pint --test
node --check whatsapp-connector/src/server.js
```

The suite covers authorization, finance invariants, accounting balance, lifecycle workflows, communication idempotency, security, backup recovery, portal rendering and operational indexes.

## Deployment documentation

- [Host PC and LAN guide](docs/HOST_PC_AND_LAN_GUIDE.md)
- [Local deployment](docs/LOCAL_DEPLOYMENT.md)
- [Performance and capacity](docs/PERFORMANCE_AND_CAPACITY.md)
- [Architecture decisions](docs/architecture/)
- [Security documentation](docs/security/)
- [Product requirements](docs/product/)
- [Principal/admin guide](docs/user-guides/PRINCIPAL_ADMIN_GUIDE.md)
- [Teacher guide](docs/user-guides/TEACHER_GUIDE.md)
- [Finance guide](docs/user-guides/FINANCE_GUIDE.md)
- [Daily operations checklist](docs/user-guides/DAILY_OPERATIONS_CHECKLIST.md)
- [User acceptance test plan](docs/USER_ACCEPTANCE_TEST_PLAN.md)

Windows startup helpers are under `scripts/windows/`. Review paths, service-account permissions and firewall rules before registering the startup task.

## Security

- Never commit `.env`, connector sessions, database files, backup keys or access tokens.
- Revoke any credential accidentally pasted into chat, logs or issue trackers.
- Keep `APP_DEBUG=false` in production.
- Expose the ERP only to the trusted school LAN unless a separate hardened deployment is designed.
- Test encrypted backup restoration before go-live and periodically afterward.
- Report vulnerabilities privately; do not publish secrets in a GitHub issue.

## Scope

This edition is single-school and day-school focused. Hostel functionality and public parent/student portals are intentionally excluded from the initial release. Online-payment and biometric integrations use replaceable foundations and require provider/hardware-specific certification before activation.

## License

Copyright © Abdullah Amjid. A final distribution license will be selected before the production release. Until then, no permission is granted for redistribution or commercial deployment outside the owner's school project.
