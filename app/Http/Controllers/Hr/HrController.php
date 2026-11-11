<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\PayrollRun;
use App\Models\StaffProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class HrController extends Controller
{
    public function index(Request $request): View
    {
        $schoolId = $request->user()->school_id;

        return view('hr.index', ['staff' => StaffProfile::where('school_id', $schoolId)->with('user')->get(), 'leaves' => LeaveRequest::whereHas('staffProfile', fn ($q) => $q->where('school_id', $schoolId))->with('staffProfile.user')->latest()->get(), 'runs' => PayrollRun::where('school_id', $schoolId)->withCount('payslips')->latest('period_start')->get()]);
    }

    public function reviewLeave(Request $request, LeaveRequest $leave): RedirectResponse
    {
        abort_unless($leave->staffProfile()->where('school_id', $request->user()->school_id)->exists(), 403);
        $data = $request->validate(['decision' => ['required', 'in:approved,rejected'], 'review_note' => ['nullable', 'string', 'max:500']]);
        abort_unless($leave->status === 'pending', 409);
        $leave->update(['status' => $data['decision'], 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'review_note' => $data['review_note'] ?? null]);

        return back()->with('success', 'Leave request '.$data['decision'].'.');
    }

    public function generatePayroll(Request $request): RedirectResponse
    {
        $data = $request->validate(['period' => ['required', 'date_format:Y-m']]);
        $start = now()->createFromFormat('Y-m', $data['period'])->startOfMonth();
        $run = DB::transaction(function () use ($request, $data, $start) {
            $run = PayrollRun::firstOrCreate(['school_id' => $request->user()->school_id, 'period' => $data['period']], ['period_start' => $start, 'period_end' => $start->copy()->endOfMonth(), 'status' => 'draft']);
            foreach (StaffProfile::where(['school_id' => $request->user()->school_id, 'status' => 'active'])->get() as $staff) {
                $run->payslips()->firstOrCreate(['staff_profile_id' => $staff->id], ['basic_salary' => $staff->basic_salary, 'allowances' => 0, 'deductions' => 0, 'net_salary' => $staff->basic_salary, 'status' => 'draft']);
            }

return $run;
        });

        return back()->with('success', 'Payroll generated for '.$run->period.'.');
    }

    public function approvePayroll(Request $request, PayrollRun $run): RedirectResponse
    {
        abort_unless($run->school_id === $request->user()->school_id, 403);
        abort_unless($run->status === 'draft' && $run->payslips()->exists(), 409);
        DB::transaction(function () use ($run, $request) {
            $run->update(['status' => 'approved', 'approved_by' => $request->user()->id, 'approved_at' => now()]);
            $run->payslips()->update(['status' => 'approved']);
        });

        return back()->with('success','Payroll approved and locked.');
    }
}
