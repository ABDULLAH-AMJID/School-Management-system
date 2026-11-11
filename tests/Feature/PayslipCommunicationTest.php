<?php

namespace Tests\Feature;

use App\Models\CommunicationEventLog;
use App\Models\OutboundMessage;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayslipCommunicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_payroll_creates_staff_salary_message_and_printable_slip(): void
    {
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();
        $teacher = User::where('email', 'teacher@school.test')->firstOrFail();
        $teacher->update(['phone' => '+923001117777']);
        $this->actingAs($principal)->post(route('hr.payroll.generate'), ['period' => '2026-11'])->assertRedirect();
        $run = PayrollRun::where('period', '2026-11')->firstOrFail();
        $this->actingAs($principal)->post(route('hr.payroll.approve', $run))->assertRedirect();
        $payslip = Payslip::whereHas('staffProfile', fn ($q) => $q->where('user_id', $teacher->id))->firstOrFail();
        $this->actingAs($principal)->get(route('hr.payslips.show', $payslip))->assertOk()->assertSee('Salary slip')->assertSee('85,000.00');
        $message = OutboundMessage::where('event', 'payroll_approved')->firstOrFail();
        $this->assertSame('+923001117777', $message->recipient);
        $this->assertStringContainsString('85,000.00', $message->body);
        $this->assertSame('pending_approval', $message->status);
    }

    public function test_salary_message_is_linked_once_to_payslip(): void
    {
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();
        $teacher = User::where('email', 'teacher@school.test')->firstOrFail();
        $teacher->update(['phone' => '+923001117777']);
        $this->actingAs($principal)->post(route('hr.payroll.generate'), ['period' => '2026-10']);
        $run = PayrollRun::where('period', '2026-10')->firstOrFail();
        $this->actingAs($principal)->post(route('hr.payroll.approve', $run));
        $this->assertSame(1, CommunicationEventLog::where('event','payroll_approved')->count());
    }
}
