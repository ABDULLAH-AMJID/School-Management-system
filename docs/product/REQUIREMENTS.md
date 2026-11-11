# School Management System — Product Requirements

## Product model
- Single-school, local-first ERP served from one on-premise computer over the school LAN/Wi-Fi.
- Responsive desktop/mobile staff interface.
- Capacity target: 500–1,500 students and 25–75 concurrent staff users.
- English interface, configurable Punjab Boards, Playgroup through Intermediate.
- Parent communication primarily through WhatsApp.

## Roles and governance
Principal is the system owner. Granular RBAC supports custom roles including principal, administrator, receptionist, teacher, cashier, accountant, librarian, HR officer, transport manager and inventory manager. Sensitive actions use configurable maker/checker approvals and immutable audit logs.

## Functional scope
1. Admissions, enquiries, applications, student lifecycle and guardian records.
2. Academic years, terms, classes, sections, subjects and teacher allocation.
3. Daily/period student attendance; staff attendance, leave and device adapters.
4. Configurable examinations, weightages, practical/theory marks, grades, positions, publication, report cards and Punjab board profiles.
5. Timetables, substitutions, rooms and conflict detection.
6. Fees, concessions, fines, instalments, invoices, receipts, arrears, cash/bank collection and payment adapters.
7. Double-entry accounting, vouchers, reconciliation and financial statements.
8. HR, contracts, leave and payroll.
9. Library catalogue and circulation.
10. Transport routes, stops, vehicles and assignments.
11. Inventory, purchasing and fixed assets.
12. Certificates, notices, complaints and reporting.
13. WhatsApp templates, schedules, approval, quiet hours, retries, delivery logs and opt-out.
14. Encrypted off-site backup with local restore workflow.

## Exclusions
- Hostel management.
- Public parent/student portal in initial local deployment.
- Legacy data migration; deployment starts fresh.
