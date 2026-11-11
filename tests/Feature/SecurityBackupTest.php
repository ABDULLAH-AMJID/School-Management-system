<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BackupRecord;
use App\Models\User;
use App\Services\Backup\CreateEncryptedBackup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityBackupTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_mutation_is_audited_without_csrf_token(): void
    {
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();

        $this->actingAs($principal)->post(route('engagement.notices.store'), [
            'title' => 'Audit test', 'body' => 'This action must be audited.', 'audience' => 'all_staff',
            'starts_on' => today()->toDateString(), 'ends_on' => today()->addDay()->toDateString(),
            '_token' => 'must-never-be-stored',
        ])->assertRedirect();

        $log = AuditLog::where('event', 'post:engagement.notices.store')->firstOrFail();
        $this->assertSame($principal->id, $log->actor_id);
        $this->assertArrayNotHasKey('_token', $log->new_values);
        $this->assertSame('Audit test', $log->new_values['title']);
    }

    public function test_database_backup_is_encrypted_and_checksummed(): void
    {
        Storage::fake('local');
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();

        $record = app(CreateEncryptedBackup::class)->run($principal->school);

        Storage::disk('local')->assertExists($record->filename);
        $encrypted = Storage::disk('local')->get($record->filename);
        $this->assertStringNotContainsString('SQLite format 3', $encrypted);
        $this->assertSame(hash('sha256', $encrypted), $record->checksum);
        $this->assertSame('completed', BackupRecord::first()->status);
    }
}
