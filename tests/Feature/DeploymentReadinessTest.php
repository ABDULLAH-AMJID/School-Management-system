<?php

namespace Tests\Feature;

use App\Services\Operations\DeploymentReadiness;
use Tests\TestCase;

class DeploymentReadinessTest extends TestCase
{
    public function test_production_configuration_can_pass_deployment_gate(): void
    {
        config(['app.env' => 'production', 'app.debug' => false, 'app.key' => 'base64:test-key', 'database.connections.sqlite.database' => '/srv/school/database.sqlite', 'queue.default' => 'database', 'session.driver' => 'database', 'services.whatsapp.connector_url' => 'http://127.0.0.1:3210', 'services.whatsapp.connector_token' => str_repeat('x', 32)]);
        $result = app(DeploymentReadiness::class)->inspect();
        $this->assertTrue($result['ready']);
        $this->assertNotContains(false, array_column($result['checks'], 'ok'));
    }

    public function test_unsafe_development_configuration_fails_with_actionable_checks(): void
    {
        config(['app.env' => 'local', 'app.debug' => true, 'database.connections.sqlite.database' => ':memory:', 'queue.default' => 'sync', 'session.driver' => 'file', 'services.whatsapp.connector_url' => null, 'services.whatsapp.connector_token' => 'short']);
        $result = app(DeploymentReadiness::class)->inspect();
        $this->assertFalse($result['ready']);
        $failed = collect($result['checks'])->where('ok', false)->pluck('key');
        $this->assertTrue($failed->contains('debug'));
        $this->assertTrue($failed->contains('database'));
        $this->assertTrue($failed->contains('whatsapp_token'));
    }

    public function test_deployment_command_returns_failure_for_unsafe_settings(): void
    {
        config(['app.env' => 'local', 'app.debug' => true]);
        $this->artisan('school:deployment-check')->expectsOutputToContain('[FIX] environment')->assertFailed();
    }
}
