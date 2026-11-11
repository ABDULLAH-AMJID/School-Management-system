<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InterfaceAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_shell_is_local_first_keyboard_accessible_and_mobile_ready(): void
    {
        $this->seed();
        $u = User::where('email', 'principal@school.test')->firstOrFail();
        $response = $this->actingAs($u)->get('/')->assertOk()->assertSee('Skip to main content')->assertSee('aria-label="Toggle navigation"', false)->assertSee('id="main-content"', false)->assertSee('name="viewport"', false);
        $html = $response->getContent();
        $this->assertStringNotContainsString('fonts.googleapis.com', $html);
        $this->assertStringContainsString('prefers-reduced-motion', $html);
        $this->assertStringContainsString('@media print', $html);
        $this->assertStringContainsString('window.erpEscape', $html);
        $this->assertStringContainsString('erpEscape(x.title)', $html);
    }

    public function test_mobile_navigation_is_permission_aware_for_teacher(): void
    {
        $this->seed();
        $u = User::where('email', 'teacher@school.test')->firstOrFail();
        $this->actingAs($u)->get('/')->assertOk()->assertSee('Toggle navigation')->assertSee('Attendance')->assertDontSee('Bank Reconciliation')->assertDontSee('School Settings');
    }
}
