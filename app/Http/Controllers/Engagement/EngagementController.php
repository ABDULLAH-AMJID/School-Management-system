<?php

namespace App\Http\Controllers\Engagement;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Notice;
use App\Models\SchoolEvent;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class EngagementController extends Controller
{
    public function index(Request $request): View
    {
        $schoolId = $request->user()->school_id;

        return view('engagement.index', ['notices' => Notice::where('school_id', $schoolId)->latest()->get(), 'events' => SchoolEvent::where('school_id', $schoolId)->where('ends_at', '>=', now())->orderBy('starts_at')->get(), 'complaints' => Complaint::where('school_id', $schoolId)->with(['student', 'assignee'])->latest()->get(), 'students' => Student::where('school_id', $schoolId)->orderBy('first_name')->get(), 'staff' => User::where('school_id', $schoolId)->where('status', 'active')->orderBy('name')->get()]);
    }

    public function storeNotice(Request $request): RedirectResponse
    {
        $data = $request->validate(['title' => ['required', 'string', 'max:160'], 'body' => ['required', 'string', 'max:5000'], 'audience' => ['required', Rule::in(['all', 'all_staff', 'teachers', 'parents', 'students'])], 'starts_on' => ['required', 'date'], 'ends_on' => ['required', 'date', 'after_or_equal:starts_on'], 'pinned' => ['nullable', 'boolean']]);
        Notice::create($data + ['school_id' => $request->user()->school_id, 'created_by' => $request->user()->id, 'status' => 'published', 'pinned' => $request->boolean('pinned')]);

        return back()->with('success', 'Notice published.');
    }

    public function storeComplaint(Request $request): RedirectResponse
    {
        $data = $request->validate(['source' => ['required', Rule::in(['parent', 'student', 'staff', 'anonymous'])], 'complainant_name' => ['required', 'string', 'max:120'], 'complainant_phone' => ['nullable', 'string', 'max:30'], 'student_id' => ['nullable', 'exists:students,id'], 'category' => ['required', 'string', 'max:80'], 'priority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])], 'subject' => ['required', 'string', 'max:180'], 'description' => ['required', 'string', 'max:5000'], 'assigned_to' => ['nullable', 'exists:users,id']]);
        if ($data['student_id'] ?? null) {
            abort_unless(Student::where(['id' => $data['student_id'], 'school_id' => $request->user()->school_id])->exists(), 403);
        }Complaint::create($data + ['public_id' => (string) Str::ulid(), 'school_id' => $request->user()->school_id, 'reference_no' => 'CMP-'.now()->format('ymd').'-'.str_pad((string) (Complaint::count() + 1), 4, '0', STR_PAD_LEFT), 'status' => 'open']);

        return back()->with('success', 'Complaint registered.');
    }

    public function resolve(Request $request, Complaint $complaint): RedirectResponse
    {
        abort_unless($complaint->school_id === $request->user()->school_id, 403);
        abort_unless(! in_array($complaint->status, ['resolved', 'closed'], true), 409);
        $data = $request->validate(['resolution' => ['required', 'string', 'max:5000']]);
        $complaint->update(['status' => 'resolved', 'resolution' => $data['resolution'], 'resolved_at' => now(), 'resolved_by' => $request->user()->id]);

        return back()->with('success','Complaint resolved.');
    }
}
