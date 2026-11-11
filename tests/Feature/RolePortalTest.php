<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_navigation_only_shows_authorized_workspaces(): void
    {
        $this->seed();
        $teacher = User::where('email', 'teacher@school.test')->firstOrFail();
        $this->actingAs($teacher)->get('/')->assertOk()->assertSee('Attendance')->assertSee('Timetable')->assertDontSee('Fees & Accounts')->assertDontSee('Access Control')->assertSee('Teacher');
    }

    public function test_principal_navigation_exposes_administration_workspaces(): void
    {
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();
        $this->actingAs($principal)->get('/')->assertOk()->assertSee('Fees & Accounts')->assertSee('Access Control')->assertSee('Security & Backup')->assertSee('Principal');
    }
}
