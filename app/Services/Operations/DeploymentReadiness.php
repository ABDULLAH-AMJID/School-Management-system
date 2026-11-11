<?php

namespace App\Services\Operations;

final class DeploymentReadiness
{
    public function inspect(): array
    {
        $sqlite = config('database.connections.sqlite.database');
        $checks = [['key' => 'environment', 'ok' => config('app.env') === 'production', 'detail' => 'APP_ENV must be production'], ['key' => 'debug', 'ok' => config('app.debug') === false, 'detail' => 'APP_DEBUG must be false'], ['key' => 'app_key', 'ok' => (bool) config('app.key'), 'detail' => 'APP_KEY must be generated'], ['key' => 'database', 'ok' => is_string($sqlite) && $sqlite !== ':memory:' && str_starts_with($sqlite, DIRECTORY_SEPARATOR), 'detail' => 'SQLite database must use an absolute file path'], ['key' => 'queue', 'ok' => config('queue.default') === 'database', 'detail' => 'QUEUE_CONNECTION must be database'], ['key' => 'session', 'ok' => config('session.driver') === 'database', 'detail' => 'SESSION_DRIVER should be database'], ['key' => 'whatsapp_url', 'ok' => (bool) config('services.whatsapp.connector_url'), 'detail' => 'WhatsApp connector URL must be configured'], ['key' => 'whatsapp_token', 'ok' => strlen((string) config('services.whatsapp.connector_token')) >= 32, 'detail' => 'WhatsApp connector token must be at least 32 characters'], ['key' => 'openssl', 'ok' => extension_loaded('openssl'), 'detail' => 'OpenSSL extension required'], ['key' => 'pdo_sqlite', 'ok' => extension_loaded('pdo_sqlite'), 'detail' => 'PDO SQLite extension required'], ['key' => 'mbstring', 'ok' => extension_loaded('mbstring'), 'detail' => 'Mbstring extension required']];

        return ['ready' => collect($checks)->every(fn ($c) => $c['ok']), 'checks' => $checks];
    }
}
