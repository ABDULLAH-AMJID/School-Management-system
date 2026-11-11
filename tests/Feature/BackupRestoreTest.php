<?php

namespace Tests\Feature;

use App\Models\BackupRecord;
use App\Services\Backup\RestoreEncryptedBackup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class BackupRestoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_encrypted_sqlite_backup_is_restored_atomically(): void
    {
        Storage::fake('local');
        $plain = "SQLite format 3\0".str_repeat('x', 100);
        $encrypted = Crypt::encryptString(base64_encode($plain));
        Storage::disk('local')->put('backups/test.encrypted', $encrypted);
        $record = BackupRecord::create(['filename' => 'backups/test.encrypted', 'disk' => 'local', 'size' => strlen($encrypted), 'checksum' => hash('sha256', $encrypted), 'status' => 'completed', 'started_at' => now(), 'completed_at' => now()]);
        $dir = sys_get_temp_dir().'/erp-restore-'.uniqid();
        mkdir($dir);
        $target = $dir.'/database.sqlite';
        file_put_contents($target, "SQLite format 3\0old");
        $safety = app(RestoreEncryptedBackup::class)->run($record, $target);
        $this->assertSame($plain, file_get_contents($target));
        $this->assertFileExists($safety);
        $this->assertStringContainsString('old', file_get_contents($safety));
        @unlink($target);
        @unlink($safety);
        @rmdir($dir);
    }

    public function test_tampered_backup_is_rejected_before_target_changes(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('backups/bad', 'tampered');
        $record = BackupRecord::create(['filename' => 'backups/bad', 'disk' => 'local', 'size' => 8, 'checksum' => str_repeat('0', 64), 'status' => 'completed', 'started_at' => now(), 'completed_at' => now()]);
        $target = sys_get_temp_dir().'/erp-target-'.uniqid().'.sqlite';
        file_put_contents($target, 'original');
        try {
            app(RestoreEncryptedBackup::class)->run($record, $target);
            $this->fail('Expected checksum rejection.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('checksum', $e->getMessage());
        }$this->assertSame('original', file_get_contents($target));
        @unlink($target);
    }
}
