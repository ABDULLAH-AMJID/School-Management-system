<?php

namespace App\Http\Controllers\Assessment;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateResultNotifications;
use App\Models\AssessmentComponent;
use App\Models\Enrollment;
use App\Models\ExamSession;
use App\Models\StudentMark;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class AssessmentController extends Controller
{
    public function index(Request $request): View
    {
        $exams = ExamSession::where('school_id', $request->user()->school_id)->withCount('components')->latest('starts_on')->get();

        return view('assessments.index', compact('exams'));
    }

    public function entry(Request $request, AssessmentComponent $component): View
    {
        abort_unless($component->examSession()->where('school_id', $request->user()->school_id)->exists(), 403);
        $component->load(['subject', 'gradeLevel', 'marks']);
        $students = Enrollment::with('student')->whereHas('section', fn ($q) => $q->where('grade_level_id', $component->grade_level_id))->where('status', 'active')->get();

        return view('assessments.entry', compact('component', 'students'));
    }

    public function store(Request $request, AssessmentComponent $component): RedirectResponse
    {
        abort_unless($component->examSession()->where('school_id', $request->user()->school_id)->where('status', '!=', 'published')->exists(), 403);
        $data = $request->validate(['marks' => ['required', 'array'], 'marks.*' => ['nullable', 'numeric', 'min:0', 'max:'.$component->max_marks], 'status' => ['required', 'array'], 'status.*' => [Rule::in(['present', 'absent', 'exempt'])]]);
        DB::transaction(function () use ($data, $component, $request) {
            foreach ($data['status'] as $studentId => $status) {
                StudentMark::updateOrCreate(['assessment_component_id' => $component->id, 'student_id' => $studentId], ['marks' => $status === 'present' ? ($data['marks'][$studentId] ?? null) : null, 'status' => $status, 'entered_by' => $request->user()->id, 'entered_at' => now()]);
            }
        });

        return back()->with('success', 'Marks saved successfully.');
    }

    public function publish(Request $request, ExamSession $exam): RedirectResponse
    {
        abort_unless($exam->school_id === $request->user()->school_id, 403);
        $missing = AssessmentComponent::where('exam_session_id', $exam->id)->whereDoesntHave('marks')->exists();
        if ($missing) {
            return back()->withErrors(['publish' => 'Every assessment component must contain marks before publishing.']);
        }$exam->update(['status' => 'published', 'published_at' => now(), 'published_by' => $request->user()->id]);
        GenerateResultNotifications::dispatch($exam->id, $request->user()->id);

        return back()->with('success', 'Results published successfully.');
    }
}
