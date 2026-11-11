<?php

namespace App\Http\Controllers\Search;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\FeeInvoice;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GlobalSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']]);
        $q = trim($data['q']);
        $user = $request->user();
        $sid = $user->school_id;
        $results = collect();
        if ($user->hasPermission('students.view')) {
            Student::where('school_id', $sid)->where(fn ($x) => $x->where('admission_no', 'like', "%{$q}%")->orWhere('first_name', 'like', "%{$q}%")->orWhere('last_name', 'like', "%{$q}%"))->limit(8)->get()->each(fn ($s) => $results->push(['type' => 'Student', 'title' => $s->full_name, 'subtitle' => $s->admission_no, 'url' => route('students.show', $s)]));
        }if ($user->hasPermission('finance.manage')) {
            FeeInvoice::with('student')->where('school_id', $sid)->where(fn ($x) => $x->where('number', 'like', "%{$q}%")->orWhereHas('student', fn ($s) => $s->where('admission_no', 'like', "%{$q}%")))->limit(6)->get()->each(fn ($i) => $results->push(['type' => 'Invoice', 'title' => $i->number, 'subtitle' => $i->student->full_name.' · PKR '.number_format($i->balance / 100, 2).' due', 'url' => route('finance.index').'#invoice-'.$i->id]));
        }if ($user->hasPermission('complaints.manage')) {
            Complaint::where('school_id', $sid)->where(fn ($x) => $x->where('reference_no', 'like', "%{$q}%")->orWhere('subject', 'like', "%{$q}%")->orWhere('complainant_name', 'like', "%{$q}%"))->limit(6)->get()->each(fn ($c) => $results->push(['type' => 'Complaint', 'title' => $c->reference_no, 'subtitle' => $c->subject, 'url' => route('engagement.index').'#complaint-'.$c->id]));
        }if ($user->hasPermission('access.manage') || $user->hasPermission('hr.manage')) {
            User::where('school_id', $sid)->where(fn ($x) => $x->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%"))->limit(6)->get()->each(fn ($staff) => $results->push(['type' => 'Staff', 'title' => $staff->name, 'subtitle' => $staff->email, 'url' => route('access.index')]));
        }

return response()->json(['query' => $q, 'results' => $results->take(20)->values()]);
    }
}
