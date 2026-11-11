<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BackupRecord;
use App\Services\Backup\CreateEncryptedBackup;
use App\Services\Operations\SystemHealth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class SecurityController extends Controller
{
    public function index(Request $request): View
    {
        return view('security.index', ['logs' => AuditLog::where('school_id', $request->user()->school_id)->latest('created_at')->paginate(25), 'backups' => BackupRecord::where('school_id', $request->user()->school_id)->latest()->limit(10)->get()]);
    }

    public function auditCsv(Request $request): StreamedResponse
    {
        $data = $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from'], 'event' => ['nullable', 'string', 'max:100'], 'actor_id' => ['nullable', 'integer']]);
        $query = AuditLog::where('school_id', $request->user()->school_id)
            ->when($data['from'] ?? null, fn ($q, $from) => $q->whereDate('created_at', '>=', $from))
            ->when($data['to'] ?? null, fn ($q, $to) => $q->whereDate('created_at', '<=', $to))
            ->when($data['event'] ?? null, fn ($q, $event) => $q->where('event', $event))
            ->when($data['actor_id'] ?? null, fn ($q, $actor) => $q->where('actor_id', $actor))
            ->orderBy('created_at');

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Timestamp', 'Actor ID', 'Event', 'Record Type', 'Record ID', 'IP Address', 'Old Values', 'New Values']);
            $query->chunkById(500, function ($logs) use ($out) {
                foreach ($logs as $log) {
                    fputcsv($out, [$log->created_at?->toIso8601String(), $log->actor_id, $log->event, $log->auditable_type, $log->auditable_id, $log->ip_address, json_encode($log->old_values, JSON_UNESCAPED_UNICODE), json_encode($log->new_values, JSON_UNESCAPED_UNICODE)]);
                }
            });
            fclose($out);
        }, 'audit-log-'.today()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function health(SystemHealth $health): JsonResponse
    {
        return response()->json($health->inspect());
    }

    public function backup(Request $request, CreateEncryptedBackup $service): RedirectResponse
    {
        $record = $service->run($request->user()->school);

        return back()->with('success', 'Encrypted backup created: '.$record->filename);
    }
}
