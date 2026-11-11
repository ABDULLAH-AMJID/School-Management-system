<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\LeaveRequest;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\StaffProfile;
use App\Services\Messaging\TriggerDirectEventMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class HrController extends Controller
{
    public function index(Request $request): View
    {
        $schoolId = $request->user()->school_id;

        return view('hr.index', ['staff' => StaffProfile::where('school_id', $schoolId)->with('user')->get(), 'leaves' => LeaveRequest::whereHas('staffProfile', fn ($q) => $q->where('school_id', $schoolId))->with('staffProfile.user')->latest()->get(), 'runs' => PayrollRun::where('school_id', $schoolId)->withCount('payslips')->latest('period_start')->get()]);
    }

    public function storeLeave(Request $request): RedirectResponse
    {
        $profile = StaffProfile::where(['school_id' => $request->user()->school_id, 'user_id' => $request->user()->id])->firstOrFail();
        $data = $request->validate(['type' => ['required', Rule::in(['casual', 'sick', 'annual', 'unpaid', 'other'])], 'starts_on' => ['required', 'date', 'after_or_equal:today'], 'ends_on' => ['required', 'date', 'after_or_equal:starts_on'], 'reason' => ['required', 'string', 'max:1000']]);
        $days = now()->parse($data['starts_on'])->diffInDays(now()->parse($data['ends_on'])) + 1;
        LeaveRequest::create([...$data, 'staff_profile_id' => $profile->id, 'days' => $days, 'status' => 'pending']);

        return back()->with('success', 'Leave request submitted.');
    }

    public function reviewLeave(Request $request, LeaveRequest $leave, TriggerDirectEventMessage $messenger): RedirectResponse
    {
        abort_unless($leave->staffProfile()->where('school_id', $request->user()->school_id)->exists(), 403);
        $data = $request->validate(['decision' => ['required', 'in:approved,rejected'], 'review_note' => ['nullable', 'string', 'max:500']]);
        abort_unless($leave->status === 'pending', 409);
        $leave->update(['status' => $data['decision'], 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'review_note' => $data['review_note'] ?? null]);
        $leave->load('staffProfile.user');
        $staffUser = $leave->staffProfile->user;
        if ($staffUser?->phone) {
            $messenger->handle($request->user()->school_id, 'leave_reviewed', 'leave_request', $leave->id, $staffUser->phone, $request->user(), ['staff_name' => $staffUser->name, 'leave_status' => $data['decision'], 'starts_on' => $leave->starts_on->toDateString(), 'ends_on' => $leave->ends_on->toDateString(), 'review_note' => $data['review_note'] ?? '']);
        }

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

    public function payslip(Request $request, Payslip $payslip): View
    {
        $payslip->load(['staffProfile.user', 'payrollRun']);
        abort_unless($payslip->staffProfile->school_id === $request->user()->school_id, 403);

        return view('hr.payslip', compact('payslip'));
    }

    public function approvePayroll(Request $request, PayrollRun $run, TriggerDirectEventMessage $messenger): RedirectResponse
    {
        abort_unless($run->school_id === $request->user()->school_id, 403);
        abort_unless($run->status === 'draft' && $run->payslips()->exists(), 409);
        DB::transaction(function () use ($run, $request) {
            $total = $run->payslips()->sum('net_salary');
            $expense = Account::where(['school_id' => $run->school_id, 'code' => '5000'])->firstOrFail();
            $payable = Account::where(['school_id' => $run->school_id, 'code' => '2100'])->firstOrFail();
            $entry = JournalEntry::create(['school_id' => $run->school_id, 'number' => 'JV-PAY-'.$run->period, 'entry_date' => $run->period_end, 'reference_type' => PayrollRun::class, 'reference_id' => $run->id, 'description' => 'Payroll accrual '.$run->period, 'status' => 'posted', 'created_by' => $request->user()->id, 'posted_at' => now()]);
            $entry->lines()->createMany([['account_id' => $expense->id, 'debit' => $total, 'credit' => 0, 'memo' => 'Salary expense'], ['account_id' => $payable->id, 'debit' => 0, 'credit' => $total, 'memo' => 'Payroll payable']]);
            $run->update(['status' => 'approved', 'approved_by' => $request->user()->id, 'approved_at' => now(), 'journal_entry_id' => $entry->id]);
            $run->payslips()->update(['status' => 'approved']);
        });
        $run->load('payslips.staffProfile.user');
        foreach ($run->payslips as $payslip) {
            $staffUser = $payslip->staffProfile->user;
            if ($staffUser?->phone) {
                $messenger->handle($run->school_id, 'payroll_approved', 'payslip', $payslip->id, $staffUser->phone, $request->user(), ['staff_name' => $staffUser->name, 'pay_period' => $run->period, 'net_salary' => number_format($payslip->net_salary / 100, 2)]);
            }
        }

        return back()->with('success', 'Payroll approved and locked.');
    }
}
