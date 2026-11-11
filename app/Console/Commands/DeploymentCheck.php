<?php

namespace App\Console\Commands;

use App\Services\Operations\DeploymentReadiness;
use Illuminate\Console\Command;

class DeploymentCheck extends Command
{
    protected $signature = 'school:deployment-check {--json}';

    protected $description = 'Validate production settings before exposing the ERP on the school LAN';

    public function handle(DeploymentReadiness $service): int
    {
        $result = $service->inspect();
        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT));
        } else {
            foreach ($result['checks'] as $c) {
                $this->line(($c['ok'] ? '[OK] ' : '[FIX] ').$c['key'].' — '.$c['detail']);
            }
        }

return $result['ready'] ? self::SUCCESS : self::FAILURE;
    }
}
