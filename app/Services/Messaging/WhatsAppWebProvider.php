<?php

namespace App\Services\Messaging;

use App\Contracts\Messaging\MessagingProvider;
use App\Models\OutboundMessage;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class WhatsAppWebProvider implements MessagingProvider
{
    public function send(OutboundMessage $message): string
    {
        $url = config('services.whatsapp.connector_url');
        $token = config('services.whatsapp.connector_token');
        if (! $url || ! $token) {
            throw new RuntimeException('WhatsApp connector is not configured.');
        }$response = Http::acceptJson()->withToken($token)->timeout(20)->retry(2, 500)->post(rtrim($url, '/').'/api/messages', ['to' => $message->recipient, 'text' => $message->body, 'idempotency_key' => $message->idempotency_key]);
        $response->throw();

        return (string) $response->json('message_id');
    }

    public function health(): array
    {
        $url = config('services.whatsapp.connector_url');
        if (! $url) {
            return ['connected' => false, 'state' => 'not_configured'];
        }

return Http::withToken((string) config('services.whatsapp.connector_token'))->timeout(4)->get(rtrim($url, '/').'/api/health')->json();
    }
}
