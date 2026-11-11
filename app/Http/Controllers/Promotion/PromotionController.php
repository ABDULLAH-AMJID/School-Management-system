<?php

namespace App\Http\Controllers\Promotion;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\PromotionDecision;
use App\Models\PromotionRun;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class PromotionController extends Controller
{
    public function index(Request $request): View
    {
        $schoolId = $request->user()->school_id;

        return view('promotions.index', ['years' => AcademicYear::where('school_id', $schoolId)->orderByDesc('starts_on')->get(), 'sections' => Section::whereHas('gradeLevel', fn ($q) => $q->where('school_id', $schoolId))->with('gradeLevel')->get(), 'runs' => PromotionRun::where('school_id', $schoolId)->with(['fromSection.gradeLevel', 'toSection.gradeLevel'])->withCount('decisions')->latest()->get()]);
    }

    public function create(Request $request): RedirectResponse
    {
        $data = $request->validate(['from_year_id' => ['required', 'different:to_year_id', 'exists:academic_years,id'], 'to_year_id' => ['required', 'exists:academic_years,id'], 'from_section_id' => ['required', 'different:to_section_id', 'exists:sections,id'], 'to_section_id' => ['required', 'exists:sections,id']]);
        $schoolId = $request->user()->school_id;
        abort_unless(AcademicYear::where('school_id', $schoolId)->whereIn('id', [$data['from_year_id'], $data['to_year_id']])->count() === 2, 403);
        abort_unless(Section::whereHas('gradeLevel', fn ($query) => $query->where('school_id', $schoolId))->whereIn('id', [$data['from_section_id'], $data['to_section_id']])->count() === 2, 403);
        $run = PromotionRun::create(['school_id' => $schoolId, 'from_academic_year_id' => $data['from_year_id'], 'to_academic_year_id' => $data['to_year_id'], 'from_section_id' => $data['from_section_id'], 'to_section_id' => $data['to_section_id'], 'created_by' => $request->user()->id, 'status' => 'draft']);
        foreach (Enrollment::where(['academic_year_id' => $data['from_year_id'], 'section_id' => $data['from_section_id'], 'status' => 'active'])->get() as $enrollment) {
            PromotionDecision::create(['promotion_run_id' => $run->id, 'student_id' => $enrollment->student_id, 'decision' => 'promote', 'destination_section_id' => $data['to_section_id'], 'new_roll_no' => $enrollment->roll_no]);
        }

        return back()->with('success', 'Promotion run prepared for review.');
    }

    public function complete(Request $request, PromotionRun $run): RedirectResponse
    {
        abort_unless($run->school_id === $request->user()->school_id && $run->status === 'draft', 403);
        $data = $request->validate(['decision' => ['required', 'array'], 'decision.*' => [Rule::in(['promote', 'repeat', 'graduate'])]]);
        DB::transaction(function () use ($run, $data, $request) {
            foreach ($run->decisions()->get() as $decision) {
                $choice = $data['decision'][$decision->id] ?? $decision->decision;
                $decision->update(['decision' => $choice]);
                $old = Enrollment::where(['student_id' => $decision->student_id, 'academic_year_id' => $run->from_academic_year_id])->firstOrFail();
                $old->update(['status' => $choice === 'graduate' ? 'graduated' : 'completed']);
                if ($choice !== 'graduate') {
                    Enrollment::updateOrCreate(['student_id' => $decision->student_id, 'academic_year_id' => $run->to_academic_year_id], ['section_id' => $choice === 'repeat' ? $run->from_section_id : $run->to_section_id, 'roll_no' => $decision->new_roll_no, 'status' => 'active']);
                } else {
                    $old->student()->update(['status' => 'graduated']);
                }
            }$run->update(['status' => 'completed', 'completed_by' => $request->user()->id, 'completed_at' => now()]);
        });

        return back()->with('success', 'Promotion run completed and new enrollments created.');
    }
}
