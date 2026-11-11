<?php

namespace App\Http\Controllers\Fee;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\FeeGenerationRun;
use App\Models\FeeInstallment;
use App\Models\FeeInvoice;
use App\Models\FeeStructure;
use App\Models\GradeLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class FeeSetupController extends Controller
{
    public function index(Request $r): View
    {
        $sid = $r->user()->school_id;

        return view('fee-setup.index', ['years' => AcademicYear::where('school_id', $sid)->get(), 'grades' => GradeLevel::where('school_id', $sid)->orderBy('sequence')->get(), 'structures' => FeeStructure::where('school_id', $sid)->with(['gradeLevel', 'installments'])->get()]);
    }

    public function store(Request $r): RedirectResponse
    {
        $sid = $r->user()->school_id;
        $d = $r->validate(['academic_year_id' => ['required', Rule::exists('academic_years', 'id')->where('school_id', $sid)], 'grade_level_id' => ['required', Rule::exists('grade_levels', 'id')->where('school_id', $sid)], 'name' => ['required', 'string', 'max:100'], 'installment_name' => ['required', 'string', 'max:100'], 'issue_date' => ['required', 'date'], 'due_date' => ['required', 'date', 'after_or_equal:issue_date'], 'amount' => ['required', 'numeric', 'min:1'], 'fine_amount' => ['nullable', 'numeric', 'min:0'], 'fine_grace_days' => ['nullable', 'integer', 'min:0', 'max:90']]);
        DB::transaction(function () use ($d, $sid) {
            $s = FeeStructure::firstOrCreate(['school_id' => $sid, 'academic_year_id' => $d['academic_year_id'], 'grade_level_id' => $d['grade_level_id'], 'name' => $d['name']]);
            $s->installments()->create(['name' => $d['installment_name'], 'issue_date' => $d['issue_date'], 'due_date' => $d['due_date'], 'amount' => (int) round($d['amount'] * 100), 'fine_amount' => (int) round(($d['fine_amount'] ?? 0) * 100), 'fine_grace_days' => $d['fine_grace_days'] ?? 0]);
        });

        return back()->with('success', 'Fee installment configured.');
    }

    public function generate(Request $r, FeeInstallment $installment): RedirectResponse
    {
        $installment->load('feeStructure');
        $s = $installment->feeStructure;
        abort_unless($s->school_id === $r->user()->school_id, 403);
        DB::transaction(function () use ($installment, $s, $r) {
            abort_if(FeeGenerationRun::where('fee_installment_id', $installment->id)->exists(), 422, 'Installment already generated.');
            $enrollments = Enrollment::where('academic_year_id', $s->academic_year_id)->whereHas('section', fn ($q) => $q->where('grade_level_id', $s->grade_level_id))->get();
            foreach ($enrollments as $e) {
                $invoice = FeeInvoice::create(['school_id' => $s->school_id, 'student_id' => $e->student_id, 'number' => 'INV-'.$installment->id.'-'.$e->student_id, 'issue_date' => $installment->issue_date, 'due_date' => $installment->due_date, 'subtotal' => $installment->amount, 'discount' => 0, 'fine' => 0, 'total' => $installment->amount, 'paid' => 0, 'status' => 'issued']);
                $invoice->items()->create(['description' => $s->name.' · '.$installment->name, 'quantity' => 1, 'unit_amount' => $installment->amount, 'total' => $installment->amount]);
            }FeeGenerationRun::create(['fee_installment_id' => $installment->id, 'generated_by' => $r->user()->id, 'invoice_count' => $enrollments->count(), 'total_amount' => $enrollments->count() * $installment->amount, 'generated_at' => now()]);
        });

        return back()->with('success','Installment invoices generated.');
    }
}
