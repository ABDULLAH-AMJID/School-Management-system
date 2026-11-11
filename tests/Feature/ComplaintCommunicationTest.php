<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\Student;
use App\Models\User;
use App\Services\Messaging\TriggerStudentEventMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ComplaintCommunicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_linked_complaint_sends_acknowledgement_and_resolution_to_complainant(): void
    {
        $this->seed();
        Queue::fake();
        $u = User::where('email', 'principal@school.test')->firstOrFail();
        $student = Student::firstOrFail();
        $phone = '+923009991111';
        $this->actingAs($u)->post(route('engagement.complaints.store'), ['source' => 'parent', 'complainant_name' => 'Parent', 'complainant_phone' => $phone, 'student_id' => $student->id, 'category' => 'Academic', 'priority' => 'normal', 'subject' => 'Homework concern', 'description' => 'Details'])->assertRedirect();
        $complaint = Complaint::firstOrFail();
        $this->assertDatabaseHas('outbound_messages', ['recipient' => $phone, 'event' => 'complaint_registered', 'status' => 'approved']);
        $this->actingAs($u)->post(route('engagement.complaints.resolve', $complaint), ['resolution' => 'Discussed with class teacher'])->assertRedirect();
        $this->assertDatabaseHas('outbound_messages', ['recipient' => $phone, 'event' => 'complaint_resolved', 'status' => 'pending_approval']);
        $this->assertDatabaseCount('communication_event_logs', 2);
    }

    public function test_repeated_event_source_does_not_duplicate_complaint_message(): void
    {
        $this->seed();
        Queue::fake();
        $u = User::where('email', 'principal@school.test')->firstOrFail();
        $student = Student::firstOrFail();
        $service = app(TriggerStudentEventMessage::class);
        $service->handle('complaint_registered', 'complaint', 44, $student, $u, ['complaint_reference' => 'CMP-44', 'complaint_subject' => 'Test'], '+923001234567');
        $service->handle('complaint_registered', 'complaint', 44, $student, $u, ['complaint_reference' => 'CMP-44', 'complaint_subject' => 'Test'], '+923001234567');
        $this->assertDatabaseCount('outbound_messages', 1);
    }
}
