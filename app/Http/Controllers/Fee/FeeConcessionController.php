<?php

namespace App\Http\Controllers\Fee;

use App\Http\Controllers\Controller;
use App\Models\FeeConcession;
use App\Models\FeeInvoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

final class FeeConcessionController extends Controller
{
    public function store(Request $r, FeeInvoice $invoice): RedirectResponse
    {
        abort_unless($invoice->school_id === $r->user()->school_id, 403);
        $d = $r->validate(['type' => ['required', Rule::in(['scholarship', 'sibling', 'staff_child', 'hardship', 'special'])], 'amount' => ['required', 'numeric', 'min:1'], 'reason' => ['required', 'string', 'max:1000']]);
        $amount = (int) round($d['amount'] * 100);
        abort_if($amount > $invoice->total - $invoice->paid, 422, 'Concession exceeds outstanding balance.');
        $c = FeeConcession::create(['school_id' => $invoice->school_id, 'student_id' => $invoice->student_id, 'fee_invoice_id' => $invoice->id, 'type' => $d['type'], 'amount' => $amount, 'reason' => $d['reason'], 'requested_by' => $r->user()->id]);

        return back()->with('success', "Concession request #{$c->id} submitted.");
    }

    public function approve(Request $r, FeeConcession $concession): RedirectResponse
    {
        abort_unless($concession->school_id === $r->user()->school_id, 403);
        DB::transaction(function () use ($concession, $r) {
            $c = FeeConcession::lockForUpdate()->findOrFail($concession->id);
            abort_unless($c->status === 'pending', 422);
            $invoice = FeeInvoice::lockForUpdate()->findOrFail($c->fee_invoice_id);
            abort_if($c->amount > $invoice->total - $invoice->paid, 422, 'Concession exceeds current balance.');
            $invoice->increment('discount', $c->amount);
            $invoice->decrement('total', $c->amount);
            if ($invoice->paid === $invoice->fresh()->total) {
                $invoice->update(['status' => 'paid']);
            }$c->update(['status' => 'approved', 'reviewed_by' => $r->user()->id, 'reviewed_at' => now()]);
        });

        return back()->with('success', 'Concession approved and invoice adjusted.');
    }

    public function reject(Request $r, FeeConcession $concession): RedirectResponse
    {
        abort_unless($concession->school_id === $r->user()->school_id, 403);
        $d = $r->validate(['review_note' => ['required', 'string', 'max:500']]);
        abort_unless($concession->status === 'pending', 422);
        $concession->update(['status' => 'rejected', 'reviewed_by' => $r->user()->id, 'reviewed_at' => now(), 'review_note' => $d['review_note']]);

        return back()->with('success','Concession rejected.');
    }
}
