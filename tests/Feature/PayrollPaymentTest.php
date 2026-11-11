<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_payroll_payment_settles_liability_and_credits_bank(): void
    {
        $this->seed();
        $u = User::where('email', 'principal@school.test')->firstOrFail();
        $this->actingAs($u)->post(route('hr.payroll.generate'), ['period' => '2026-07']);
        $run = PayrollRun::where('period', '2026-07')->firstOrFail();
        $this->actingAs($u)->post(route('hr.payroll.approve', $run))->assertRedirect();
        $bank = Account::where('code', '1010')->firstOrFail();
        $this->actingAs($u)->post(route('hr.payroll.pay', $run), ['payment_account_id' => $bank->id, 'payment_reference' => 'BANK-2026-07'])->assertRedirect();
        $run->refresh();
        $this->assertSame('paid', $run->status);
        $this->assertNotNull($run->paid_at);
        $this->assertSame(0, $run->payslips()->where('status', '!=', 'paid')->count());
        $entry = JournalEntry::with('lines')->findOrFail($run->payment_journal_entry_id);
        $this->assertSame($entry->lines->sum('debit'), $entry->lines->sum('credit'));
        $this->assertSame($bank->id, $entry->lines->firstWhere('credit', '>', 0)->account_id);
    }

    public function test_payroll_cannot_be_paid_before_approval_or_twice(): void
    {
        $this->seed();
        $u = User::where('email', 'principal@school.test')->firstOrFail();
        $bank = Account::where('code', '1010')->firstOrFail();
        $this->actingAs($u)->post(route('hr.payroll.generate'), ['period' => '2026-06']);
        $run = PayrollRun::where('period', '2026-06')->firstOrFail();
        $data = ['payment_account_id' => $bank->id, 'payment_reference' => 'BANK-TEST'];
        $this->actingAs($u)->post(route('hr.payroll.pay', $run), $data)->assertStatus(409);
        $this->actingAs($u)->post(route('hr.payroll.approve', $run));
        $this->actingAs($u)->post(route('hr.payroll.pay', $run), $data)->assertRedirect();
        $this->actingAs($u)->post(route('hr.payroll.pay',$run),$data)->assertStatus(409);
    }
}
