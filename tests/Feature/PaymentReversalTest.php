<?php

namespace Tests\Feature;

use App\Models\FeeInvoice;
use App\Models\JournalEntry;
use App\Models\PaymentReversal;
use App\Models\User;
use App\Services\Finance\CollectFeePayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentReversalTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_reversal_restores_invoice_and_posts_inverse_journal(): void
    {
        $this->seed();
        $u = User::where('email', 'principal@school.test')->firstOrFail();
        $payment = app(CollectFeePayment::class)->handle(FeeInvoice::firstOrFail(), 10000, 'cash', null, $u);
        $invoice = FeeInvoice::findOrFail($payment->fee_invoice_id);
        $paidBefore = $invoice->paid;
        $this->actingAs($u)->post(route('payment-reversals.store', $payment), ['reason' => 'Receipt entered against the wrong student'])->assertRedirect();
        $r = PaymentReversal::firstOrFail();
        $this->assertSame('pending', $r->status);
        $this->actingAs($u)->post(route('payment-reversals.approve', $r))->assertRedirect();
        $this->assertSame('reversed', $payment->refresh()->status);
        $this->assertSame($paidBefore - $payment->amount, $invoice->refresh()->paid);
        $entry = JournalEntry::with('lines')->findOrFail($r->refresh()->journal_entry_id);
        $this->assertNotNull($entry->reversal_of_id);
        $this->assertSame($entry->lines->sum('debit'), $entry->lines->sum('credit'));
    }

    public function test_payment_cannot_be_reversed_twice(): void
    {
        $this->seed();
        $u = User::where('email', 'principal@school.test')->firstOrFail();
        $payment = app(CollectFeePayment::class)->handle(FeeInvoice::firstOrFail(), 10000, 'cash', null, $u);
        $r = PaymentReversal::create(['school_id' => $payment->school_id, 'payment_id' => $payment->id, 'amount' => $payment->amount, 'reason' => 'Duplicate receipt correction', 'requested_by' => $u->id]);
        $this->actingAs($u)->post(route('payment-reversals.approve', $r))->assertRedirect();
        $this->actingAs($u)->post(route('payment-reversals.approve', $r))->assertSessionHasErrors('reversal');
    }
}
