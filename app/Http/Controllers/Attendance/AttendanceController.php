<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\AttendanceSession;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Services\Messaging\TriggerStudentEventMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $schoolId = $request->user()->school_id;
        $sections = Section::whereHas('gradeLevel', fn ($q) => $q->where('school_id', $schoolId))->with('gradeLevel')->get();
        $sectionId = $request->integer('section_id') ?: $sections->first()?->id;
        $date = $request->date('date', today())->toDateString();
        $students = collect();
        $session = null;
        if ($sectionId) {
            $students = Enrollment::with('student')->where('section_id', $sectionId)->where('status', 'active')->orderBy('roll_no')->get();
            $session = AttendanceSession::with('records')->where(['section_id' => $sectionId, 'attendance_date' => $date, 'period' => 'daily'])->first();
        }

        return view('attendance.index', compact('sections', 'sectionId', 'date', 'students', 'session'));
    }

    public function store(Request $request, TriggerStudentEventMessage $messenger): RedirectResponse
    {
        $data = $request->validate(['section_id' => ['required', 'integer', Rule::exists('sections', 'id')], 'date' => ['required', 'date', 'before_or_equal:today'], 'status' => ['required', 'array'], 'status.*' => [Rule::in(['present', 'absent', 'late', 'leave'])]]);
        $section = Section::with('gradeLevel')->findOrFail($data['section_id']);
        abort_unless($section->gradeLevel->school_id === $request->user()->school_id, 403);
        $studentIds = Enrollment::where('section_id', $section->id)->pluck('student_id');
        abort_unless(collect(array_keys($data['status']))->diff($studentIds)->isEmpty(), 422);
        $session = DB::transaction(function () use ($data, $request, $studentIds) {
            $session = AttendanceSession::updateOrCreate(['section_id' => $data['section_id'], 'attendance_date' => $data['date'], 'period' => 'daily'], ['school_id' => $request->user()->school_id, 'taken_by' => $request->user()->id, 'submitted_at' => now()]);
            foreach ($studentIds as $id) {
                StudentAttendance::updateOrCreate(['attendance_session_id' => $session->id, 'student_id' => $id], ['status' => $data['status'][$id] ?? 'present', 'source' => 'manual']);
            }

            return $session;
        });
        foreach (collect($data['status'])->filter(fn ($status) => $status === 'absent')->keys() as $studentId) {
            $messenger->handle('student_absent', 'attendance_session', $session->id, Student::findOrFail($studentId), $request->user(), ['attendance_date' => $data['date']]);
        }

        return back()->with('success', 'Attendance was submitted successfully.');
    }
}
