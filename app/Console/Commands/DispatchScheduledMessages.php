<?php

namespace App\Console\Commands;

use App\Jobs\SendOutboundMessage;
use App\Models\OutboundMessage;
use Illuminate\Console\Command;

class DispatchScheduledMessages extends Command
{
    protected $signature = 'school:messages:dispatch {--limit=200}';

    protected $description = 'Queue due approved WhatsApp messages while respecting quiet hours';

    public function handle(): int
    {
        $queued = 0;
        $now = now();
        OutboundMessage::with('template')->whereIn('status', ['approved', 'failed'])->where(fn ($q) => $q->whereNull('scheduled_for')->orWhere('scheduled_for', '<=', $now))->where('attempts', '<', 4)->orderBy('scheduled_for')->limit((int) $this->option('limit'))->get()->each(function ($message) use (&$queued, $now) {
            $template = $message->template;
            if ($template && $this->insideQuietHours($now->format('H:i:s'), $template->quiet_from, $template->quiet_until)) {
                return;
            }
            $claimed = OutboundMessage::whereKey($message->id)
                ->whereIn('status', ['approved', 'failed'])
                ->update(['status' => 'queued']);
            if ($claimed === 1) {
                SendOutboundMessage::dispatch($message->id);
                $queued++;
            }
        });
        $this->info("Queued {$queued} message(s) for delivery.");

        return self::SUCCESS;
    }

    private function insideQuietHours(string $time, ?string $from, ?string $until): bool
    {
        if (! $from || ! $until) {
            return false;
        }$from = substr($from, 0, 8);
        $until = substr($until, 0, 8);

        return $from <= $until ? $time >= $from && $time < $until : $time >= $from || $time < $until;
    }
}
