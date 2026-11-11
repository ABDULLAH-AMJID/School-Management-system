<?php

namespace Tests\Feature;

use App\Jobs\SendOutboundMessage;
use App\Models\MessageTemplate;
use App\Models\OutboundMessage;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CommunicationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_message_is_rendered_and_waits_for_approval(): void
    {
        Queue::fake();
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();
        $student = Student::firstOrFail();
        $template = MessageTemplate::where('event', 'absence')->firstOrFail();

        $this->actingAs($principal)->post(route('communication.store'), [
            'template_id' => $template->id,
            'student_id' => $student->id,
            'recipient' => '+923001234567',
        ])->assertRedirect()->assertSessionHas('success');

        $message = OutboundMessage::firstOrFail();
        $this->assertSame('pending_approval', $message->status);
        $this->assertStringContainsString($student->full_name, $message->body);
        $this->assertNotEmpty($message->idempotency_key);
        Queue::assertNothingPushed();
    }

    public function test_principal_can_approve_pending_message(): void
    {
        Queue::fake();
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();
        $student = Student::firstOrFail();
        $template = MessageTemplate::firstOrFail();
        $this->actingAs($principal)->post(route('communication.store'), ['template_id' => $template->id, 'student_id' => $student->id, 'recipient' => '+923001234567']);
        $message = OutboundMessage::firstOrFail();

        $this->actingAs($principal)->post(route('communication.approve', $message))->assertRedirect();

        $this->assertSame('approved', $message->fresh()->status);
        $this->assertSame($principal->id, $message->fresh()->approved_by);
        Queue::assertPushed(SendOutboundMessage::class, fn ($job) => $job->messageId === $message->id);
    }

    public function test_invalid_international_number_is_rejected(): void
    {
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();
        $this->actingAs($principal)->post(route('communication.store'), [
            'template_id' => MessageTemplate::first()->id,
            'student_id' => Student::first()->id,
            'recipient' => '03001234567',
        ])->assertSessionHasErrors('recipient');
    }
}
