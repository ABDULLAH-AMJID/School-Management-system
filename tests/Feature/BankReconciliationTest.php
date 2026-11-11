<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\BankReconciliation;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankReconciliationTest extends TestCase
{
    use RefreshDatabase;

    public function test_balanced_statement_can_be_completed_and_locked(): void
    {
        $this->seed();
        $u = User::where('email', 'principal@school.test')->firstOrFail();
        $a = Account::where('code', '1010')->firstOrFail();
        $j = JournalEntry::create(['school_id' => $u->school_id, 'number' => 'JV-BANK', 'entry_date' => today(), 'description' => 'Deposit', 'status' => 'posted', 'created_by' => $u->id, 'posted_at' => now()]);
        $j->lines()->create(['account_id' => $a->id, 'debit' => 500000, 'credit' => 0]);
        $this->actingAs($u)->post(route('reconciliation.store'), ['account_id' => $a->id, 'statement_date' => today()->toDateString(), 'statement_balance' => 5000])->assertRedirect();
        $run = BankReconciliation::firstOrFail();
        $this->assertSame(0, $run->difference);
        $this->actingAs($u)->post(route('reconciliation.complete', $run))->assertRedirect();
        $this->assertSame('completed', $run->refresh()->status);
        $this->actingAs($u)->post(route('reconciliation.complete', $run))->assertStatus(422);
    }

    public function test_reconciliation_with_difference_cannot_complete(): void
    {
        $this->seed();
        $u = User::where('email', 'principal@school.test')->firstOrFail();
        $a = Account::where('code', '1010')->firstOrFail();
        $this->actingAs($u)->post(route('reconciliation.store'), ['account_id' => $a->id, 'statement_date' => today()->toDateString(), 'statement_balance' => 100])->assertRedirect();
        $run = BankReconciliation::firstOrFail();
        $this->assertSame(10000, $run->difference);
        $this->actingAs($u)->post(route('reconciliation.complete', $run))->assertStatus(422);
        $this->assertSame('draft', $run->refresh()->status);
    }
}
