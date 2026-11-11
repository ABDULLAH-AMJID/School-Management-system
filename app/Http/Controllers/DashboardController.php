<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\FeeInvoice;
use App\Models\LeaveRequest;
use App\Models\OutboundMessage;
use App\Models\Student;
use App\Models\StudentAttendance;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $schoolId = $request->user()->school_id;
        $students = Student::where(['school_id' => $schoolId, 'status' => 'active'])->count();
        $todayAttendance = StudentAttendance::whereHas('student', fn ($q) => $q->where('school_id', $schoolId))->whereHas('attendanceSession', fn ($q) => $q->whereDate('attendance_date', today()))->get();
        $present = $todayAttendance->whereIn('status', ['present', 'late'])->count();
        $attendanceRate = $todayAttendance->count() ? round($present / $todayAttendance->count() * 100, 1) : 0;
        $billed = FeeInvoice::where('school_id', $schoolId)->sum('total');
        $collected = FeeInvoice::where('school_id', $schoolId)->sum('paid');
        $pending = Complaint::where(['school_id' => $schoolId, 'status' => 'open'])->count() + OutboundMessage::where(['school_id' => $schoolId, 'status' => 'pending_approval'])->count() + LeaveRequest::whereHas('staffProfile', fn ($q) => $q->where('school_id', $schoolId))->where('status', 'pending')->count();

        $metrics = collect([['permission' => 'students.view', 'label' => 'Active students', 'value' => number_format($students), 'delta' => 'Current enrollment', 'tone' => 'violet', 'icon' => 'users'], ['permission' => 'attendance.manage', 'label' => 'Present today', 'value' => $attendanceRate.'%', 'delta' => $todayAttendance->count() ? "$present of {$todayAttendance->count()}" : 'Register not submitted', 'tone' => 'green', 'icon' => 'check'], ['permission' => 'finance.manage', 'label' => 'Fees collected', 'value' => '₨ '.number_format($collected / 100), 'delta' => $billed ? round($collected / $billed * 100).'% of billed fees' : 'No invoices', 'tone' => 'blue', 'icon' => 'wallet'], ['permission' => 'messages.approve', 'label' => 'Pending approvals', 'value' => (string) $pending, 'delta' => 'Across school workflows', 'tone' => 'amber', 'icon' => 'clock']])->filter(fn ($metric) => $request->user()->hasPermission($metric['permission']))->map(fn ($metric) => collect($metric)->except('permission')->all())->values()->all();

        return view('dashboard', compact('metrics'));
    }
}
