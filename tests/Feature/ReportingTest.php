<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportingTest extends TestCase
{
    use RefreshDatabase;

    public function test_principal_can_view_live_reporting_dashboard(): void
    {
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();

        $this->actingAs($principal)->get(route('reports.index'))
            ->assertOk()
            ->assertSee('School reports')
            ->assertSee('Outstanding fees');
    }

    public function test_student_register_csv_is_school_scoped(): void
    {
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();
        $student = Student::firstOrFail();

        $response = $this->actingAs($principal)->get(route('reports.students.csv'));

        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString($student->admission_no, $response->streamedContent());
        $this->assertStringContainsString('Admission No', $response->streamedContent());
    }

    public function test_fee_defaulter_export_contains_outstanding_invoice(): void
    {
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();

        $response = $this->actingAs($principal)->get(route('reports.defaulters.csv'));

        $response->assertOk();
        $this->assertStringContainsString('INV-2026-0001', $response->streamedContent());
        $this->assertStringContainsString('Balance PKR', $response->streamedContent());
    }
}
