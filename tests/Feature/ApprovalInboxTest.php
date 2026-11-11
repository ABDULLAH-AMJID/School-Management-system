<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\ExpenseVoucher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalInboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_principal_sees_pending_decisions_across_authorized_modules(): void
    {
        $this->seed();
        $u = User::where('email', 'principal@school.test')->firstOrFail();
        $expense = Account::where('type', 'expense')->firstOrFail();
        $cash = Account::where('code', '1000')->firstOrFail();
        ExpenseVoucher::create(['school_id' => $u->school_id, 'number' => 'EV-INBOX', 'expense_date' => today(), 'expense_account_id' => $expense->id, 'payment_account_id' => $cash->id, 'payee' => 'Vendor', 'amount' => 250000, 'description' => 'Office supplies', 'payment_method' => 'cash', 'status' => 'pending', 'submitted_by' => $u->id]);
        $this->actingAs($u)->get(route('approvals.index'))->assertOk()->assertSee('Approval inbox')->assertSee('Expense vouchers')->assertSee('EV-INBOX')->assertSee('Leave requests');
    }

    public function test_teacher_inbox_does_not_leak_finance_or_hr_approvals(): void
    {
        $this->seed();
        $teacher = User::where('email', 'teacher@school.test')->firstOrFail();
        $this->actingAs($teacher)->get(route('approvals.index'))->assertOk()->assertSee('All clear')->assertDontSee('Expense vouchers')->assertDontSee('Leave requests')->assertDontSee('Fee concessions');
    }
}
