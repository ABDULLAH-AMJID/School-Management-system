<?php

namespace App\Services\Messaging;

use App\Jobs\SendOutboundMessage;
use App\Models\MessageTemplate;
use App\Models\OutboundMessage;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Str;

final class QueueMessage
{
    public function create(MessageTemplate $template, Student $student, string $recipient, User $actor, array $variables = []): OutboundMessage
    {
        $body = $template->body;
        foreach ($variables + ['student_name' => $student->full_name, 'admission_no' => $student->admission_no] as $key => $value) {
            $body = str_replace('{{'.$key.'}}', (string) $value, $body);
        } $auto = $template->mode === 'automatic';
        $message = OutboundMessage::create(['public_id' => (string) Str::ulid(), 'school_id' => $student->school_id, 'template_id' => $template->id, 'student_id' => $student->id, 'provider' => 'whatsapp_web', 'recipient' => $recipient, 'event' => $template->event, 'body' => $body, 'status' => $auto ? 'approved' : 'pending_approval', 'approved_by' => $auto ? $actor->id : null, 'approved_at' => $auto ? now() : null, 'created_by' => $actor->id, 'scheduled_for' => now(), 'idempotency_key' => (string) Str::uuid(), 'metadata' => ['variables' => $variables]]);
        if ($auto) {
            SendOutboundMessage::dispatch($message->id);
        }

return $message;
    }
}
