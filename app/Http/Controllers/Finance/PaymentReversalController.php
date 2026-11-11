<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentReversal;
use App\Services\Finance\ApprovePaymentReversal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentReversalController extends Controller
{
    public function store(Request $r, Payment $payment): RedirectResponse
    {
        abort_unless($payment->school_id === $r->user()->school_id, 403);
        abort_unless($payment->status === 'posted', 422);
        $data = $r->validate(['reason' => ['required', 'string', 'min:10', 'max:1000']]);
        $reversal = PaymentReversal::create(['school_id' => $payment->school_id, 'payment_id' => $payment->id, 'amount' => $payment->amount, 'reason' => $data['reason'], 'requested_by' => $r->user()->id]);

        return back()->with('success', "Reversal request #{$reversal->id} submitted.");
    }

    public function approve(Request $r, PaymentReversal $reversal, ApprovePaymentReversal $service): RedirectResponse
    {
        abort_unless($reversal->school_id === $r->user()->school_id, 403);
        $service->handle($reversal, $r->user());

        return back()->with('success', 'Payment reversed with balanced accounting entry.');
    }
}
