<?php

namespace App\Services\Operations;

use App\Models\BackupRecord;
use App\Services\Messaging\WhatsAppWebProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class SystemHealth
{
    public function __construct(private WhatsAppWebProvider $whatsapp) {}

    public function inspect(): array
    {
        $checks = [];
        $checks['database'] = $this->check(fn () => DB::select('select 1'), 'Database connection');
        $checks['storage'] = $this->check(function () {
            Storage::disk('local')->put('health/.probe', 'ok');
            Storage::disk('local')->delete('health/.probe');
        }, 'Writable local storage');
        $backup = BackupRecord::where('status', 'completed')->latest('completed_at')->first();
        $checks['backup'] = [
            'ok' => (bool) ($backup && $backup->completed_at?->greaterThan(now()->subDays(2))),
            'label' => 'Recent encrypted backup',
            'detail' => $backup ? ($backup->completed_at?->diffForHumans() ?? 'Incomplete') : 'No completed backup',
        ];
        $checks['queue'] = ['ok' => config('queue.default') !== 'sync', 'label' => 'Background queue', 'detail' => config('queue.default') === 'sync' ? 'Sync mode; use database queue in deployment' : config('queue.default')];
        try {
            $health = $this->whatsapp->health();
            $checks['whatsapp'] = ['ok' => (bool) ($health['connected'] ?? false), 'label' => 'WhatsApp connector', 'detail' => $health['state'] ?? 'unknown'];
        } catch (\Throwable) {
            $checks['whatsapp'] = ['ok' => false, 'label' => 'WhatsApp connector', 'detail' => 'unavailable'];
        }

        return ['healthy' => collect($checks)->except('whatsapp')->every(fn ($check) => $check['ok']), 'checked_at' => now()->toIso8601String(), 'checks' => $checks];
    }

    private function check(callable $callback, string $label): array
    {
        try {
            $callback();

            return ['ok' => true, 'label' => $label, 'detail' => 'Ready'];
        } catch (\Throwable $exception) {
            return ['ok' => false, 'label' => $label, 'detail' => mb_substr($exception->getMessage(), 0, 120)];
        }
    }
}
