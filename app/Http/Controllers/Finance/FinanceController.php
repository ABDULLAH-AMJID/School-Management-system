<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\ExpenseVoucher;
use App\Models\FeeConcession;
use App\Models\FeeInvoice;
use App\Services\Finance\ApproveExpenseVoucher;
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

        $expenseAccounts = Account::where(['school_id' => $request->user()->school_id, 'type' => 'expense', 'active' => true])->get();
        $paymentAccounts = Account::where('school_id', $request->user()->school_id)->whereIn('type', ['asset'])->where('active', true)->get();
        $concessions = FeeConcession::where('school_id', $request->user()->school_id)->with(['student', 'invoice'])->latest()->limit(20)->get();
        $vouchers = ExpenseVoucher::where('school_id', $request->user()->school_id)->with(['expenseAccount', 'paymentAccount'])->latest()->limit(20)->get();

        return view('finance.index', compact('invoices', 'totals', 'expenseAccounts', 'paymentAccounts', 'vouchers', 'concessions'));
    }

    public function storeExpense(Request $request): RedirectResponse
    {
        $schoolId = $request->user()->school_id;
        $data = $request->validate(['expense_date' => ['required', 'date', 'before_or_equal:today'], 'expense_account_id' => ['required', Rule::exists('accounts', 'id')->where(fn ($q) => $q->where(['school_id' => $schoolId, 'type' => 'expense', 'active' => true]))], 'payment_account_id' => ['required', Rule::exists('accounts', 'id')->where(fn ($q) => $q->where(['school_id' => $schoolId, 'type' => 'asset', 'active' => true]))], 'payee' => ['required', 'string', 'max:150'], 'amount' => ['required', 'numeric', 'min:1'], 'description' => ['required', 'string', 'max:1000'], 'payment_method' => ['required', Rule::in(['cash', 'bank', 'cheque', 'easypaisa', 'jazzcash'])], 'reference' => ['nullable', 'string', 'max:100']]);
        $voucher = ExpenseVoucher::create([...$data, 'school_id' => $schoolId, 'number' => 'EV-'.now()->format('YmdHis').'-'.strtoupper(str()->random(4)), 'amount' => (int) round($data['amount'] * 100), 'submitted_by' => $request->user()->id]);

        return back()->with('success', "Expense voucher {$voucher->number} submitted for approval.");
    }

    public function approveExpense(Request $request, ExpenseVoucher $voucher, ApproveExpenseVoucher $service): RedirectResponse
    {
        abort_unless($voucher->school_id === $request->user()->school_id, 403);
        $service->handle($voucher, $request->user());

        return back()->with('success', 'Expense approved and posted to the ledger.');
    }

    public function rejectExpense(Request $request, ExpenseVoucher $voucher): RedirectResponse
    {
        abort_unless($voucher->school_id === $request->user()->school_id, 403);
        $data = $request->validate(['rejection_reason' => ['required', 'string', 'max:500']]);
        abort_unless($voucher->status === 'pending', 422);
        $voucher->update(['status' => 'rejected', 'rejection_reason' => $data['rejection_reason']]);

        return back()->with('success', 'Expense voucher rejected.');
    }

    public function collect(Request $request, FeeInvoice $invoice, CollectFeePayment $service): RedirectResponse
    {
        abort_unless($invoice->school_id === $request->user()->school_id, 403);
        $data = $request->validate(['amount' => ['required', 'numeric', 'min:1'], 'method' => ['required', Rule::in(['cash', 'bank', 'easypaisa', 'jazzcash'])], 'reference' => ['nullable', 'string', 'max:100']]);
        $payment = $service->handle($invoice, (int) round($data['amount'] * 100), $data['method'], $data['reference'] ?? null, $request->user());

        return back()->with('success', 'Payment posted. Receipt '.$payment->receipt_no.' generated.');
    }
}
