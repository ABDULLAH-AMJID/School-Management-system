<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollAccountingTest extends TestCase
{
    use RefreshDatabase;

    public function test_payroll_approval_posts_balanced_salary_accrual(): void
    {
        $this->seed();
        $u = User::where('email', 'principal@school.test')->firstOrFail();
        $this->actingAs($u)->post(route('hr.payroll.generate'), ['period' => '2026-09'])->assertRedirect();
        $run = PayrollRun::where('period', '2026-09')->firstOrFail();
        $this->actingAs($u)->post(route('hr.payroll.approve', $run))->assertRedirect();
        $run->refresh();
        $entry = JournalEntry::with('lines')->findOrFail($run->journal_entry_id);
        $this->assertSame(JournalEntry::class, get_class($entry));
        $this->assertSame($entry->lines->sum('debit'), $entry->lines->sum('credit'));
        $this->assertSame($run->payslips()->sum('net_salary'), $entry->lines->sum('debit'));
        $this->assertSame(Account::where('code', '5000')->value('id'), $entry->lines->firstWhere('debit', '>', 0)->account_id);
        $this->assertSame(Account::where('code', '2100')->value('id'), $entry->lines->firstWhere('credit', '>', 0)->account_id);
    }

    public function test_approved_payroll_cannot_post_a_second_journal(): void
    {
        $this->seed();
        $u = User::where('email', 'principal@school.test')->firstOrFail();
        $this->actingAs($u)->post(route('hr.payroll.generate'), ['period' => '2026-08']);
        $run = PayrollRun::where('period', '2026-08')->firstOrFail();
        $this->actingAs($u)->post(route('hr.payroll.approve', $run))->assertRedirect();
        $this->actingAs($u)->post(route('hr.payroll.approve', $run))->assertStatus(409);
        $this->assertSame(1, JournalEntry::where('reference_type', PayrollRun::class)->where('reference_id', $run->id)->count());
    }
}
