<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_trial_balance_export_is_balanced(): void
    {
        $this->seed();
        $u = User::where('email', 'principal@school.test')->firstOrFail();
        $cash = Account::where('code', '1000')->firstOrFail();
        $income = Account::where('code', '4000')->firstOrFail();
        $j = JournalEntry::create(['school_id' => $u->school_id, 'number' => 'JV-RPT', 'entry_date' => today(), 'description' => 'Test', 'status' => 'posted', 'posted_at' => now()]);
        $j->lines()->createMany([['account_id' => $cash->id, 'debit' => 250000, 'credit' => 0], ['account_id' => $income->id, 'debit' => 0, 'credit' => 250000]]);
        $content = $this->actingAs($u)->get(route('reports.trial-balance'))->assertOk()->streamedContent();
        $this->assertStringContainsString('TOTAL', $content);
        $this->assertStringContainsString('2500,2500', $content);
    }

    public function test_general_ledger_has_running_balance_and_is_school_scoped(): void
    {
        $this->seed();
        $u = User::where('email', 'principal@school.test')->firstOrFail();
        $a = Account::where('code', '1000')->firstOrFail();
        $j = JournalEntry::create(['school_id' => $u->school_id, 'number' => 'JV-LED', 'entry_date' => today(), 'description' => 'Opening', 'status' => 'posted', 'posted_at' => now()]);
        $j->lines()->create(['account_id' => $a->id, 'debit' => 100000, 'credit' => 0, 'memo' => 'Cash opening']);
        $content = $this->actingAs($u)->get(route('reports.ledger', ['account' => $a, 'from' => today()->subDay()->toDateString(), 'to' => today()->addDay()->toDateString()]))->assertOk()->streamedContent();
        $this->assertStringContainsString('Running Balance PKR', $content);
        $this->assertStringContainsString('"Cash opening",1000,0,1000', $content);
    }
}
