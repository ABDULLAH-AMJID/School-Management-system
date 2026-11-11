<?php

namespace App\Services\Messaging;

use App\Jobs\SendOutboundMessage;
use App\Models\CommunicationPreference;
use App\Models\MessageTemplate;
use App\Models\OutboundMessage;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class QueueMessage
{
    public function create(MessageTemplate $template, Student $student, string $recipient, User $actor, array $variables = [], mixed $scheduledFor = null): OutboundMessage
    {
        $body = $template->body;
        foreach ($variables + ['student_name' => $student->full_name, 'admission_no' => $student->admission_no] as $key => $value) {
            $body = str_replace('{{'.$key.'}}', (string) $value, $body);
        }
        $blocked = CommunicationPreference::where(['school_id' => $student->school_id, 'recipient' => $recipient, 'channel' => 'whatsapp', 'opted_out' => true])->whereIn('event', ['*', $template->event])->exists();
        if ($blocked) {
            throw ValidationException::withMessages(['recipient' => 'This recipient has opted out of this communication.']);
        }
        if ($template->mode === 'disabled') {
            throw ValidationException::withMessages(['template_id' => 'This message event is disabled.']);
        }
        $auto = in_array($template->mode, ['automatic', 'manual'], true);
        $message = OutboundMessage::create(['public_id' => (string) Str::ulid(), 'school_id' => $student->school_id, 'template_id' => $template->id, 'student_id' => $student->id, 'provider' => 'whatsapp_web', 'recipient' => $recipient, 'event' => $template->event, 'body' => $body, 'status' => $auto ? 'approved' : 'pending_approval', 'approved_by' => $auto ? $actor->id : null, 'approved_at' => $auto ? now() : null, 'created_by' => $actor->id, 'scheduled_for' => $scheduledFor ?: now(), 'idempotency_key' => (string) Str::uuid(), 'metadata' => ['variables' => $variables]]);
        if ($auto) {
            SendOutboundMessage::dispatch($message->id);
        }

        return $message;
    }
}
