<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Guardian;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $query = Student::query()->where('school_id', $request->user()->school_id);
        if ($search = trim((string) $request->query('search'))) {
            $query->where(fn ($q) => $q->where('admission_no', 'like', "%{$search}%")->orWhere('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"));
        }

        return view('students.index', ['students' => $query->latest()->paginate(15)->withQueryString(), 'search' => $search ?? '']);
    }

    public function create(): View
    {
        return view('students.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['admission_no' => ['required', 'string', 'max:30', Rule::unique('students')->where('school_id', $request->user()->school_id)], 'first_name' => ['required', 'string', 'max:80'], 'last_name' => ['nullable', 'string', 'max:80'], 'gender' => ['required', Rule::in(['male', 'female', 'other'])], 'date_of_birth' => ['nullable', 'date', 'before:today'], 'admitted_on' => ['required', 'date'], 'status' => ['required', Rule::in(['active', 'inactive'])]]);
        $data += ['school_id' => $request->user()->school_id, 'public_id' => (string) Str::ulid()];
        $student = Student::create($data);

        return redirect()->route('students.index')->with('success', "{$student->full_name} was admitted successfully.");
    }

    public function show(Request $request, Student $student): View
    {
        $this->authorizeSchool($request, $student);
        $student->load(['guardians', 'school']);
        $enrollment = Enrollment::with(['section.gradeLevel'])->where('student_id', $student->id)->latest('id')->first();

        return view('students.show', compact('student', 'enrollment'));
    }

    public function edit(Request $request, Student $student): View
    {
        $this->authorizeSchool($request, $student);

        return view('students.edit', compact('student'));
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $this->authorizeSchool($request, $student);
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'], 'last_name' => ['nullable', 'string', 'max:80'],
            'date_of_birth' => ['nullable', 'date', 'before:today'], 'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'cnic_b_form' => ['nullable', 'string', 'max:25'], 'blood_group' => ['nullable', 'string', 'max:5'],
            'medical_notes' => ['nullable', 'string', 'max:2000'], 'previous_school' => ['nullable', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:1000'],
        ]);
        $student->update($data);

        return redirect()->route('students.show', $student)->with('success', 'Student profile updated.');
    }

    public function addGuardian(Request $request, Student $student): RedirectResponse
    {
        $this->authorizeSchool($request, $student);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'], 'relationship' => ['required', 'string', 'max:50'],
            'phone' => ['required', 'regex:/^\\+[1-9]\\d{7,14}$/'], 'whatsapp_phone' => ['nullable', 'regex:/^\\+[1-9]\\d{7,14}$/'],
            'email' => ['nullable', 'email', 'max:150'], 'occupation' => ['nullable', 'string', 'max:100'],
            'primary_contact' => ['nullable', 'boolean'], 'fee_responsible' => ['nullable', 'boolean'],
        ]);
        $guardian = Guardian::firstOrCreate(['school_id' => $student->school_id, 'phone' => $data['phone']], [
            'public_id' => (string) Str::ulid(), 'name' => $data['name'], 'relationship' => $data['relationship'],
            'whatsapp_phone' => $data['whatsapp_phone'] ?? $data['phone'], 'email' => $data['email'] ?? null,
            'occupation' => $data['occupation'] ?? null, 'whatsapp_opt_in' => true,
        ]);
        if ($request->boolean('primary_contact')) {
            $student->guardians()->updateExistingPivot($student->guardians()->pluck('guardians.id'), ['primary_contact' => false]);
        }
        $student->guardians()->syncWithoutDetaching([$guardian->id => ['primary_contact' => $request->boolean('primary_contact'), 'fee_responsible' => $request->boolean('fee_responsible')]]);

        return back()->with('success', 'Guardian linked successfully.');
    }

    public function withdraw(Request $request, Student $student): RedirectResponse
    {
        $this->authorizeSchool($request, $student);
        $data = $request->validate(['withdrawn_on' => ['required', 'date', 'after_or_equal:'.$student->admitted_on->toDateString(), 'before_or_equal:today'], 'withdrawal_reason' => ['required', 'string', 'max:500']]);
        DB::transaction(function () use ($student, $data) {
            $locked = Student::lockForUpdate()->findOrFail($student->id);
            abort_unless($locked->status === 'active', 409);
            $locked->update($data + ['status' => 'withdrawn']);
            Enrollment::where('student_id', $locked->id)->where('status', 'active')->update(['status' => 'withdrawn']);
        });

        return redirect()->route('students.show', $student)->with('success', 'Student withdrawn and active enrollment closed.');
    }

    private function authorizeSchool(Request $request, Student $student): void
    {
        abort_unless($student->school_id === $request->user()->school_id, 403);
    }
}
