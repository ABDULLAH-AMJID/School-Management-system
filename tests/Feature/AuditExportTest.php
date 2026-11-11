<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_principal_can_export_filtered_school_audit_history(): void
    {
        $this->seed();
        $u = User::where('email', 'principal@school.test')->firstOrFail();
        AuditLog::create(['school_id' => $u->school_id, 'actor_id' => $u->id, 'event' => 'finance.approved', 'auditable_type' => 'Voucher', 'auditable_id' => '12', 'old_values' => ['status' => 'pending'], 'new_values' => ['status' => 'approved'], 'ip_address' => '10.0.0.2', 'created_at' => now()]);
        AuditLog::create(['school_id' => $u->school_id, 'actor_id' => $u->id, 'event' => 'student.updated', 'created_at' => now()]);
        $content = $this->actingAs($u)->get(route('security.audit.csv', ['event' => 'finance.approved']))->assertOk()->streamedContent();
        $this->assertStringContainsString('finance.approved', $content);
        $this->assertStringContainsString('Voucher', $content);
        $this->assertStringNotContainsString('student.updated', $content);
    }

    public function test_audit_export_rejects_invalid_date_range(): void
    {
        $this->seed();
        $u = User::where('email', 'principal@school.test')->firstOrFail();
        $this->actingAs($u)->get(route('security.audit.csv', ['from' => '2026-12-10', 'to' => '2026-12-01']))->assertSessionHasErrors('to');
    }
}
