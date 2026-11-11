<?php

namespace App\Http\Controllers\Results;

use App\Http\Controllers\Controller;
use App\Models\AssessmentComponent;
use App\Models\Enrollment;
use App\Models\ExamSession;
use App\Models\GradeScale;
use App\Models\Student;
use App\Models\StudentMark;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ResultController extends Controller
{
    public function index(Request $request): View
    {
        $schoolId = $request->user()->school_id;

        return view('results.index', ['exams' => ExamSession::where('school_id', $schoolId)->withCount('components')->latest()->get(), 'students' => Student::where('school_id', $schoolId)->where('status', 'active')->orderBy('first_name')->get()]);
    }

    public function show(Request $request, ExamSession $exam, Student $student): View
    {
        abort_unless($exam->school_id === $request->user()->school_id && $student->school_id === $request->user()->school_id, 403);
        $rows = $this->calculate($exam, $student);
        $overall = $rows->sum('weighted') / max(1, $rows->sum('weight'));
        $grade = $this->grade($exam->school_id, $overall);
        $enrollment = Enrollment::with('section.gradeLevel')->where(['student_id' => $student->id, 'academic_year_id' => $exam->academic_year_id])->first();

        return view('results.show', compact('exam', 'student', 'rows', 'overall', 'grade', 'enrollment'));
    }

    public function tabulationCsv(Request $request, ExamSession $exam): StreamedResponse
    {
        abort_unless($exam->school_id === $request->user()->school_id, 403);
        $students = Student::where('school_id', $exam->school_id)->whereHas('enrollments', fn ($q) => $q->where('academic_year_id', $exam->academic_year_id))->get();

        return response()->streamDownload(function () use ($exam, $students) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Admission No', 'Student', 'Percentage', 'Grade', 'Result']);
            foreach ($students as $student) {
                $rows = $this->calculate($exam, $student);
                $percentage = $rows->sum('weighted') / max(1, $rows->sum('weight'));
                $grade = $this->grade($exam->school_id, $percentage);
                $failed = $rows->contains(fn ($row) => $row['status'] === 'absent' || ($row['marks'] !== null && $row['marks'] < $row['pass_marks']));
                fputcsv($out, [$student->admission_no, $student->full_name, number_format($percentage, 2), $grade?->grade ?? '—', $failed ? 'Fail' : 'Pass']);
            }fclose($out);
        }, 'tabulation-'.$exam->id.'.csv', ['Content-Type' => 'text/csv']);
    }

    private function calculate(ExamSession $exam, Student $student): Collection
    {
        return AssessmentComponent::where('exam_session_id', $exam->id)->with('subject')->get()->map(function ($component) use ($student) {
            $mark = StudentMark::where(['assessment_component_id' => $component->id, 'student_id' => $student->id])->first();
            $percentage = $mark && $mark->status === 'present' ? (float) $mark->marks / $component->max_marks * 100 : 0;

            return ['subject' => $component->subject->name, 'component' => $component->name, 'marks' => $mark?->marks, 'status' => $mark?->status ?? 'not_entered', 'max_marks' => $component->max_marks, 'pass_marks' => $component->pass_marks, 'weight' => (float) $component->weight_percent, 'weighted' => $percentage * (float) $component->weight_percent];
        });
    }

    private function grade(int $schoolId, float $percentage): ?GradeScale
    {
        return GradeScale::where('school_id', $schoolId)->where('minimum_percent','<=',$percentage)->where('maximum_percent','>=',$percentage)->orderByDesc('minimum_percent')->first();
    }
}
