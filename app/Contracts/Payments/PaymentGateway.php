<?php

namespace App\Contracts\Payments;

use App\Models\PaymentGatewayTransaction;

interface PaymentGateway
{
    public function initiate(PaymentGatewayTransaction $transaction): array;

    public function verifyWebhook(string $payload, string $signature): bool;

    public function parseWebhook(array $payload): array;
}
