<?php

namespace App\Http\Controllers\Timetable;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateTimetableNotifications;
use App\Models\AcademicYear;
use App\Models\Room;
use App\Models\Section;
use App\Models\TeachingAssignment;
use App\Models\TimetableEntry;
use App\Models\TimetablePeriod;
use App\Models\TimetablePublication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class TimetableController extends Controller
{
    public function index(Request $request): View
    {
        $schoolId = $request->user()->school_id;
        $year = AcademicYear::where(['school_id' => $schoolId, 'is_current' => true])->firstOrFail();
        $sections = Section::whereHas('gradeLevel', fn ($q) => $q->where('school_id', $schoolId))->with('gradeLevel')->get();
        $sectionId = $request->integer('section_id') ?: $sections->first()?->id;
        $periods = TimetablePeriod::where('school_id', $schoolId)->orderBy('sequence')->get();
        $entries = TimetableEntry::where(['academic_year_id' => $year->id, 'section_id' => $sectionId])->with(['assignment.subject', 'assignment.teacher', 'room'])->get()->keyBy(fn ($e) => $e->weekday.'-'.$e->period_id);

        return view('timetable.index', compact('year', 'sections', 'sectionId', 'periods', 'entries'));
    }

    public function create(Request $request): View
    {
        $schoolId = $request->user()->school_id;
        $year = AcademicYear::where(['school_id' => $schoolId, 'is_current' => true])->firstOrFail();

        return view('timetable.create', ['year' => $year, 'sections' => Section::whereHas('gradeLevel', fn ($q) => $q->where('school_id', $schoolId))->with('gradeLevel')->get(), 'periods' => TimetablePeriod::where('school_id', $schoolId)->orderBy('sequence')->get(), 'assignments' => TeachingAssignment::where('academic_year_id', $year->id)->with(['subject', 'teacher', 'section.gradeLevel'])->get(), 'rooms' => Room::where('school_id', $schoolId)->get()]);
    }

    public function publish(Request $request, Section $section): RedirectResponse
    {
        abort_unless($section->gradeLevel()->where('school_id', $request->user()->school_id)->exists(), 403);
        $year = AcademicYear::where(['school_id' => $request->user()->school_id, 'is_current' => true])->firstOrFail();
        $snapshot = TimetableEntry::where(['academic_year_id' => $year->id, 'section_id' => $section->id])->orderBy('weekday')->orderBy('period_id')->get(['weekday', 'period_id', 'teaching_assignment_id', 'room_id'])->toJson();
        abort_if($snapshot === '[]', 422, 'Cannot publish an empty timetable.');
        $hash = hash('sha256', $snapshot);
        $latest = TimetablePublication::where(['academic_year_id' => $year->id, 'section_id' => $section->id])->latest('version')->first();
        if ($latest?->snapshot_hash === $hash) {
            return back()->with('success', 'Timetable is already up to date.');
        }
        $publication = DB::transaction(fn () => TimetablePublication::create(['school_id' => $request->user()->school_id, 'academic_year_id' => $year->id, 'section_id' => $section->id, 'version' => ($latest?->version ?? 0) + 1, 'snapshot_hash' => $hash, 'published_by' => $request->user()->id, 'published_at' => now()]));
        GenerateTimetableNotifications::dispatch($publication->id);

        return back()->with('success', 'Timetable version '.$publication->version.' published.');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['weekday' => ['required', 'integer', 'between:1,6'], 'period_id' => ['required', 'exists:timetable_periods,id'], 'section_id' => ['required', 'exists:sections,id'], 'teaching_assignment_id' => ['required', 'exists:teaching_assignments,id'], 'room_id' => ['nullable', 'exists:rooms,id']]);
        $schoolId = $request->user()->school_id;
        $year = AcademicYear::where(['school_id' => $schoolId, 'is_current' => true])->firstOrFail();
        $assignment = TeachingAssignment::with('section.gradeLevel')->findOrFail($data['teaching_assignment_id']);
        abort_unless($assignment->section->gradeLevel->school_id === $schoolId && $assignment->section_id === (int) $data['section_id'], 403);
        $teacherBusy = TimetableEntry::where(['academic_year_id' => $year->id, 'weekday' => $data['weekday'], 'period_id' => $data['period_id']])->whereHas('assignment', fn ($q) => $q->where('teacher_id', $assignment->teacher_id))->exists();
        if ($teacherBusy) {
            throw ValidationException::withMessages(['teaching_assignment_id' => 'This teacher is already assigned in the selected period.']);
        }if ($data['room_id'] ?? null) {
            $roomBusy = TimetableEntry::where(['academic_year_id' => $year->id, 'weekday' => $data['weekday'], 'period_id' => $data['period_id'], 'room_id' => $data['room_id']])->exists();
            if ($roomBusy) {
                throw ValidationException::withMessages(['room_id' => 'This room is already occupied in the selected period.']);
            }
        }TimetableEntry::create($data + ['academic_year_id' => $year->id]);

        return redirect()->route('timetable.index', ['section_id' => $data['section_id']])->with('success', 'Timetable entry added.');
    }
}
