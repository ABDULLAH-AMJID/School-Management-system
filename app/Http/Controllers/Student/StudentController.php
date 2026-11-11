<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
}
