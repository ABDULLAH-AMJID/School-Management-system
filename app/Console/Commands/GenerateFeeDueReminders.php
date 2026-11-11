<?php

namespace App\Console\Commands;

use App\Models\FeeInvoice;
use App\Models\User;
use App\Services\Messaging\TriggerStudentEventMessage;
use Illuminate\Console\Command;

class GenerateFeeDueReminders extends Command
{
    protected $signature = 'school:fees:remind {--date=} {--days-before=3}';

    protected $description = 'Create consent-aware fee due and overdue reminder messages';

    public function handle(TriggerStudentEventMessage $messenger): int
    {
        $date = $this->option('date') ? now()->parse($this->option('date'))->startOfDay() : today();
        $until = $date->copy()->addDays((int) $this->option('days-before'));
        $created = 0;
        FeeInvoice::with('student')->whereColumn('paid', '<', 'total')->whereDate('due_date', '<=', $until)->whereIn('status', ['issued', 'partially_paid', 'overdue'])->chunkById(200, function ($rows) use ($messenger, $date, &$created) {
            foreach ($rows as $invoice) {
                $actor = User::where('school_id', $invoice->school_id)->whereHas('roles', fn ($q) => $q->where('name', 'principal'))->first();
                if (! $actor) {
                    continue;
                }$days = $date->diffInDays($invoice->due_date, false);
                $label = $days < 0 ? abs($days).' day(s) overdue' : ($days === 0 ? 'due today' : 'due in '.$days.' day(s)');
                $log = $messenger->handle('fee_due', 'fee_due_reminder', $invoice->id.'-'.$date->toDateString(), $invoice->student, $actor, ['invoice_no' => $invoice->number, 'due_date' => $invoice->due_date->toDateString(), 'balance' => number_format($invoice->balance / 100, 2), 'due_status' => $label]);
                if ($log?->status === 'created') {
                    $created++;
                }
            }
        });
        $this->info("Created {$created} fee reminder(s).");

        return self::SUCCESS;
    }
}
