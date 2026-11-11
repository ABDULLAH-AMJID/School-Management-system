<?php

namespace Tests\Feature;

use App\Jobs\GenerateResultNotifications;
use App\Models\AssessmentComponent;
use App\Models\Enrollment;
use App\Models\ExamSession;
use App\Models\Guardian;
use App\Models\MessageTemplate;
use App\Models\OutboundMessage;
use App\Models\StudentMark;
use App\Models\User;
use App\Services\Messaging\TriggerStudentEventMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class ResultCommunicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_publishing_exam_queues_result_notification_generation(): void
    {
        $this->seed();
        Queue::fake();
        $u = User::where('email', 'principal@school.test')->firstOrFail();
        $exam = ExamSession::firstOrFail();
        foreach (AssessmentComponent::where('exam_session_id', $exam->id)->get() as $c) {
            StudentMark::create(['assessment_component_id' => $c->id, 'student_id' => Enrollment::first()->student_id, 'marks' => 70, 'status' => 'present']);
        }$this->actingAs($u)->post(route('exams.publish', $exam))->assertRedirect();
        Queue::assertPushed(GenerateResultNotifications::class, fn ($job) => $job->examId === $exam->id);
    }

    public function test_result_job_renders_percentage_for_guardian_once(): void
    {
        $this->seed();
        $u = User::where('email', 'principal@school.test')->firstOrFail();
        $exam = ExamSession::firstOrFail();
        $student = Enrollment::where('academic_year_id', $exam->academic_year_id)->firstOrFail()->student;
        $g = Guardian::create(['school_id' => $student->school_id, 'public_id' => (string) Str::ulid(), 'name' => 'Parent', 'relationship' => 'Mother', 'phone' => '+923001234000', 'whatsapp_opt_in' => true]);
        $student->guardians()->attach($g, ['primary_contact' => true]);
        $template = MessageTemplate::where('event', 'result_published')->first() ?? MessageTemplate::create(['school_id' => $student->school_id, 'event' => 'result_published', 'name' => 'Result', 'body' => '{{student_name}} {{percentage}}% {{exam_name}} {{result_status}}', 'mode' => 'approval', 'active' => true]);
        foreach (AssessmentComponent::where('exam_session_id', $exam->id)->get() as $c) {
            StudentMark::updateOrCreate(['assessment_component_id' => $c->id, 'student_id' => $student->id], ['marks' => $c->max_marks * 0.8, 'status' => 'present']);
        }$job = new GenerateResultNotifications($exam->id, $u->id);
        $job->handle(app(TriggerStudentEventMessage::class));
        $job->handle(app(TriggerStudentEventMessage::class));
        $message = OutboundMessage::where('event', 'result_published')->firstOrFail();
        $this->assertStringContainsString('80.00%', $message->body);
        $this->assertDatabaseCount('outbound_messages', 1);
    }
}
