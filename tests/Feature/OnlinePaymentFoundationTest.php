<?php

namespace Tests\Feature;

use App\Models\FeeInvoice;
use App\Models\PaymentGatewayTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OnlinePaymentFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_request_is_idempotent_and_safely_requires_configuration(): void
    {
        $this->seed();
        $u = User::where('email', 'principal@school.test')->firstOrFail();
        $invoice = FeeInvoice::firstOrFail();
        $key = (string) Str::uuid();
        $data = ['amount' => 100, 'provider' => 'easypaisa', 'idempotency_key' => $key];
        $this->actingAs($u)->post(route('payments.online.store', $invoice), $data)->assertRedirect()->assertSessionHas('success');
        $tx = PaymentGatewayTransaction::firstOrFail();
        $this->assertSame('configuration_required', $tx->status);
        $this->assertSame(10000, $tx->amount);
        $this->actingAs($u)->post(route('payments.online.store', $invoice), $data)->assertRedirect();
        $this->assertDatabaseCount('payment_gateway_transactions', 1);
    }

    public function test_online_payment_cannot_exceed_invoice_balance(): void
    {
        $this->seed();
        $u = User::where('email', 'principal@school.test')->firstOrFail();
        $invoice = FeeInvoice::firstOrFail();
        $this->actingAs($u)->post(route('payments.online.store', $invoice), ['amount' => ($invoice->balance / 100) + 1, 'provider' => 'jazzcash', 'idempotency_key' => (string) Str::uuid()])->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('payment_gateway_transactions',0);
    }
}
