<?php

namespace Tests\Feature;

use App\Models\AssessmentComponent;
use App\Models\Enrollment;
use App\Models\ExamSession;
use App\Models\StudentMark;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_principal_can_enter_valid_marks(): void
    {
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();
        $component = AssessmentComponent::firstOrFail();
        $students = Enrollment::pluck('student_id');
        $statuses = $students->mapWithKeys(fn ($id) => [$id => 'present'])->all();
        $marks = $students->mapWithKeys(fn ($id) => [$id => 75])->all();

        $this->actingAs($principal)->put(route('assessments.marks.store', $component), [
            'status' => $statuses,
            'marks' => $marks,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseCount('student_marks', $students->count());
        $this->assertSame('75.00', StudentMark::first()->marks);
    }

    public function test_marks_above_component_maximum_are_rejected(): void
    {
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();
        $component = AssessmentComponent::firstOrFail();
        $studentId = Enrollment::firstOrFail()->student_id;

        $this->actingAs($principal)->put(route('assessments.marks.store', $component), [
            'status' => [$studentId => 'present'],
            'marks' => [$studentId => $component->max_marks + 1],
        ])->assertSessionHasErrors("marks.$studentId");
    }

    public function test_incomplete_exam_cannot_be_published(): void
    {
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();
        $exam = ExamSession::firstOrFail();

        $this->actingAs($principal)->post(route('exams.publish', $exam))
            ->assertSessionHasErrors('publish');

        $this->assertSame('marks_entry', $exam->fresh()->status);
    }
}
