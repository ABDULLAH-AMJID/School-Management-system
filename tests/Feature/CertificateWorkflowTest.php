<?php

namespace Tests\Feature;

use App\Models\CertificateTemplate;
use App\Models\IssuedCertificate;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificateWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_certificate_is_rendered_with_frozen_student_details(): void
    {
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();
        $student = Student::firstOrFail();
        $template = CertificateTemplate::firstOrFail();

        $this->actingAs($principal)->post(route('certificates.issue'), [
            'template_id' => $template->id, 'student_id' => $student->id,
            'issued_on' => today()->toDateString(),
        ])->assertRedirect();

        $certificate = IssuedCertificate::firstOrFail();
        $this->assertStringContainsString($student->full_name, $certificate->rendered_body);
        $this->assertStringContainsString($student->admission_no, $certificate->rendered_body);
        $this->assertNotEmpty($certificate->verification_code);
    }

    public function test_public_verification_reports_valid_and_revoked_state(): void
    {
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();
        $student = Student::firstOrFail();
        $template = CertificateTemplate::firstOrFail();
        $this->actingAs($principal)->post(route('certificates.issue'), ['template_id' => $template->id, 'student_id' => $student->id, 'issued_on' => today()->toDateString()]);
        $certificate = IssuedCertificate::firstOrFail();

        $this->get(route('certificates.verify', $certificate->verification_code))->assertOk()->assertSee('Valid certificate');
        $this->actingAs($principal)->post(route('certificates.revoke', $certificate), ['reason' => 'Issued with incorrect details'])->assertRedirect();
        $this->get(route('certificates.verify', $certificate->verification_code))->assertOk()->assertSee('Certificate revoked')->assertSee('incorrect details');
    }
}
