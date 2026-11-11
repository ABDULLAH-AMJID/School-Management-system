<?php

namespace App\Http\Controllers\StaffAttendance;

use App\Http\Controllers\Controller;
use App\Models\StaffAttendance;
use App\Models\StaffProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class StaffAttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $schoolId = $request->user()->school_id;
        $date = ($request->date('date') ?? today())->toDateString();
        $staff = StaffProfile::where(['school_id' => $schoolId, 'status' => 'active'])->with('user')->get();
        $records = StaffAttendance::where(['school_id' => $schoolId, 'attendance_date' => $date])->get()->keyBy('staff_profile_id');

        return view('staff-attendance.index', compact('date', 'staff', 'records'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['date' => ['required', 'date', 'before_or_equal:today'], 'status' => ['required', 'array'], 'status.*' => [Rule::in(['present', 'absent', 'late', 'leave', 'half_day'])], 'check_in' => ['nullable', 'array'], 'check_in.*' => ['nullable', 'date_format:H:i'], 'check_out' => ['nullable', 'array'], 'check_out.*' => ['nullable', 'date_format:H:i']]);
        $staff = StaffProfile::where(['school_id' => $request->user()->school_id, 'status' => 'active'])->get();
        DB::transaction(function () use ($staff, $data, $request) {
            foreach ($staff as $profile) {
                $status = $data['status'][$profile->id] ?? 'present';
                $in = $data['check_in'][$profile->id] ?? null;
                $out = $data['check_out'][$profile->id] ?? null;
                $late = $in ? max(0, now()->setTimeFromTimeString($in)->diffInMinutes(now()->setTime(8, 0), false) * -1) : 0;
                StaffAttendance::updateOrCreate(['staff_profile_id' => $profile->id, 'attendance_date' => $data['date']], ['school_id' => $request->user()->school_id, 'status' => $status, 'check_in' => $in, 'check_out' => $out, 'late_minutes' => $status === 'late' ? $late : 0, 'source' => 'manual', 'recorded_by' => $request->user()->id]);
            }
        });

        return back()->with('success', 'Staff attendance saved.');
    }
}
