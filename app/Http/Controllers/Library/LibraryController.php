<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;
use App\Models\LibraryBook;
use App\Models\LibraryCopy;
use App\Models\LibraryLoan;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class LibraryController extends Controller
{
    public function index(Request $request): View
    {
        $schoolId = $request->user()->school_id;

        return view('library.index', ['books' => LibraryBook::where('school_id', $schoolId)->withCount(['copies', 'copies as available_copies_count' => fn ($q) => $q->where('status', 'available')])->get(), 'loans' => LibraryLoan::where('school_id', $schoolId)->with(['copy.book', 'student'])->latest()->limit(25)->get(), 'students' => Student::where(['school_id' => $schoolId, 'status' => 'active'])->orderBy('first_name')->get(), 'copies' => LibraryCopy::whereHas('book', fn ($q) => $q->where('school_id', $schoolId))->where('status', 'available')->with('book')->get()]);
    }

    public function storeBook(Request $request): RedirectResponse
    {
        $data = $request->validate(['title' => ['required', 'string', 'max:200'], 'author' => ['required', 'string', 'max:150'], 'isbn' => ['nullable', 'string', 'max:30'], 'category' => ['required', 'string', 'max:80'], 'accession_no' => ['required', 'unique:library_copies'], 'barcode' => ['required', 'unique:library_copies']]);
        DB::transaction(function () use ($data, $request) {
            $book = LibraryBook::create(['school_id' => $request->user()->school_id, 'title' => $data['title'], 'author' => $data['author'], 'isbn' => $data['isbn'] ?? null, 'category' => $data['category']]);
            LibraryCopy::create(['library_book_id' => $book->id, 'accession_no' => $data['accession_no'], 'barcode' => $data['barcode'], 'status' => 'available', 'condition' => 'good', 'acquired_on' => today()]);
        });

        return back()->with('success', 'Book and first copy added.');
    }

    public function issue(Request $request): RedirectResponse
    {
        $data = $request->validate(['copy_id' => ['required', 'exists:library_copies,id'], 'student_id' => ['required', 'exists:students,id'], 'due_on' => ['required', 'date', 'after:today']]);
        $copy = LibraryCopy::with('book')->findOrFail($data['copy_id']);
        $student = Student::findOrFail($data['student_id']);
        abort_unless($copy->book->school_id === $request->user()->school_id && $student->school_id === $request->user()->school_id, 403);
        DB::transaction(function () use ($copy, $student, $data, $request) {
            $copy = LibraryCopy::lockForUpdate()->find($copy->id);
            if ($copy->status !== 'available') {
                throw ValidationException::withMessages(['copy_id' => 'This copy is no longer available.']);
            }LibraryLoan::create(['school_id' => $request->user()->school_id, 'library_copy_id' => $copy->id, 'student_id' => $student->id, 'issued_on' => today(), 'due_on' => $data['due_on'], 'status' => 'issued', 'issued_by' => $request->user()->id]);
            $copy->update(['status' => 'issued']);
        });

        return back()->with('success', 'Book issued successfully.');
    }

    public function returnBook(Request $request, LibraryLoan $loan): RedirectResponse
    {
        abort_unless($loan->school_id === $request->user()->school_id, 403);
        abort_unless($loan->status === 'issued', 409);
        DB::transaction(function () use ($loan, $request) {
            $days = max(0, $loan->due_on->diffInDays(today(), false));
            $loan->update(['returned_on' => today(), 'fine_amount' => $days * 5000, 'status' => 'returned', 'returned_by' => $request->user()->id]);
            $loan->copy()->update(['status' => 'available']);
        });

        return back()->with('success', 'Book returned. Fine: PKR '.number_format($loan->fresh()->fine_amount / 100));
    }
}
