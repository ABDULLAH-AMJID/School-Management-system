<?php

namespace App\Services\Finance;

use App\Models\ExpenseVoucher;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ApproveExpenseVoucher
{
    public function handle(ExpenseVoucher $voucher, User $approver): ExpenseVoucher
    {
        return DB::transaction(function () use ($voucher, $approver) {
            $voucher = ExpenseVoucher::lockForUpdate()->findOrFail($voucher->id);
            if ($voucher->status !== 'pending') {
                throw ValidationException::withMessages(['voucher' => 'Only pending vouchers may be approved.']);
            }$journal = JournalEntry::create(['school_id' => $voucher->school_id, 'number' => 'JV-'.now()->format('Ymd').'-'.str_pad((string) $voucher->id, 6, '0', STR_PAD_LEFT), 'entry_date' => $voucher->expense_date, 'reference_type' => 'expense_voucher', 'reference_id' => $voucher->id, 'description' => $voucher->description, 'status' => 'posted', 'created_by' => $approver->id, 'posted_at' => now()]);
            $journal->lines()->createMany([['account_id' => $voucher->expense_account_id, 'debit' => $voucher->amount, 'credit' => 0, 'memo' => $voucher->payee], ['account_id' => $voucher->payment_account_id, 'debit' => 0, 'credit' => $voucher->amount, 'memo' => $voucher->payee]]);
            $voucher->update(['status' => 'approved', 'approved_by' => $approver->id, 'approved_at' => now(), 'journal_entry_id' => $journal->id]);

            return $voucher->refresh();
        });
    }
}
