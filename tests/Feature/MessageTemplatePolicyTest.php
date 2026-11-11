<?php

namespace Tests\Feature;

use App\Jobs\SendOutboundMessage;
use App\Models\MessageTemplate;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MessageTemplatePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_principal_can_create_template_with_quiet_hours(): void
    {
        $this->seed();
        $u = User::where('email', 'principal@school.test')->firstOrFail();
        $this->actingAs($u)->post(route('communication.templates.store'), ['event' => 'absence_alert', 'name' => 'Absence Alert', 'body' => 'Hello {{student_name}}', 'mode' => 'approval', 'quiet_from' => '21:00', 'quiet_until' => '07:00'])->assertRedirect();
        $this->assertDatabaseHas('message_templates', ['event' => 'absence_alert', 'mode' => 'approval', 'quiet_from' => '21:00']);
    }

    public function test_disabled_policy_blocks_message_and_manual_policy_queues_immediately(): void
    {
        $this->seed();
        Queue::fake();
        $u = User::where('email', 'principal@school.test')->firstOrFail();
        $student = Student::firstOrFail();
        $template = MessageTemplate::firstOrFail();
        $template->update(['mode' => 'disabled']);
        $payload = ['template_id' => $template->id, 'student_id' => $student->id, 'recipient' => '+923001234567'];
        $this->actingAs($u)->post(route('communication.store'), $payload)->assertSessionHasErrors('template_id');
        $template->update(['mode' => 'manual']);
        $this->actingAs($u)->post(route('communication.store'), $payload)->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseHas('outbound_messages', ['template_id' => $template->id, 'status' => 'approved']);
        Queue::assertPushed(SendOutboundMessage::class);
    }

    public function test_custom_message_can_be_scheduled_for_future_delivery(): void
    {
        $this->seed();
        Queue::fake();
        $u = User::where('email', 'principal@school.test')->firstOrFail();
        $student = Student::firstOrFail();
        $template = MessageTemplate::firstOrFail();
        $template->update(['mode' => 'approval']);
        $when = now()->addHour()->startOfMinute();
        $this->actingAs($u)->post(route('communication.store'), ['template_id' => $template->id, 'student_id' => $student->id, 'recipient' => '+923001234567', 'scheduled_for' => $when->toDateTimeString()])->assertRedirect();
        $this->assertDatabaseHas('outbound_messages', ['template_id' => $template->id, 'scheduled_for' => $when->toDateTimeString(), 'status' => 'pending_approval']);
    }
}
