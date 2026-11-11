# Cashier and Accountant Guide

## Fee operations

Confirm the student and invoice before collecting payment. Enter the exact amount, method and external reference. The system prevents overpayment and posts a balanced journal automatically. Provide the generated receipt number.

## Structures, fines and concessions

Configure grade/year installments before generation. Each installment may be generated only once. Automated fines are idempotent. Submit concessions with evidence; a separate approver should authorize them.

## Corrections

Never edit or delete a posted payment to correct it. Request a payment reversal with a detailed reason. Approval restores the invoice and creates an inverse journal entry.

## Expenses and reconciliation

Submit expenses against the correct expense and payment accounts. Approved vouchers post to the ledger. Reconcile each cash/bank account against its statement; a run cannot be completed with a non-zero difference.

## Payroll

Generate the period, review every payslip, then obtain approval. Approval accrues salary expense and payroll payable. Payment settles payroll payable against the selected bank/cash account and cannot be repeated.
