<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_principal_static_portal_pages_render_without_server_errors(): void
    {
        $this->withoutExceptionHandling();
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();
        $routes = ['dashboard', 'students.index', 'students.create', 'attendance.index', 'academics.index', 'assessments.index', 'results.index', 'promotions.index', 'timetable.index', 'timetable.create', 'finance.index', 'fee-setup.index', 'reconciliation.index', 'hr.index', 'staff-attendance.index', 'library.index', 'transport.index', 'inventory.index', 'communication.index', 'engagement.index', 'certificates.index', 'reports.index', 'access.index', 'security.index', 'settings.school.edit', 'approvals.index', 'profile.edit'];
        foreach ($routes as $name) {
            $response = $this->actingAs($principal)->get(route($name));
            $this->assertLessThan(500, $response->getStatusCode(), "Route [$name] returned {$response->getStatusCode()}");
        }
    }

    public function test_teacher_authorized_portal_pages_render_and_finance_stays_forbidden(): void
    {
        $this->seed();
        $teacher = User::where('email', 'teacher@school.test')->firstOrFail();
        foreach (['dashboard', 'students.index', 'attendance.index', 'assessments.index', 'timetable.index', 'approvals.index', 'profile.edit'] as $name) {
            $this->actingAs($teacher)->get(route($name))->assertOk();
        }$this->actingAs($teacher)->get(route('finance.index'))->assertForbidden();
    }
}
