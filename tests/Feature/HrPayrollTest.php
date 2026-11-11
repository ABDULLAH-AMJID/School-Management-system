<?php

namespace Tests\Feature;

use App\Models\LeaveRequest;
use App\Models\PayrollRun;
use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrPayrollTest extends TestCase
{
    use RefreshDatabase;

    public function test_principal_can_approve_pending_leave(): void
    {
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();
        $leave = LeaveRequest::firstOrFail();

        $this->actingAs($principal)->post(route('hr.leaves.review', $leave), [
            'decision' => 'approved',
            'review_note' => 'Approved with cover arranged.',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame('approved', $leave->fresh()->status);
        $this->assertSame($principal->id, $leave->fresh()->reviewed_by);
    }

    public function test_payroll_generation_is_idempotent(): void
    {
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();
        $period = today()->format('Y-m');

        $this->actingAs($principal)->post(route('hr.payroll.generate'), ['period' => $period]);
        $this->actingAs($principal)->post(route('hr.payroll.generate'), ['period' => $period]);

        $this->assertDatabaseCount('payroll_runs', 1);
        $this->assertDatabaseCount('payslips', StaffProfile::where('status', 'active')->count());
    }

    public function test_approved_payroll_is_locked(): void
    {
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();
        $period = today()->format('Y-m');
        $this->actingAs($principal)->post(route('hr.payroll.generate'), ['period' => $period]);
        $run = PayrollRun::firstOrFail();

        $this->actingAs($principal)->post(route('hr.payroll.approve', $run))->assertRedirect();

        $this->assertSame('approved', $run->fresh()->status);
        $this->assertSame(0, $run->payslips()->where('status', '!=', 'approved')->count());
    }
}
