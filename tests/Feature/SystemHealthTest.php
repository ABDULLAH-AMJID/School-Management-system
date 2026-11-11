<?php

namespace Tests\Feature;

use App\Models\BackupRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SystemHealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_health_reports_operational_components_without_false_whatsapp_state(): void
    {
        $this->seed();
        Storage::fake('local');
        config(['services.whatsapp.connector_url' => 'http://connector.test', 'services.whatsapp.connector_token' => 'secret', 'queue.default' => 'database']);
        Http::fake(['http://connector.test/api/health' => Http::response(['connected' => false, 'state' => 'qr_required'])]);
        BackupRecord::create(['school_id' => User::first()->school_id, 'filename' => 'backup', 'disk' => 'local', 'size' => 10, 'checksum' => str_repeat('a', 64), 'status' => 'completed', 'started_at' => now(), 'completed_at' => now()]);
        $u = User::where('email', 'principal@school.test')->firstOrFail();
        $response = $this->actingAs($u)->get(route('security.health'))->assertOk()->assertJsonPath('checks.database.ok', true)->assertJsonPath('checks.storage.ok', true)->assertJsonPath('checks.backup.ok', true)->assertJsonPath('checks.queue.ok', true)->assertJsonPath('checks.whatsapp.ok', false)->assertJsonPath('checks.whatsapp.detail', 'qr_required');
        $this->assertTrue($response->json('healthy'));
    }

    public function test_health_command_returns_failure_when_required_backup_is_missing(): void
    {
        $this->seed();
        Storage::fake('local');
        config(['services.whatsapp.connector_url' => null, 'queue.default' => 'database']);
        BackupRecord::query()->delete();
        $this->artisan('school:health')->expectsOutputToContain('[WARN] Recent encrypted backup')->assertFailed();
    }
}
