<?php

namespace App\Http\Controllers\Certificate;

use App\Http\Controllers\Controller;
use App\Models\CertificateTemplate;
use App\Models\IssuedCertificate;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class CertificateController extends Controller
{
    public function index(Request $request): View
    {
        $schoolId = $request->user()->school_id;

        return view('certificates.index', ['templates' => CertificateTemplate::where(['school_id' => $schoolId, 'active' => true])->get(), 'students' => Student::where('school_id', $schoolId)->orderBy('first_name')->get(), 'certificates' => IssuedCertificate::where('school_id', $schoolId)->with('student')->latest()->paginate(20)]);
    }

    public function issue(Request $request): RedirectResponse
    {
        $data = $request->validate(['template_id' => ['required', Rule::exists('certificate_templates', 'id')], 'student_id' => ['required', Rule::exists('students', 'id')], 'issued_on' => ['required', 'date', 'before_or_equal:today']]);
        $template = CertificateTemplate::where('school_id', $request->user()->school_id)->findOrFail($data['template_id']);
        $student = Student::where('school_id', $request->user()->school_id)->findOrFail($data['student_id']);
        $variables = ['student_name' => $student->full_name, 'admission_no' => $student->admission_no, 'date' => $data['issued_on'], 'school_name' => $request->user()->school->name];
        $body = $template->body;
        foreach ($variables as $key => $value) {
            $body = str_replace('{{'.$key.'}}', $value, $body);
        }$certificate = IssuedCertificate::create(['public_id' => (string) Str::ulid(), 'school_id' => $student->school_id, 'certificate_template_id' => $template->id, 'student_id' => $student->id, 'serial_no' => 'CERT-'.now()->format('Y').'-'.str_pad((string) (IssuedCertificate::count() + 1), 6, '0', STR_PAD_LEFT), 'verification_code' => strtoupper(Str::random(16)), 'title' => $template->title, 'rendered_body' => $body, 'metadata' => $variables, 'issued_on' => $data['issued_on'], 'issued_by' => $request->user()->id]);

        return redirect()->route('certificates.show', $certificate)->with('success', 'Certificate issued.');
    }

    public function show(Request $request, IssuedCertificate $certificate): View
    {
        abort_unless($certificate->school_id === $request->user()->school_id, 403);

        return view('certificates.show', ['certificate' => $certificate->load(['student', 'school'])]);
    }

    public function verify(string $code): View
    {
        $certificate = IssuedCertificate::where('verification_code', strtoupper($code))->with(['student', 'school'])->first();

        return view('certificates.verify', compact('certificate'));
    }

    public function revoke(Request $request, IssuedCertificate $certificate): RedirectResponse
    {
        abort_unless($certificate->school_id === $request->user()->school_id, 403);
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        abort_if($certificate->revoked_at, 409);
        $certificate->update(['revoked_at' => now(), 'revocation_reason' => $data['reason']]);

        return back()->with('success','Certificate revoked.');
    }
}
