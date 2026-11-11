<?php

namespace App\Http\Controllers\Reporting;

use App\Http\Controllers\Controller;
use App\Models\FeeInvoice;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $schoolId = $request->user()->school_id;
        $statusCounts = Student::where('school_id', $schoolId)->select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status');
        $finance = ['billed' => FeeInvoice::where('school_id', $schoolId)->sum('total'), 'collected' => Payment::where('school_id', $schoolId)->where('status', 'posted')->sum('amount'), 'outstanding' => FeeInvoice::where('school_id', $schoolId)->sum(DB::raw('total - paid'))];
        $attendance = StudentAttendance::whereHas('student', fn ($q) => $q->where('school_id', $schoolId))->select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status');

        return view('reports.index', compact('statusCounts', 'finance', 'attendance'));
    }

    public function studentsCsv(Request $request): StreamedResponse
    {
        $schoolId = $request->user()->school_id;

        return response()->streamDownload(function () use ($schoolId) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Admission No', 'Student Name', 'Gender', 'Status', 'Admission Date']);
            Student::where('school_id', $schoolId)->orderBy('admission_no')->chunk(500, function ($students) use ($out) {
                foreach ($students as $s) {
                    fputcsv($out, [$s->admission_no, $s->full_name, $s->gender, $s->status, $s->admitted_on?->toDateString()]);
                }
            });
            fclose($out);
        }, 'students-'.today()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function defaultersCsv(Request $request): StreamedResponse
    {
        $schoolId = $request->user()->school_id;

        return response()->streamDownload(function () use ($schoolId) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Invoice', 'Admission No', 'Student', 'Due Date', 'Total PKR', 'Paid PKR', 'Balance PKR']);
            FeeInvoice::with('student')->where('school_id', $schoolId)->whereColumn('paid', '<', 'total')->orderBy('due_date')->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $i) {
                    fputcsv($out, [$i->number, $i->student->admission_no, $i->student->full_name, $i->due_date->toDateString(), $i->total / 100, $i->paid / 100, $i->balance / 100]);
                }
            });
            fclose($out);
        }, 'fee-defaulters-'.today()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }
}
