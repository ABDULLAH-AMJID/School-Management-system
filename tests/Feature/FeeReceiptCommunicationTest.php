<?php

namespace Tests\Feature;

use App\Models\FeeInvoice;
use App\Models\Guardian;
use App\Models\OutboundMessage;
use App\Models\User;
use App\Services\Finance\CollectFeePayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class FeeReceiptCommunicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_posted_fee_payment_creates_guardian_receipt_message(): void
    {
        $this->seed();
        $u = User::where('email', 'principal@school.test')->firstOrFail();
        $invoice = FeeInvoice::firstOrFail();
        $g = Guardian::create(['school_id' => $invoice->school_id, 'public_id' => (string) Str::ulid(), 'name' => 'Fee Parent', 'relationship' => 'Father', 'phone' => '+923001110000', 'whatsapp_opt_in' => true]);
        $invoice->student->guardians()->attach($g, ['primary_contact' => true, 'fee_responsible' => true]);
        $payment = app(CollectFeePayment::class)->handle($invoice, 100000, 'cash', null, $u);
        $message = OutboundMessage::where('event', 'fee_payment_received')->firstOrFail();
        $this->assertSame('+923001110000', $message->recipient);
        $this->assertSame('pending_approval', $message->status);
        $this->assertStringContainsString($payment->receipt_no, $message->body);
        $this->assertStringContainsString('1,000.00', $message->body);
        $this->assertDatabaseHas('communication_event_logs', ['event' => 'fee_payment_received', 'source_id' => (string) $payment->id, 'status' => 'created']);
    }

    public function test_payment_still_posts_when_no_guardian_recipient_exists(): void
    {
        $this->seed();
        $u = User::where('email', 'principal@school.test')->firstOrFail();
        $invoice = FeeInvoice::firstOrFail();
        $payment = app(CollectFeePayment::class)->handle($invoice, 10000, 'cash', null, $u);
        $this->assertSame('posted', $payment->status);
        $this->assertDatabaseHas('communication_event_logs',['event' => 'fee_payment_received', 'status' => 'skipped']);
    }
}
