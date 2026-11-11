<?php

namespace App\Http\Controllers\Communication;

use App\Http\Controllers\Controller;
use App\Jobs\SendOutboundMessage;
use App\Models\MessageTemplate;
use App\Models\OutboundMessage;
use App\Models\Student;
use App\Services\Messaging\QueueMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class CommunicationController extends Controller
{
    public function index(Request $request): View
    {
        $schoolId = $request->user()->school_id;

        return view('communication.index', ['messages' => OutboundMessage::where('school_id', $schoolId)->with(['student', 'template'])->latest()->paginate(20), 'templates' => MessageTemplate::where('school_id', $schoolId)->where('active', true)->get(), 'students' => Student::where('school_id', $schoolId)->where('status', 'active')->orderBy('first_name')->get()]);
    }

    public function store(Request $request, QueueMessage $service): RedirectResponse
    {
        $data = $request->validate(['template_id' => ['required', Rule::exists('message_templates', 'id')], 'student_id' => ['required', Rule::exists('students', 'id')], 'recipient' => ['required', 'regex:/^\+[1-9]\d{7,14}$/']]);
        $template = MessageTemplate::where('school_id', $request->user()->school_id)->findOrFail($data['template_id']);
        $student = Student::where('school_id', $request->user()->school_id)->findOrFail($data['student_id']);
        $message = $service->create($template, $student, $data['recipient'], $request->user());

        return back()->with('success', $message->status === 'approved' ? 'Message queued for delivery.' : 'Message created and awaits approval.');
    }

    public function approve(Request $request, OutboundMessage $message): RedirectResponse
    {
        abort_unless($message->school_id === $request->user()->school_id, 403);
        abort_unless($message->status === 'pending_approval', 409);
        $message->update(['status' => 'approved', 'approved_by' => $request->user()->id, 'approved_at' => now()]);
        SendOutboundMessage::dispatch($message->id);

        return back()->with('success', 'Message approved and queued.');
    }

    public function cancel(Request $request, OutboundMessage $message): RedirectResponse
    {
        abort_unless($message->school_id === $request->user()->school_id, 403);
        abort_unless(in_array($message->status, ['pending_approval', 'approved', 'failed'], true), 409);
        $message->update(['status' => 'cancelled']);

        return back()->with('success','Message cancelled.');
    }
}
