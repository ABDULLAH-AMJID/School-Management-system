<?php

namespace App\Console\Commands;

use App\Models\OutboundMessage;
use Illuminate\Console\Command;

class RetryFailedMessages extends Command
{
    protected $signature = 'school:messages:retry {--older-than=5}';

    protected $description = 'Make eligible failed messages available to the scheduled dispatcher';

    public function handle(): int
    {
        $count = OutboundMessage::where('status', 'failed')->where('attempts', '<', 4)->where('updated_at', '<=', now()->subMinutes((int) $this->option('older-than')))->update(['status' => 'approved', 'scheduled_for' => now()]);
        $this->info("Released {$count} failed message(s) for retry.");

        return self::SUCCESS;
    }
}
