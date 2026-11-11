<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BackupRecord;
use App\Services\Backup\CreateEncryptedBackup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class SecurityController extends Controller
{
    public function index(Request $request): View
    {
        return view('security.index', ['logs' => AuditLog::where('school_id', $request->user()->school_id)->latest('created_at')->paginate(25), 'backups' => BackupRecord::where('school_id', $request->user()->school_id)->latest()->limit(10)->get()]);
    }

    public function backup(Request $request, CreateEncryptedBackup $service): RedirectResponse
    {
        $record = $service->run($request->user()->school);

        return back()->with('success', 'Encrypted backup created: '.$record->filename);
    }
}
