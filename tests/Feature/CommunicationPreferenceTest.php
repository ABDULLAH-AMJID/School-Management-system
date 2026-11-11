<?php

namespace Tests\Feature;

use App\Models\MessageTemplate;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunicationPreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_opt_out_blocks_message_creation(): void
    {
        $this->seed();
        $u = User::where('email', 'principal@school.test')->firstOrFail();
        $student = Student::firstOrFail();
        $phone = '+923009998888';
        $this->actingAs($u)->post(route('communication.preferences.store'), ['recipient' => $phone, 'event' => '*', 'opted_out' => 1, 'reason' => 'Guardian request'])->assertRedirect();
        $template = MessageTemplate::firstOrFail();
        $this->actingAs($u)->post(route('communication.store'), ['template_id' => $template->id, 'student_id' => $student->id, 'recipient' => $phone])->assertSessionHasErrors('recipient');
        $this->assertDatabaseCount('outbound_messages', 0);
    }

    public function test_consent_restoration_allows_event_message(): void
    {
        $this->seed();
        $u = User::where('email', 'principal@school.test')->firstOrFail();
        $student = Student::firstOrFail();
        $template = MessageTemplate::firstOrFail();
        $template->update(['mode' => 'approval']);
        $phone = '+923009998888';
        foreach ([1, 0] as $out) {
            $this->actingAs($u)->post(route('communication.preferences.store'), ['recipient' => $phone, 'event' => $template->event, 'opted_out' => $out, 'reason' => 'Recorded by office']);
        }$this->actingAs($u)->post(route('communication.store'), ['template_id' => $template->id, 'student_id' => $student->id, 'recipient' => $phone])->assertRedirect();
        $this->assertDatabaseHas('outbound_messages', ['recipient' => $phone, 'status' => 'pending_approval']);
        $this->assertDatabaseHas('communication_preferences', ['recipient' => $phone, 'opted_out' => false]);
    }
}
