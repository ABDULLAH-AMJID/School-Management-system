<?php

namespace Tests\Feature;

use App\Models\AssessmentComponent;
use App\Models\Enrollment;
use App\Models\ExamSession;
use App\Models\StudentMark;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResultCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_result_card_calculates_percentage_and_grade(): void
    {
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();
        $student = Enrollment::firstOrFail()->student;
        $exam = ExamSession::firstOrFail();
        foreach (AssessmentComponent::where('exam_session_id', $exam->id)->get() as $component) {
            StudentMark::create(['assessment_component_id' => $component->id, 'student_id' => $student->id, 'marks' => 75, 'status' => 'present', 'entered_by' => $principal->id, 'entered_at' => now()]);
        }

        $this->actingAs($principal)->get(route('results.show', [$exam, $student]))
            ->assertOk()->assertSee('75.00%')->assertSee('Grade')->assertSee('Excellent');
    }

    public function test_tabulation_export_contains_calculated_student_result(): void
    {
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();
        $student = Enrollment::firstOrFail()->student;
        $exam = ExamSession::firstOrFail();
        foreach (AssessmentComponent::where('exam_session_id', $exam->id)->get() as $component) {
            StudentMark::create(['assessment_component_id' => $component->id, 'student_id' => $student->id, 'marks' => 80, 'status' => 'present']);
        }

        $response = $this->actingAs($principal)->get(route('results.tabulation.csv', $exam));

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString($student->admission_no, $content);
        $this->assertStringContainsString('A+', $content);
        $this->assertStringContainsString('Pass', $content);
    }
}
