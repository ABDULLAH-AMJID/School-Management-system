<?php

namespace Tests\Feature;

use App\Models\Guardian;
use App\Models\MessageTemplate;
use App\Models\OutboundMessage;
use App\Models\Student;
use App\Models\User;
use App\Services\Messaging\TriggerStudentEventMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AttendanceCommunicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_absence_event_creates_guardian_message_once(): void
    {
        $this->seed();
        $u = User::where('email', 'principal@school.test')->firstOrFail();
        $student = Student::firstOrFail();
        $guardian = Guardian::create(['school_id' => $student->school_id, 'public_id' => (string) Str::ulid(), 'name' => 'Test Guardian', 'relationship' => 'Father', 'phone' => '+923001112222', 'whatsapp_opt_in' => true]);
        $student->guardians()->attach($guardian, ['primary_contact' => true, 'fee_responsible' => true]);
        $template = MessageTemplate::where('event', 'student_absent')->first() ?? MessageTemplate::create(['school_id' => $student->school_id, 'event' => 'student_absent', 'name' => 'Absence', 'body' => '{{student_name}} absent on {{attendance_date}}', 'mode' => 'approval', 'active' => true]);
        $service = app(TriggerStudentEventMessage::class);
        $first = $service->handle('student_absent', 'attendance_session', 999, $student, $u, ['attendance_date' => '2026-11-11']);
        $second = $service->handle('student_absent', 'attendance_session', 999, $student, $u, ['attendance_date' => '2026-11-11']);
        $this->assertSame('created', $first->status);
        $this->assertNull($second);
        $this->assertDatabaseCount('outbound_messages', 1);
        $this->assertDatabaseHas('outbound_messages', ['recipient' => '+923001112222', 'status' => 'pending_approval']);
        $this->assertStringContainsString('2026-11-11', $first->fresh()->outbound_message_id ? OutboundMessage::find($first->outbound_message_id)->body : '');
    }

    public function test_event_is_safely_logged_as_skipped_without_template(): void
    {
        $this->seed();
        $u = User::where('email', 'principal@school.test')->firstOrFail();
        $student = Student::firstOrFail();
        $log = app(TriggerStudentEventMessage::class)->handle('unknown_event', 'student', 1, $student, $u);
        $this->assertSame('skipped', $log->status);
        $this->assertSame('No active template', $log->detail);
        $this->assertDatabaseCount('outbound_messages', 0);
    }
}
