<?php

namespace App\Console\Commands;

use App\Models\BackupRecord;
use App\Services\Backup\RestoreEncryptedBackup;
use Illuminate\Console\Command;

class RestoreSchoolBackup extends Command
{
    protected $signature = 'school:backup:restore {backup : Backup record ID} {--force : Skip interactive confirmation}';

    protected $description = 'Verify and restore an encrypted local SQLite backup';

    public function handle(RestoreEncryptedBackup $service): int
    {
        $record = BackupRecord::find($this->argument('backup'));
        if (! $record) {
            $this->error('Backup record not found.');

            return self::FAILURE;
        }if (! $this->option('force') && ! $this->confirm('This replaces the active database. Continue?')) {
            return self::FAILURE;
        }try {
            $safety = $service->run($record);
            $this->info('Backup restored successfully.');
            $this->line('Pre-restore safety copy: '.$safety);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
