<?php

namespace App\Jobs;

use App\Contracts\Messaging\MessagingProvider;
use App\Models\OutboundMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SendOutboundMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    public array $backoff = [60, 300, 900];

    public function __construct(public int $messageId) {}

    public function handle(): void
    {
        $message = OutboundMessage::findOrFail($this->messageId);
        if (! in_array($message->status, ['approved', 'queued'], true)) {
            return;
        }$message->update(['status' => 'sending']);
        try {
            $provider = app(MessagingProvider::class);
            $providerId = $provider->send($message);
            $message->update(['status' => 'sent', 'provider_message_id' => $providerId, 'sent_at' => now(), 'last_error' => null]);
        } catch (Throwable $e) {
            $message->increment('attempts');
            $message->update(['status' => 'failed', 'last_error' => mb_substr($e->getMessage(), 0, 1000)]);
            throw $e;
        }
    }
}
