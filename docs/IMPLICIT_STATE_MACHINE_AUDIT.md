# Implicit State Machine Audit

**Scope:** static review of Laravel application code, migrations, jobs, commands, services, and the WhatsApp sidecar.  
**Date:** 2026-11-11  
**Meaning:** an implicit state machine is any lifecycle represented by status/state/mode flags, timestamps, counters, or coordinated records without a formal state-machine abstraction.

## Executive summary

The application contains **24 primary implicit state machines**, plus **10 state-like classifications/configuration switches**. Most states are free-form strings stored in `string` columns; transition rules live in controllers, jobs, commands, and services. No central transition map, enum layer, database check constraint, or optimistic-lock version currently guarantees the full lifecycle.

The highest-risk machines are outbound messaging, fee invoices/payments/reversals, payroll, promotion, library circulation, backups, and online payments because they cross transactions, external effects, or multiple records.

## Primary state machines

### 1. User access lifecycle
- **State:** `users.status`
- **States found:** `active`, `suspended`
- **Transitions:** creation → active; access administration can switch active ↔ suspended.
- **Guard:** `EnsureActiveUser` blocks non-active users.
- **Secondary state:** `must_change_password: true → false` after mandatory password replacement.
- **Concern:** status accepts a free string at DB level; suspension should also invalidate existing sessions explicitly.

### 2. Student lifecycle
- **State:** `students.status`
- **States found:** `active`, `withdrawn`, `graduated`
- **Transitions:** admission → active; active → withdrawn; active → graduated during promotion.
- **Correlated fields:** `withdrawn_on`, `withdrawal_reason`.
- **Concern:** no explicit transition service prevents withdrawn/graduated students from being reactivated inconsistently.

### 3. Enrollment lifecycle
- **State:** `enrollments.status`
- **States found:** `draft` (schema default), `active`, `completed`, `graduated`, `withdrawn`
- **Transitions:** enrollment activation; active → completed/graduated during promotion; active → withdrawn with student withdrawal; a new active enrollment is created for promote/repeat.
- **Concern:** schema default `draft` appears disconnected from normal creation paths; student and enrollment states can diverge.

### 4. Academic year lifecycle
- **State:** effectively `academic_years.active`/date range.
- **States:** active/inactive boolean.
- **Transitions:** configuration selects the active year.
- **Concern:** uniqueness of one active year per school is an application invariant rather than an obvious DB invariant.

### 5. Exam publication lifecycle
- **State:** `exam_sessions.status`
- **States found:** `draft`, `published`
- **Transition:** draft → published; stamps `published_at`, `published_by`, then queues result notifications.
- **Concern:** no unpublish/correction lifecycle; publishing guard and completeness validation should be centralized.

### 6. Student mark classification
- **State:** `student_marks.status`
- **States:** `present`, `absent`, `exempt`; missing row is treated as `not_entered`.
- **Transition:** editable through mark entry.
- **Concern:** marks remain editable after exam publication unless separately blocked; `not_entered` is virtual, not persisted.

### 7. Promotion run lifecycle
- **State:** `promotion_runs.status`
- **States:** `draft`, `completed`
- **Transition:** draft → completed, atomically applying per-student decisions.
- **Decision states:** `promote`, `repeat`, `graduate`.
- **Concern:** no review/approved/running/failed state and no safe resume model for a large run.

### 8. Timetable publication lifecycle
- **State:** presence/version of `timetable_publications` plus `published_at`.
- **States:** unpublished → published version(s).
- **Transition:** publication creates a version/snapshot and notification work.
- **Concern:** state is inferred from records rather than a single status; superseded/current semantics need an explicit invariant.

### 9. Student attendance classification
- **State:** `student_attendances.status`
- **States:** `present`, `absent`, `late`, `leave`.
- **Transition:** repeated marking updates the classification.
- **Concern:** this is editable classification rather than a workflow; locking/approval after day close is absent.

### 10. Attendance device lifecycle
- **State:** `attendance_devices.status`
- **States found:** `inactive` by default; operational activation is implied.
- **Event processing state:** `attendance_device_events.processing_status`, default `pending`.
- **Concern:** no complete pending → processed/failed/retry transition implementation was found; this is an incomplete machine.

### 11. Staff attendance classification
- **State:** `staff_attendances.status`
- **States found:** `present` plus UI/domain alternatives.
- **Transition:** upserted attendance record.
- **Concern:** like student attendance, there is no close/approval period.

### 12. Leave request lifecycle
- **State:** `leave_requests.status`
- **States found:** `pending`, `approved`, `rejected`
- **Transition:** pending → approved/rejected.
- **Concern:** transitions are controller-driven; overlap, cancellation, and post-approval amendment states are not modeled.

### 13. Payroll run lifecycle
- **State:** `payroll_runs.status`
- **States:** `draft`, `approved`, `paid`
- **Transitions:** draft → approved → paid.
- **Correlated fields:** `approved_at/by`, `paid_at/by`, accounting journal link; payslips begin as `draft` and are updated with the run.
- **Concern:** payment and journal posting require strict locking/idempotency; no rejected/voided/correction state.

### 14. Fee invoice lifecycle
- **State:** `fee_invoices.status`
- **States:** `issued`, `partially_paid`, `paid`, `overdue`
- **Transitions:** issued → partially_paid/paid on collection; issued/partially_paid → overdue by fine command; reversal moves paid/partially-paid back to issued/partially_paid.
- **Derived state:** status depends on `paid`, `total`, and due date.
- **Concern:** persisted status can drift from amounts/date; reversal currently does not visibly restore `overdue` based on due date.

### 15. Payment lifecycle
- **State:** `payments.status`
- **States:** `posted`, `reversed`
- **Transition:** collection creates posted; approved reversal makes it reversed.
- **Concern:** irreversible finance state should be enforced through domain services and DB constraints, not free-form updates.

### 16. Payment reversal approval lifecycle
- **State:** `payment_reversals.status`
- **States:** `pending`, `approved`
- **Transition:** pending → approved, creating reversal journal, changing payment and invoice.
- **Concern:** no rejected/cancelled state; concurrent approvals need row locking and unique one-effective-reversal enforcement.

### 17. Expense voucher lifecycle
- **State:** `expense_vouchers.status`
- **States:** `pending`, `approved`
- **Transition:** pending → approved with posted journal entry.
- **Concern:** no reject/void/correct state; approval and accounting entry are tightly coupled but implicit.

### 18. Fee concession lifecycle
- **State:** `fee_concessions.status`
- **States:** `pending`, `approved`, `rejected`
- **Transition:** pending → approved/rejected; approval applies financial effect.
- **Concern:** amendment/revocation and duplicate concession rules are not expressed as state transitions.

### 19. Bank reconciliation lifecycle
- **State:** `bank_reconciliations.status`
- **States:** `draft`, `completed`
- **Transition:** draft → completed after difference is zero; stamps completer/time.
- **Concern:** reconciliation items can potentially change independently unless completion immutability is enforced everywhere.

### 20. Online payment transaction lifecycle
- **State:** `payment_gateway_transactions.status`
- **States found:** `created`, provider-returned values, `configuration_required`; webhook records default to `received`.
- **Transition:** created → arbitrary gateway response status.
- **Concern:** this is the least bounded machine: provider status is copied directly, no canonical state map exists, and webhook verification/processed/duplicate/failure lifecycle is not explicit.

### 21. Outbound message lifecycle
- **State:** `outbound_messages.status`
- **States:** `pending_approval`, `approved`, `queued`, `sending`, `sent`, `failed`, `cancelled`
- **Transitions:** creation → pending_approval/approved; pending_approval → approved; approved/failed → queued; queued/approved → sending → sent/failed; failed → approved for retry; pending/approved/failed → cancelled.
- **Related controls:** attempts (<4), schedule, quiet hours, approval timestamps, idempotency key.
- **Concern:** multiple dispatchers can race before `queued`; `sending` can become stranded after worker death; cancellation and retry rules are spread across four components.

### 22. Communication event outcome lifecycle
- **State:** `communication_event_logs.status`
- **States:** `created`, `skipped`.
- **Meaning:** terminal outcome/audit classification rather than an evolving entity.
- **Concern:** skipped reasons are free text; no failed/linked-delivery projection.

### 23. Library circulation lifecycle
- **States:** `library_copies.status` = `available`/`issued`; `library_loans.status` = `issued`/`returned`.
- **Transitions:** available copy + no loan → issued copy + issued loan; return → available copy + returned loan.
- **Concern:** two machines must change together. Missing states include lost, damaged, reserved, written_off, overdue; concurrent issue needs locking.

### 24. Backup lifecycle
- **State:** `backup_records.status`
- **States:** `running`, `completed`, `failed`
- **Transitions:** creation → running → completed/failed.
- **Correlated fields:** start/completion timestamps, checksum, filename, error.
- **Restore guard:** only completed backups may restore.
- **Concern:** a killed process leaves `running` forever; restore itself has no recorded requested/running/succeeded/failed lifecycle.

## State-like classifications and switches

These are finite domains but are not necessarily workflows:

1. **Message template mode:** `automatic`, `approval`, `manual`, `disabled` (configuration strategy). Current code treats `manual` as auto-approved, which deserves product confirmation.
2. **Notice status:** schema default `published`; publication/unpublication lifecycle is not fully modeled.
3. **Complaint status:** `open` → `resolved`, with `resolved_at`; assignment/priority are orthogonal substates.
4. **Inventory item active:** boolean active/inactive.
5. **Fixed asset status:** default `in_use`; likely needs maintenance/disposed/lost lifecycle.
6. **Stock movement type:** receipt/issue/adjustment-like event classification; inventory quantity is derived/mutated state.
7. **Vehicle/route/transport assignment status:** active/inactive service configuration; assignment begins active.
8. **Staff profile status:** active and potential inactive employment classification, but no hire/probation/resignation/termination workflow.
9. **Journal entry status:** currently created directly as `posted`; schema hints at a draft/posted concept but no formal posting/reversal machine beyond linked reversal entries.
10. **WhatsApp connector runtime:** `starting`, `qr_required`, `authenticated`, `connected`, `auth_failure`, `disconnected`, `logged_out`, `failed`, plus provider-facing `not_configured`/`unavailable`. This is in-memory and resets on process restart.

## Cross-machine transitions

The most consequential workflows update several machines together:

- **Student withdrawal:** student active → withdrawn and all active enrollments → withdrawn.
- **Promotion:** run draft → completed; old enrollment active → completed/graduated; new enrollment → active or student → graduated.
- **Fee collection:** invoice state changes; payment → posted; journal entry → posted.
- **Payment reversal:** reversal pending → approved; payment posted → reversed; invoice recalculated; reversal journal posted.
- **Payroll settlement:** payroll approved → paid; payslips change; accounting journal posted.
- **Library issue/return:** copy and loan states must agree.
- **Exam publication:** exam draft → published and message machines are spawned.
- **Timetable publication:** publication version is created and message machines are spawned.

## Structural findings

1. **Free-form state storage:** status columns are predominantly strings with no DB check constraints.
2. **No canonical enums:** state names are duplicated as string literals across migrations, controllers, commands, services, jobs, tests, and views.
3. **Distributed guards:** legal-transition checks are local and uneven; some flows guard source state, others overwrite it.
4. **Timestamp coupling:** `published_at`, `approved_at`, `paid_at`, etc. can theoretically disagree with status.
5. **Concurrency exposure:** select/check/update flows often need `lockForUpdate`, atomic compare-and-set, or unique constraints.
6. **No transition history:** audit middleware records HTTP mutations, but background-job/command transitions and domain reasons do not form a complete per-entity state history.
7. **Recovery gaps:** intermediate states such as `sending`, `running`, and external payment statuses need timeout/recovery policies.
8. **Orthogonal concerns mixed together:** operational status, approval status, payment status, and publication status are sometimes compressed into one field.

## Recommended remediation order

### P0 — financial and external side effects
- Introduce backed enums and dedicated transition services for fee invoice, payment/reversal, payroll, expense, concession, online payment, and outbound messages.
- Add transactional row locks/idempotency constraints and tests for duplicate concurrent requests.
- Define canonical external-payment states and verified webhook processing.

### P1 — multi-record consistency
- Formalize promotion, withdrawal, library circulation, reconciliation, and backup/restore transitions.
- Add invariants ensuring paired records cannot disagree.
- Add stale-state recovery for `sending` messages and `running` backups.

### P2 — academic and HR governance
- Lock marks after publication or add explicit reopen/correction workflow.
- Add reject/cancel/amend states where operationally required.
- Complete biometric event processing lifecycle.

### P3 — maintainability
- Create PHP backed enums per bounded context.
- Use methods such as `approve()`, `publish()`, `reverse()`, and `complete()` instead of raw `update(['status' => ...])`.
- Add DB check constraints where supported, status/timestamp consistency checks, and transition tables/audit events.
- Document each machine with transition diagrams and role permissions.

## Suggested target pattern

Each important machine should have:

- a backed enum defining states;
- one transition service/method defining allowed edges;
- authorization and tenant checks at the boundary;
- a DB transaction and row lock for multi-record changes;
- idempotency for repeatable commands/external callbacks;
- invariant validation before commit;
- a domain transition log (`from`, `to`, actor, reason, correlation ID, timestamp);
- recovery rules for non-terminal states;
- table-driven tests covering every legal and illegal edge.
