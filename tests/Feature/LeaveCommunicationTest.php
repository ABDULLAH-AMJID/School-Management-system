<?php

namespace Tests\Feature;

use App\Models\LeaveRequest;
use App\Models\OutboundMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class LeaveCommunicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_submit_own_leave_request(): void
    {
        $this->seed();
        $teacher = User::where('email', 'teacher@school.test')->firstOrFail();
        $this->actingAs($teacher)->post(route('hr.leaves.store'), ['type' => 'casual', 'starts_on' => today()->addDays(5)->toDateString(), 'ends_on' => today()->addDays(7)->toDateString(), 'reason' => 'Family event'])->assertRedirect()->assertSessionHas('success');
        $leave = LeaveRequest::latest('id')->firstOrFail();
        $this->assertSame('pending', $leave->status);
        $this->assertSame(3, $leave->days);
        $this->assertSame($teacher->id, $leave->staffProfile->user_id);
    }

    public function test_leave_decision_creates_direct_staff_whatsapp_message_once(): void
    {
        $this->seed();
        Queue::fake();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();
        $teacher = User::where('email', 'teacher@school.test')->firstOrFail();
        $teacher->update(['phone' => '+923001119999']);
        $leave = LeaveRequest::firstOrFail();
        $this->actingAs($principal)->post(route('hr.leaves.review', $leave), ['decision' => 'approved', 'review_note' => 'Approved by principal'])->assertRedirect();
        $this->assertDatabaseHas('outbound_messages', ['recipient' => '+923001119999', 'event' => 'leave_reviewed', 'status' => 'pending_approval']);
        $message = OutboundMessage::where('event', 'leave_reviewed')->firstOrFail();
        $this->assertStringContainsString('approved', $message->body);
        Queue::assertNothingPushed();
        $this->assertDatabaseCount('communication_event_logs', 1);
    }
}
