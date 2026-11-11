# Target Architecture

The legacy Laravel 8 application is a domain reference. The target is a modern modular monolith: simple on-premise operations with replaceable integrations.

## Modules
Platform, Identity, People, Admissions, Academics, Attendance, Assessment, Timetable, Billing, Accounting, HR/Payroll, Library, Transport, Inventory, Communication and Reporting.

## Engineering rules
- Policies and permissions protect every operation.
- Public ULIDs avoid exposing sequential identifiers.
- Money uses integer minor units and explicit currency.
- Academic/financial periods are explicit and lockable.
- Posted accounting entries are immutable and reversed, never edited.
- Notifications use transactional outbox events and idempotency keys.
- Database constraints enforce integrity; sensitive actions are audited.
- Provider interfaces isolate messaging, payments, biometrics and backups.

## WhatsApp
`MessagingProvider` separates business workflows from transport. The initial QR/Web connector is a separately supervised, explicitly unofficial service. Session material is encrypted. The contract permits later migration to Meta Cloud API without changing ERP modules.
