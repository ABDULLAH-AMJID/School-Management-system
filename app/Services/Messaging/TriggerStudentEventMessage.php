<?php

namespace App\Services\Messaging;

use App\Models\CommunicationEventLog;
use App\Models\MessageTemplate;
use App\Models\Student;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class TriggerStudentEventMessage
{
    public function __construct(private QueueMessage $queue) {}

    public function handle(string $event, string $sourceType, string|int $sourceId, Student $student, User $actor, array $variables = [], ?string $recipient = null): ?CommunicationEventLog
    {
        if (CommunicationEventLog::where(['event' => $event, 'source_type' => $sourceType, 'source_id' => (string) $sourceId, 'student_id' => $student->id])->exists()) {
            return null;
        }$template = MessageTemplate::where(['school_id' => $student->school_id, 'event' => $event, 'active' => true])->first();
        $guardian = $student->guardians()->wherePivot('primary_contact', true)->first() ?? $student->guardians()->first();
        $recipient ??= $guardian?->whatsapp_phone ?: $guardian?->phone;
        if (! $template || ! $recipient) {
            return CommunicationEventLog::create(['school_id' => $student->school_id, 'event' => $event, 'source_type' => $sourceType, 'source_id' => $sourceId, 'student_id' => $student->id, 'status' => 'skipped', 'detail' => $template ? 'No recipient phone' : 'No active template']);
        }try {
            $message = $this->queue->create($template, $student, $recipient, $actor, $variables);

            return CommunicationEventLog::create(['school_id' => $student->school_id, 'event' => $event, 'source_type' => $sourceType, 'source_id' => $sourceId, 'student_id' => $student->id, 'outbound_message_id' => $message->id, 'status' => 'created']);
        } catch (ValidationException $e) {
            return CommunicationEventLog::create(['school_id' => $student->school_id, 'event' => $event, 'source_type' => $sourceType, 'source_id' => $sourceId, 'student_id' => $student->id, 'status' => 'skipped', 'detail' => $e->getMessage()]);
        }
    }
}
