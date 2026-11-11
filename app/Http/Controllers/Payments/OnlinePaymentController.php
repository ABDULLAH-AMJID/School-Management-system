<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\FeeInvoice;
use App\Services\Payments\InitiateOnlinePayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class OnlinePaymentController extends Controller
{
    public function store(Request $r, FeeInvoice $invoice, InitiateOnlinePayment $service): RedirectResponse
    {
        abort_unless($invoice->school_id === $r->user()->school_id, 403);
        $d = $r->validate(['amount' => ['required', 'numeric', 'min:1'], 'provider' => ['required', Rule::in(['easypaisa', 'jazzcash', 'bank_gateway'])], 'idempotency_key' => ['required', 'uuid']]);
        $tx = $service->handle($invoice, (int) round($d['amount'] * 100), $d['provider'], $r->user(), $d['idempotency_key']);

        return back()->with('success', "Online transaction {$tx->public_id}: ".str_replace('_', ' ', $tx->status));
    }
}
