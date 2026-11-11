<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\FeeGenerationRun;
use App\Models\FeeInstallment;
use App\Models\FeeInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeeStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_fee_installment_generates_one_invoice_per_enrolled_student(): void
    {
        $this->seed();
        $u = User::where('email', 'principal@school.test')->firstOrFail();
        $year = AcademicYear::firstOrFail();
        $grade = Enrollment::with('section')->firstOrFail()->section->gradeLevel;
        $this->actingAs($u)->post(route('fee-setup.store'), ['academic_year_id' => $year->id, 'grade_level_id' => $grade->id, 'name' => 'Annual Tuition', 'installment_name' => 'November', 'issue_date' => today()->toDateString(), 'due_date' => today()->addDays(10)->toDateString(), 'amount' => 5000, 'fine_amount' => 250, 'fine_grace_days' => 3])->assertRedirect();
        $i = FeeInstallment::firstOrFail();
        $this->actingAs($u)->post(route('fee-setup.generate', $i))->assertRedirect();
        $this->assertSame(Enrollment::where('academic_year_id', $year->id)->whereHas('section', fn ($q) => $q->where('grade_level_id', $grade->id))->count(), FeeInvoice::where('number', 'like', 'INV-'.$i->id.'-%')->count());
        $this->assertSame(500000, FeeInvoice::latest('id')->firstOrFail()->total);
        $this->assertSame(1, FeeInvoice::latest('id')->firstOrFail()->items()->count());
        $this->assertSame(Enrollment::where('academic_year_id', $year->id)->whereHas('section', fn ($q) => $q->where('grade_level_id', $grade->id))->count(), FeeGenerationRun::firstOrFail()->invoice_count);
    }

    public function test_installment_cannot_generate_twice(): void
    {
        $this->test_fee_installment_generates_one_invoice_per_enrolled_student();
        $u = User::where('email', 'principal@school.test')->firstOrFail();
        $this->actingAs($u)->post(route('fee-setup.generate', FeeInstallment::firstOrFail()))->assertStatus(422);
    }
}
