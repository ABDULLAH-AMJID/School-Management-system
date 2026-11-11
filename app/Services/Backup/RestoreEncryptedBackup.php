<?php

namespace App\Services\Backup;

use App\Models\BackupRecord;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class RestoreEncryptedBackup
{
    public function run(BackupRecord $record, ?string $target = null): string
    {
        if ($record->status !== 'completed') {
            throw new RuntimeException('Only completed backups may be restored.');
        }$disk = Storage::disk($record->disk);
        if (! $disk->exists($record->filename)) {
            throw new RuntimeException('Backup file is missing.');
        }$encrypted = $disk->get($record->filename);
        if (! hash_equals($record->checksum, hash('sha256', $encrypted))) {
            throw new RuntimeException('Backup checksum verification failed.');
        }$plain = base64_decode(Crypt::decryptString($encrypted), true);
        if ($plain === false || ! str_starts_with($plain, "SQLite format 3\0")) {
            throw new RuntimeException('Decrypted backup is not a valid SQLite database.');
        }$target ??= config('database.connections.sqlite.database');
        if (! $target || $target === ':memory:') {
            throw new RuntimeException('Restore requires a file-based SQLite target.');
        }$directory = dirname($target);
        if (! is_dir($directory)) {
            mkdir($directory, 0750, true);
        }$temporary = $target.'.restore-'.bin2hex(random_bytes(6));
        if (file_put_contents($temporary, $plain, LOCK_EX) === false) {
            throw new RuntimeException('Unable to write restored database.');
        }$safety = $target.'.before-restore-'.now()->format('Ymd-His');
        if (is_file($target) && ! copy($target, $safety)) {
            @unlink($temporary);
            throw new RuntimeException('Unable to create pre-restore safety copy.');
        }if (! rename($temporary, $target)) {
            @unlink($temporary);
            throw new RuntimeException('Unable to activate restored database.');
        }chmod($target, 0640);

        return $safety;
    }
}
