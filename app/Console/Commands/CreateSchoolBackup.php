<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Services\Backup\CreateEncryptedBackup;
use Illuminate\Console\Command;

class CreateSchoolBackup extends Command
{
    protected $signature = 'school:backup';

    protected $description = 'Create an encrypted local school database backup';

    public function handle(CreateEncryptedBackup $service): int
    {
        $record = $service->run(School::first());
        $this->info("Encrypted backup created: {$record->filename}");

        return self::SUCCESS;
    }
}
