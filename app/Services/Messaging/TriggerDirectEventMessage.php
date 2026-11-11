<?php

namespace App\Services\Messaging;

use App\Jobs\SendOutboundMessage;
use App\Models\CommunicationEventLog;
use App\Models\CommunicationPreference;
use App\Models\MessageTemplate;
use App\Models\OutboundMessage;
use App\Models\User;
use Illuminate\Support\Str;

final class TriggerDirectEventMessage
{
    public function handle(int $schoolId, string $event, string $sourceType, string|int $sourceId, string $recipient, User $actor, array $variables = []): ?CommunicationEventLog
    {
        if (CommunicationEventLog::where(['event' => $event, 'source_type' => $sourceType, 'source_id' => (string) $sourceId])->whereNull('student_id')->exists()) {
            return null;
        }$template = MessageTemplate::where(['school_id' => $schoolId, 'event' => $event, 'active' => true])->first();
        $blocked = CommunicationPreference::where(['school_id' => $schoolId, 'recipient' => $recipient, 'channel' => 'whatsapp', 'opted_out' => true])->whereIn('event', ['*', $event])->exists();
        if (! $template || $template->mode === 'disabled' || $blocked) {
            return CommunicationEventLog::create(['school_id' => $schoolId, 'event' => $event, 'source_type' => $sourceType, 'source_id' => $sourceId, 'status' => 'skipped', 'detail' => ! $template ? 'No active template' : ($blocked ? 'Recipient opted out' : 'Event disabled')]);
        }$body = $template->body;
        foreach ($variables as $key => $value) {
            $body = str_replace('{{'.$key.'}}', (string) $value, $body);
        }$approved = in_array($template->mode, ['automatic', 'manual'], true);
        $message = OutboundMessage::create(['public_id' => (string) Str::ulid(), 'school_id' => $schoolId, 'template_id' => $template->id, 'provider' => 'whatsapp_web', 'recipient' => $recipient, 'event' => $event, 'body' => $body, 'status' => $approved ? 'approved' : 'pending_approval', 'scheduled_for' => now(), 'approved_by' => $approved ? $actor->id : null, 'approved_at' => $approved ? now() : null, 'created_by' => $actor->id, 'idempotency_key' => (string) Str::uuid(), 'metadata' => ['variables' => $variables]]);
        if ($approved) {
            SendOutboundMessage::dispatch($message->id);
        }

return CommunicationEventLog::create(['school_id' => $schoolId, 'event' => $event, 'source_type' => $sourceType, 'source_id' => $sourceId, 'outbound_message_id' => $message->id, 'status' => 'created']);
    }
}
