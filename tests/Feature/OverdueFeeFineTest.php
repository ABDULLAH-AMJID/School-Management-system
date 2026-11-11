<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\FeeInstallment;
use App\Models\FeeInvoice;
use App\Models\FeeStructure;
use App\Models\GradeLevel;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OverdueFeeFineTest extends TestCase
{
    use RefreshDatabase;

    public function test_overdue_fine_is_applied_once_after_grace_period(): void
    {
        $this->seed();
        $student = Student::firstOrFail();
        $structure = FeeStructure::create(['school_id' => $student->school_id, 'academic_year_id' => AcademicYear::first()->id, 'grade_level_id' => GradeLevel::first()->id, 'name' => 'Tuition']);
        $i = FeeInstallment::create(['fee_structure_id' => $structure->id, 'name' => 'Old', 'issue_date' => today()->subDays(20), 'due_date' => today()->subDays(10), 'amount' => 100000, 'fine_amount' => 5000, 'fine_grace_days' => 3]);
        $invoice = FeeInvoice::create(['school_id' => $student->school_id, 'student_id' => $student->id, 'fee_installment_id' => $i->id, 'number' => 'INV-FINE', 'issue_date' => today()->subDays(20), 'due_date' => today()->subDays(10), 'subtotal' => 100000, 'discount' => 0, 'fine' => 0, 'total' => 100000, 'paid' => 0, 'status' => 'issued']);
        $this->artisan('school:fees:apply-fines')->assertSuccessful()->expectsOutput('Applied 1 overdue fine(s).');
        $this->assertSame(105000, $invoice->refresh()->total);
        $this->assertSame('overdue', $invoice->status);
        $this->artisan('school:fees:apply-fines')->assertSuccessful()->expectsOutput('Applied 0 overdue fine(s).');
        $this->assertSame(105000, $invoice->refresh()->total);
        $this->assertDatabaseCount('fee_fine_applications', 1);
    }

    public function test_fine_is_not_applied_inside_grace_period(): void
    {
        $this->seed();
        $student = Student::firstOrFail();
        $s = FeeStructure::create(['school_id' => $student->school_id, 'academic_year_id' => AcademicYear::first()->id, 'grade_level_id' => GradeLevel::first()->id, 'name' => 'Transport']);
        $i = FeeInstallment::create(['fee_structure_id' => $s->id, 'name' => 'Current', 'issue_date' => today()->subDays(5), 'due_date' => today()->subDay(), 'amount' => 100000, 'fine_amount' => 5000, 'fine_grace_days' => 3]);
        FeeInvoice::create(['school_id' => $student->school_id, 'student_id' => $student->id, 'fee_installment_id' => $i->id, 'number' => 'INV-GRACE', 'issue_date' => today()->subDays(5), 'due_date' => today()->subDay(), 'subtotal' => 100000, 'discount' => 0, 'fine' => 0, 'total' => 100000, 'paid' => 0, 'status' => 'issued']);
        $this->artisan('school:fees:apply-fines')->expectsOutput('Applied 0 overdue fine(s).');
        $this->assertDatabaseCount('fee_fine_applications', 0);
    }
}
