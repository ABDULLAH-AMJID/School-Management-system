<?php

namespace App\Http\Controllers\Approvals;

use App\Http\Controllers\Controller;
use App\Models\ExpenseVoucher;
use App\Models\FeeConcession;
use App\Models\LeaveRequest;
use App\Models\OutboundMessage;
use App\Models\PayrollRun;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ApprovalInboxController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $sid = $user->school_id;
        $groups = collect();
        if ($user->hasPermission('messages.approve')) {
            $groups->put('Messages', OutboundMessage::where(['school_id' => $sid, 'status' => 'pending_approval'])->latest()->limit(25)->get()->map(fn ($m) => ['title' => ucwords(str_replace('_', ' ', $m->event)), 'detail' => $m->recipient.' · '.str($m->body)->limit(80), 'date' => $m->created_at, 'approve' => route('communication.approve', $m), 'module' => route('communication.index')]));
        }if ($user->hasPermission('hr.approve')) {
            $groups->put('Leave requests', LeaveRequest::whereHas('staffProfile', fn ($q) => $q->where('school_id', $sid))->where('status', 'pending')->with('staffProfile.user')->latest()->limit(25)->get()->map(fn ($l) => ['title' => $l->staffProfile->user->name, 'detail' => ucfirst($l->type).' · '.$l->starts_on->format('d M').'–'.$l->ends_on->format('d M'), 'date' => $l->created_at, 'module' => route('hr.index')]));
        }if ($user->hasPermission('expenses.approve')) {
            $groups->put('Expense vouchers', ExpenseVoucher::where(['school_id' => $sid, 'status' => 'pending'])->latest()->limit(25)->get()->map(fn ($v) => ['title' => $v->number.' · '.$v->payee, 'detail' => 'PKR '.number_format($v->amount / 100, 2).' · '.$v->description, 'date' => $v->created_at, 'approve' => route('finance.expenses.approve', $v), 'module' => route('finance.index')]));
        }if ($user->hasPermission('concessions.approve')) {
            $groups->put('Fee concessions', FeeConcession::where(['school_id' => $sid, 'status' => 'pending'])->with('student')->latest()->limit(25)->get()->map(fn ($c) => ['title' => $c->student->full_name, 'detail' => ucfirst($c->type).' · PKR '.number_format($c->amount / 100, 2), 'date' => $c->created_at, 'approve' => route('concessions.approve', $c), 'module' => route('finance.index')]));
        }if ($user->hasPermission('payroll.approve')) {
            $groups->put('Payroll', PayrollRun::where(['school_id' => $sid, 'status' => 'draft'])->withCount('payslips')->latest()->limit(25)->get()->map(fn ($r) => ['title' => 'Payroll '.$r->period, 'detail' => $r->payslips_count.' salary slips', 'date' => $r->created_at, 'approve' => route('hr.payroll.approve', $r), 'module' => route('hr.index')]));
        }

return view('approvals.index', ['groups' => $groups, 'total' => $groups->flatten(1)->count()]);
    }
}
