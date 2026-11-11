<?php

namespace App\Jobs;

use App\Models\AssessmentComponent;
use App\Models\Enrollment;
use App\Models\ExamSession;
use App\Models\StudentMark;
use App\Models\User;
use App\Services\Messaging\TriggerStudentEventMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateResultNotifications implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $examId, public int $actorId) {}

    public function handle(TriggerStudentEventMessage $messenger): void
    {
        $exam = ExamSession::findOrFail($this->examId);
        $actor = User::findOrFail($this->actorId);
        $components = AssessmentComponent::where('exam_session_id', $exam->id)->get();
        $max = (float) $components->sum('max_marks');
        Enrollment::with('student')->where('academic_year_id', $exam->academic_year_id)->where('status', 'active')->chunkById(200, function ($rows) use ($exam, $actor, $components, $max, $messenger) {
            foreach ($rows as $enrollment) {
                $marks = StudentMark::whereIn('assessment_component_id', $components->pluck('id'))->where('student_id', $enrollment->student_id)->where('status', 'present')->sum('marks');
                $percentage = $max > 0 ? round($marks / $max * 100, 2) : 0;
                $messenger->handle('result_published', 'exam_session', $exam->id, $enrollment->student, $actor, ['exam_name' => $exam->name, 'percentage' => number_format($percentage, 2), 'result_status' => $percentage >= 33 ? 'Pass' : 'Needs attention']);
            }
        });
    }
}
