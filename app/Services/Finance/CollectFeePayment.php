<?php

namespace App\Services\Finance;

use App\Models\Account;
use App\Models\FeeInvoice;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\User;
use App\Services\Messaging\TriggerStudentEventMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CollectFeePayment
{
    public function __construct(private TriggerStudentEventMessage $messenger) {}

    public function handle(FeeInvoice $invoice, int $amount, string $method, ?string $reference, User $actor): Payment
    {
        $payment = DB::transaction(function () use ($invoice, $amount, $method, $reference, $actor) {
            $invoice = FeeInvoice::query()->lockForUpdate()->findOrFail($invoice->id);
            if ($amount <= 0 || $amount > $invoice->balance) {
                throw ValidationException::withMessages(['amount' => 'Payment must be greater than zero and cannot exceed the invoice balance.']);
            }

            $payment = Payment::create(['school_id' => $invoice->school_id, 'fee_invoice_id' => $invoice->id, 'receipt_no' => 'RC-'.now()->format('ymd').'-'.str_pad((string) (Payment::count() + 1), 5, '0', STR_PAD_LEFT), 'amount' => $amount, 'method' => $method, 'reference' => $reference, 'received_at' => now(), 'received_by' => $actor->id, 'status' => 'posted']);
            $invoice->increment('paid', $amount);
            $invoice->refresh()->update(['status' => $invoice->paid >= $invoice->total ? 'paid' : 'partially_paid']);
            $cashCode = $method === 'cash' ? '1000' : '1010';
            $cash = Account::where(['school_id' => $invoice->school_id, 'code' => $cashCode])->firstOrFail();
            $receivable = Account::where(['school_id' => $invoice->school_id, 'code' => '1100'])->firstOrFail();
            $entry = JournalEntry::create(['school_id' => $invoice->school_id, 'number' => 'JV-'.now()->format('ymd').'-'.str_pad((string) (JournalEntry::count() + 1), 5, '0', STR_PAD_LEFT), 'entry_date' => today(), 'reference_type' => Payment::class, 'reference_id' => $payment->id, 'description' => 'Fee receipt '.$payment->receipt_no, 'status' => 'posted', 'created_by' => $actor->id, 'posted_at' => now()]);
            $entry->lines()->createMany([['account_id' => $cash->id, 'debit' => $amount, 'credit' => 0, 'memo' => 'Fee received'], ['account_id' => $receivable->id, 'debit' => 0, 'credit' => $amount, 'memo' => 'Student fee receivable settled']]);

            return $payment;
        });
        $invoice = $invoice->fresh(['student']);
        $this->messenger->handle('fee_payment_received', 'payment', $payment->id, $invoice->student, $actor, ['receipt_no' => $payment->receipt_no, 'amount' => number_format($payment->amount / 100, 2), 'invoice_no' => $invoice->number, 'balance' => number_format($invoice->balance / 100, 2), 'payment_method' => ucfirst($payment->method)]);

        return $payment;
    }
}
