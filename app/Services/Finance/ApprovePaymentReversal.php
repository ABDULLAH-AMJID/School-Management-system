<?php

namespace App\Services\Finance;

use App\Models\FeeInvoice;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\PaymentReversal;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ApprovePaymentReversal
{
    public function handle(PaymentReversal $reversal, User $approver): PaymentReversal
    {
        return DB::transaction(function () use ($reversal, $approver) {
            $r = PaymentReversal::lockForUpdate()->findOrFail($reversal->id);
            if ($r->status !== 'pending') {
                throw ValidationException::withMessages(['reversal' => 'Only pending reversals may be approved.']);
            }$payment = Payment::lockForUpdate()->findOrFail($r->payment_id);
            if ($payment->status !== 'posted') {
                throw ValidationException::withMessages(['reversal' => 'Payment is no longer posted.']);
            }$invoice = FeeInvoice::lockForUpdate()->findOrFail($payment->fee_invoice_id);
            $original = JournalEntry::with('lines')->where(['reference_type' => Payment::class, 'reference_id' => $payment->id, 'status' => 'posted'])->firstOrFail();
            $entry = JournalEntry::create(['school_id' => $r->school_id, 'number' => 'RV-'.now()->format('ymd').'-'.str_pad((string) $r->id, 5, '0', STR_PAD_LEFT), 'entry_date' => today(), 'reference_type' => PaymentReversal::class, 'reference_id' => $r->id, 'description' => 'Reversal of '.$payment->receipt_no.': '.$r->reason, 'status' => 'posted', 'created_by' => $approver->id, 'reversal_of_id' => $original->id, 'posted_at' => now()]);
            foreach ($original->lines as $line) {
                $entry->lines()->create(['account_id' => $line->account_id, 'debit' => $line->credit, 'credit' => $line->debit, 'memo' => 'Reversal: '.$line->memo]);
            }$invoice->decrement('paid', $r->amount);
            $invoice->refresh();
            $invoiceStatus = $invoice->paid <= 0 ? 'issued' : 'partially_paid';
            if ($invoice->paid < $invoice->total && $invoice->due_date->isPast()) {
                $invoiceStatus = 'overdue';
            }
            $invoice->update(['status' => $invoiceStatus]);
            $payment->update(['status' => 'reversed']);
            $r->update(['status' => 'approved', 'approved_by' => $approver->id, 'approved_at' => now(), 'journal_entry_id' => $entry->id]);

            return $r->refresh();
        });
    }
}
