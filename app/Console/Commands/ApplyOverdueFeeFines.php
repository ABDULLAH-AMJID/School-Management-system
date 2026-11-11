<?php

namespace App\Console\Commands;

use App\Models\FeeFineApplication;
use App\Models\FeeInvoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ApplyOverdueFeeFines extends Command
{
    protected $signature = 'school:fees:apply-fines {--date=}';

    protected $description = 'Apply configured fines to overdue unpaid fee installments';

    public function handle(): int
    {
        $date = $this->option('date') ? now()->parse($this->option('date'))->startOfDay() : today();
        $count = 0;
        FeeInvoice::with('installment')->whereNotNull('fee_installment_id')->whereColumn('paid', '<', 'total')->whereIn('status', ['issued', 'partially_paid', 'overdue'])->chunkById(100, function ($invoices) use ($date, &$count) {
            foreach ($invoices as $invoice) {
                $installment = $invoice->installment;
                if (! $installment || $installment->fine_amount <= 0 || $date->lte($installment->due_date->copy()->addDays($installment->fine_grace_days))) {
                    continue;
                }DB::transaction(function () use ($invoice, $installment, $date, &$count) {
                    $invoice = FeeInvoice::lockForUpdate()->findOrFail($invoice->id);
                    $fine = FeeFineApplication::firstOrCreate(['fee_invoice_id' => $invoice->id, 'fee_installment_id' => $installment->id], ['amount' => $installment->fine_amount, 'applied_on' => $date, 'status' => 'applied']);
                    if ($fine->wasRecentlyCreated) {
                        $invoice->increment('fine', $installment->fine_amount);
                        $invoice->increment('total', $installment->fine_amount);
                        $invoice->update(['status' => 'overdue']);
                        $count++;
                    }
                });
            }
        });
        $this->info("Applied {$count} overdue fine(s).");

        return self::SUCCESS;
    }
}
