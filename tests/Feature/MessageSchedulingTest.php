<?php

namespace Tests\Feature;

use App\Jobs\SendOutboundMessage;
use App\Models\MessageTemplate;
use App\Models\OutboundMessage;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class MessageSchedulingTest extends TestCase
{
    use RefreshDatabase;

    public function test_due_message_is_queued_but_future_message_waits(): void
    {
        $this->seed();
        Queue::fake();
        $school = School::first();
        $template = MessageTemplate::first();
        $template->update(['quiet_from' => null, 'quiet_until' => null]);
        $due = $this->message($school->id, $template->id, now()->subMinute());
        $future = $this->message($school->id, $template->id, now()->addHour());
        $this->artisan('school:messages:dispatch')->expectsOutput('Queued 1 message(s) for delivery.')->assertSuccessful();
        $this->assertSame('queued', $due->refresh()->status);
        $this->assertSame('approved', $future->refresh()->status);
        Queue::assertPushed(SendOutboundMessage::class, fn ($job) => $job->messageId === $due->id);
    }

    public function test_quiet_hours_block_dispatch_and_failed_messages_have_retry_cap(): void
    {
        $this->seed();
        Queue::fake();
        $school = School::first();
        $template = MessageTemplate::first();
        $template->update(['quiet_from' => '00:00', 'quiet_until' => '23:59']);
        $quiet = $this->message($school->id, $template->id, now()->subMinute());
        $failed = $this->message($school->id, null, now()->subHour(), 'failed', 4);
        $this->artisan('school:messages:dispatch')->expectsOutput('Queued 0 message(s) for delivery.');
        $this->assertSame('approved', $quiet->refresh()->status);
        $this->assertSame('failed', $failed->refresh()->status);
        Queue::assertNothingPushed();
    }

    private function message(int $schoolId, ?int $templateId, $when, string $status = 'approved', int $attempts = 0): OutboundMessage
    {
        return OutboundMessage::create(['public_id' => (string) Str::ulid(), 'school_id' => $schoolId, 'template_id' => $templateId, 'provider' => 'whatsapp_web', 'recipient' => '+923001234567', 'event' => 'custom', 'body' => 'Hello', 'status' => $status, 'scheduled_for' => $when, 'attempts' => $attempts, 'idempotency_key' => (string) Str::uuid()]);
    }
}
