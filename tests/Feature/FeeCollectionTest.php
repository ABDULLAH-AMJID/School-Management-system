<?php

namespace Tests\Feature;

use App\Models\FeeInvoice;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeeCollectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_fee_collection_posts_balanced_journal_and_receipt(): void
    {
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();
        $invoice = FeeInvoice::firstOrFail();

        $this->actingAs($principal)->post(route('finance.collect', $invoice), [
            'amount' => 5000,
            'method' => 'cash',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('payments', ['fee_invoice_id' => $invoice->id, 'amount' => 500000]);
        $this->assertSame(500000, $invoice->fresh()->paid);
        $entry = JournalEntry::with('lines')->firstOrFail();
        $this->assertSame($entry->lines->sum('debit'), $entry->lines->sum('credit'));
        $this->assertSame(500000, $entry->lines->sum('debit'));
        $this->assertSame('posted', Payment::first()->status);
    }

    public function test_overpayment_is_rejected_and_nothing_is_posted(): void
    {
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();
        $invoice = FeeInvoice::firstOrFail();

        $this->actingAs($principal)->post(route('finance.collect', $invoice), [
            'amount' => ($invoice->balance / 100) + 1,
            'method' => 'cash',
        ])->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('journal_entries', 0);
    }
}
