<?php

namespace App\Services\Payments;

use App\Contracts\Payments\PaymentGateway;
use App\Models\FeeInvoice;
use App\Models\PaymentGatewayTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class InitiateOnlinePayment
{
    public function __construct(private PaymentGateway $gateway) {}

    public function handle(FeeInvoice $invoice, int $amount, string $provider, User $user, string $key): PaymentGatewayTransaction
    {
        if ($amount < 1 || $amount > $invoice->balance) {
            throw ValidationException::withMessages(['amount' => 'Payment amount exceeds the invoice balance.']);
        }

return DB::transaction(function () use ($invoice, $amount, $provider, $user, $key) {
            $existing = PaymentGatewayTransaction::where('idempotency_key', $key)->first();
            if ($existing) {
                return $existing;
            }$tx = PaymentGatewayTransaction::create(['public_id' => (string) Str::ulid(), 'school_id' => $user->school_id, 'fee_invoice_id' => $invoice->id, 'provider' => $provider, 'idempotency_key' => $key, 'amount' => $amount, 'currency' => 'PKR', 'status' => 'created', 'request_payload' => ['invoice' => $invoice->number, 'amount' => $amount]]);
            $response = $this->gateway->initiate($tx);
            $tx->update(['status' => $response['status'], 'response_payload' => $response]);

            return $tx->refresh();
        });
    }
}
