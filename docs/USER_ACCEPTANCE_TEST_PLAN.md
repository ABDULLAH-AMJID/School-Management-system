# User Acceptance Test Plan

Complete this plan with representative principal, teacher, cashier, accountant and librarian users before go-live. Record tester, date, result and evidence for each item.

## Identity and access

- Login, logout, temporary-password change and suspension.
- Every role sees only authorized menus and receives 403 for prohibited routes.
- Principal can create a custom role without removing critical principal access.

## Student and academic lifecycle

- Admit a student, link primary guardian, enroll, update and withdraw.
- Submit attendance and approve an absence message.
- Create timetable entries, detect a conflict and publish a version.
- Enter marks, reject invalid marks, publish results and print a result card.
- Promote, repeat and graduate sample students.

## Finance

- Configure and generate an installment.
- Collect cash and bank payments; verify receipt and balanced journal.
- Apply concession and overdue fine.
- Request and approve a reversal.
- Submit and approve an expense.
- Export trial balance and ledger; complete zero-difference reconciliation.

## HR and operations

- Submit/review leave, generate/approve/pay payroll and print payslip.
- Issue/return a library book, assign transport and process stock movement.
- Register/resolve a complaint and issue/verify/revoke a certificate.

## Resilience and communication

- Link dedicated WhatsApp number, send test, disconnect and relink.
- Verify quiet hours, opt-out, retry and duplicate prevention.
- Create encrypted backup, verify checksum and rehearse restore.
- Restart host and confirm web, queue, scheduler and connector recover.

## Devices and print

- Test current Chrome/Edge on desktop and Android/iPhone over Wi-Fi.
- Print receipt, result card, certificate and payslip on the school's printer.
- Confirm acceptable response with representative data volume.

Go-live requires all critical cases to pass, documented acceptance by the principal, and a tested rollback/restore plan.
