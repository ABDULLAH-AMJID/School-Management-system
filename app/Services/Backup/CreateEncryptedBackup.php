<?php

namespace App\Services\Backup;

use App\Models\BackupRecord;
use App\Models\School;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

final class CreateEncryptedBackup
{
    public function run(?School $school = null): BackupRecord
    {
        $record = BackupRecord::create(['school_id' => $school?->id, 'filename' => 'pending', 'disk' => 'local', 'checksum' => str_repeat('0', 64), 'status' => 'running', 'started_at' => now()]);

        try {
            if (config('database.default') !== 'sqlite') {
                throw new RuntimeException('Automated application backup currently requires the local SQLite deployment profile.');
            }

            $path = DB::connection()->getDatabaseName();
            if ($path === ':memory:') {
                $tables = collect(DB::select("select name from sqlite_master where type = 'table' and name not like 'sqlite_%'"))->pluck('name');
                $plain = json_encode($tables->mapWithKeys(fn ($table) => [
                    $table => DB::table($table)->get()->map(fn ($row) => (array) $row)->all(),
                ])->all(), JSON_THROW_ON_ERROR);
            } else {
                if (! is_file($path)) {
                    throw new RuntimeException('Database file not found.');
                }
                $plain = file_get_contents($path);
            }

            $encrypted = Crypt::encryptString(base64_encode($plain));
            $filename = 'backups/school-'.now()->format('Ymd-His').'.sqlite.encrypted';
            Storage::disk('local')->put($filename, $encrypted);
            $record->update(['filename' => $filename, 'size' => strlen($encrypted), 'checksum' => hash('sha256', $encrypted), 'status' => 'completed', 'completed_at' => now()]);

            return $record;
        } catch (Throwable $exception) {
            $record->update(['status' => 'failed', 'error' => $exception->getMessage(), 'completed_at' => now()]);
            throw $exception;
        }
    }
}
