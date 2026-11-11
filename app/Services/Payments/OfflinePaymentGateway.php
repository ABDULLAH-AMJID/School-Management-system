<?php

namespace App\Services\Payments;

use App\Contracts\Payments\PaymentGateway;
use App\Models\PaymentGatewayTransaction;

final class OfflinePaymentGateway implements PaymentGateway
{
    public function initiate(PaymentGatewayTransaction $transaction): array
    {
        return ['status' => 'configuration_required', 'message' => 'Connect an approved Pakistani payment provider before enabling online checkout.'];
    }

    public function verifyWebhook(string $payload, string $signature): bool
    {
        return false;
    }

    public function parseWebhook(array $payload): array
    {
        return [];
    }
}
