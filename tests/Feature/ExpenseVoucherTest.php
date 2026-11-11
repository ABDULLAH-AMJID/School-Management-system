<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\ExpenseVoucher;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseVoucherTest extends TestCase
{
    use RefreshDatabase;

    public function test_expense_requires_approval_then_posts_balanced_journal(): void
    {
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();
        $expense = Account::where('type', 'expense')->firstOrFail();
        $cash = Account::where('code', '1000')->firstOrFail();

        $this->actingAs($principal)->post(route('finance.expenses.store'), [
            'expense_date' => today()->toDateString(), 'expense_account_id' => $expense->id,
            'payment_account_id' => $cash->id, 'payee' => 'Electric Supply Company',
            'amount' => 12500, 'description' => 'Monthly electricity bill', 'payment_method' => 'bank',
        ])->assertRedirect()->assertSessionHas('success');

        $voucher = ExpenseVoucher::firstOrFail();
        $this->assertSame('pending', $voucher->status);
        $this->assertNull($voucher->journal_entry_id);

        $this->actingAs($principal)->post(route('finance.expenses.approve', $voucher))
            ->assertRedirect()->assertSessionHas('success');

        $voucher->refresh();
        $this->assertSame('approved', $voucher->status);
        $this->assertNotNull($voucher->journal_entry_id);
        $lines = $voucher->fresh()->journal_entry_id;
        $journal = JournalEntry::with('lines')->findOrFail($lines);
        $this->assertSame($journal->lines->sum('debit'), $journal->lines->sum('credit'));
        $this->assertSame(1250000, $journal->lines->sum('debit'));
    }

    public function test_approved_expense_cannot_be_approved_twice(): void
    {
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();
        $expense = Account::where('type', 'expense')->firstOrFail();
        $cash = Account::where('code', '1000')->firstOrFail();
        $voucher = ExpenseVoucher::create(['school_id' => $principal->school_id, 'number' => 'EV-TEST', 'expense_date' => today(), 'expense_account_id' => $expense->id, 'payment_account_id' => $cash->id, 'payee' => 'Vendor', 'amount' => 10000, 'description' => 'Supplies', 'payment_method' => 'cash', 'status' => 'pending', 'submitted_by' => $principal->id]);

        $this->actingAs($principal)->post(route('finance.expenses.approve', $voucher))->assertRedirect();
        $this->actingAs($principal)->post(route('finance.expenses.approve', $voucher))->assertSessionHasErrors('voucher');
        $this->assertDatabaseCount('journal_entries', 1);
        $this->assertDatabaseCount('journal_lines', 2);
    }
}
