<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppConnectorTest extends TestCase
{
    use RefreshDatabase;

    public function test_connector_status_exposes_health_and_qr_to_authorized_staff(): void
    {
        $this->seed();
        config(['services.whatsapp.connector_url' => 'http://connector.test', 'services.whatsapp.connector_token' => 'secret']);
        Http::fake(['http://connector.test/api/health' => Http::response(['connected' => false, 'state' => 'qr_required']), 'http://connector.test/api/qr' => Http::response(['state' => 'qr_required', 'qr' => 'data:image/png;base64,abc'])]);
        $u = User::where('email', 'principal@school.test')->firstOrFail();
        $this->actingAs($u)->get(route('communication.connector'))->assertOk()->assertJsonPath('health.state', 'qr_required')->assertJsonPath('qr.qr', 'data:image/png;base64,abc');
        Http::assertSent(fn ($r) => $r->hasHeader('Authorization', 'Bearer secret'));
    }

    public function test_connector_unavailable_is_reported_without_false_connected_state(): void
    {
        $this->seed();
        config(['services.whatsapp.connector_url' => 'http://connector.test', 'services.whatsapp.connector_token' => 'secret']);
        Http::fake(fn () => Http::response([], 500));
        $u = User::where('email', 'principal@school.test')->firstOrFail();
        $this->actingAs($u)->get(route('communication.connector'))->assertStatus(503)->assertJsonPath('health.connected', false)->assertJsonPath('health.state', 'unavailable');
    }
}
