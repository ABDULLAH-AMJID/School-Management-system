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
        $data = $request->validate(['template_id' => ['required', Rule::exists('message_templates', 'id')], 'student_id' => ['required', Rule::exists('students', 'id')], 'recipient' => ['required', 'regex:/^\+[1-9]\d{7,14}$/'], 'scheduled_for' => ['nullable', 'date', 'after_or_equal:now']]);
        $template = MessageTemplate::where('school_id', $request->user()->school_id)->findOrFail($data['template_id']);
        $student = Student::where('school_id', $request->user()->school_id)->findOrFail($data['student_id']);
        $message = $service->create($template, $student, $data['recipient'], $request->user(), [], $data['scheduled_for'] ?? null);

        return back()->with('success', $message->status === 'approved' ? 'Message queued for delivery.' : 'Message created and awaits approval.');
    }

    public function storeTemplate(Request $request): RedirectResponse
    {
        $data = $request->validate(['event' => ['required', 'alpha_dash', 'max:80'], 'name' => ['required', 'string', 'max:120'], 'body' => ['required', 'string', 'max:4000'], 'mode' => ['required', Rule::in(['automatic', 'approval', 'manual', 'disabled'])], 'quiet_from' => ['nullable', 'date_format:H:i'], 'quiet_until' => ['nullable', 'date_format:H:i']]);
        MessageTemplate::create([...$data, 'school_id' => $request->user()->school_id, 'active' => true]);

        return back()->with('success', 'Message template created.');
    }

    public function updateTemplate(Request $request, MessageTemplate $template): RedirectResponse
    {
        abort_unless($template->school_id === $request->user()->school_id, 403);
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'body' => ['required', 'string', 'max:4000'], 'mode' => ['required', Rule::in(['automatic', 'approval', 'manual', 'disabled'])], 'quiet_from' => ['nullable', 'date_format:H:i'], 'quiet_until' => ['nullable', 'date_format:H:i'], 'active' => ['required', 'boolean']]);
        $template->update($data);

        return back()->with('success', 'Message template updated.');
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

        return back()->with('success', 'Message cancelled.');
    }
}
