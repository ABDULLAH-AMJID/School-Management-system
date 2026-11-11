<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FeeInvoice;
use App\Services\Finance\CollectFeePayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class FinanceController extends Controller
{
    public function index(Request $request): View
    {
        $invoices = FeeInvoice::where('school_id', $request->user()->school_id)->with('student')->latest()->paginate(20);
        $totals = ['billed' => FeeInvoice::where('school_id', $request->user()->school_id)->sum('total'), 'collected' => FeeInvoice::where('school_id', $request->user()->school_id)->sum('paid')];

        return view('finance.index', compact('invoices', 'totals'));
    }

    public function collect(Request $request, FeeInvoice $invoice, CollectFeePayment $service): RedirectResponse
    {
        abort_unless($invoice->school_id === $request->user()->school_id, 403);
        $data = $request->validate(['amount' => ['required', 'numeric', 'min:1'], 'method' => ['required', Rule::in(['cash', 'bank', 'easypaisa', 'jazzcash'])], 'reference' => ['nullable', 'string', 'max:100']]);
        $payment = $service->handle($invoice, (int) round($data['amount'] * 100), $data['method'], $data['reference'] ?? null, $request->user());

        return back()->with('success', 'Payment posted. Receipt '.$payment->receipt_no.' generated.');
    }
}
