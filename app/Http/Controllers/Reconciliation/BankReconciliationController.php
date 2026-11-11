<?php

namespace App\Http\Controllers\Reconciliation;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\BankReconciliation;
use App\Models\JournalLine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class BankReconciliationController extends Controller
{
    public function index(Request $r): View
    {
        $sid = $r->user()->school_id;

        return view('reconciliation.index', ['accounts' => Account::where(['school_id' => $sid, 'type' => 'asset', 'active' => true])->get(), 'runs' => BankReconciliation::where('school_id', $sid)->with(['account', 'items'])->latest('statement_date')->get()]);
    }

    public function store(Request $r): RedirectResponse
    {
        $sid = $r->user()->school_id;
        $d = $r->validate(['account_id' => ['required', Rule::exists('accounts', 'id')->where(fn ($q) => $q->where(['school_id' => $sid, 'type' => 'asset']))], 'statement_date' => ['required', 'date', 'before_or_equal:today'], 'statement_balance' => ['required', 'numeric']]);
        $account = Account::findOrFail($d['account_id']);
        $ledger = JournalLine::where('account_id', $account->id)->whereHas('journalEntry', fn ($q) => $q->whereDate('entry_date', '<=', $d['statement_date'])->where('status', 'posted'))->selectRaw('COALESCE(SUM(debit-credit),0) balance')->value('balance');
        $run = BankReconciliation::create(['school_id' => $sid, 'account_id' => $account->id, 'statement_date' => $d['statement_date'], 'statement_balance' => (int) round($d['statement_balance'] * 100), 'ledger_balance' => $ledger, 'difference' => (int) round($d['statement_balance'] * 100) - $ledger, 'created_by' => $r->user()->id]);

        return back()->with('success', 'Reconciliation created with difference PKR '.number_format($run->difference / 100, 2));
    }

    public function complete(Request $r, BankReconciliation $run): RedirectResponse
    {
        abort_unless($run->school_id === $r->user()->school_id, 403);
        DB::transaction(function () use ($run, $r) {
            $run = BankReconciliation::lockForUpdate()->findOrFail($run->id);
            abort_unless($run->status === 'draft', 422);
            abort_if($run->difference !== 0, 422, 'Reconciliation difference must be zero.');
            $run->update(['status' => 'completed', 'completed_by' => $r->user()->id, 'completed_at' => now()]);
        });

        return back()->with('success','Bank reconciliation completed and locked.');
    }
}
