<?php

namespace App\Console\Commands;

use App\Services\Operations\SystemHealth;
use Illuminate\Console\Command;

class SchoolHealthCheck extends Command
{
    protected $signature = 'school:health {--json}';

    protected $description = 'Check local ERP database, storage, backup, queue and WhatsApp readiness';

    public function handle(SystemHealth $health): int
    {
        $result = $health->inspect();
        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT));
        } else {
            foreach ($result['checks'] as $check) {
                $this->line(($check['ok'] ? '[OK] ' : '[WARN] ').$check['label'].' — '.$check['detail']);
            }
        }$this->line('Checked at '.$result['checked_at']);

        return $result['healthy'] ? self::SUCCESS : self::FAILURE;
    }
}
