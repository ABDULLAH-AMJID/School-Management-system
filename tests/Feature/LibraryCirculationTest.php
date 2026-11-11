<?php

namespace Tests\Feature;

use App\Models\LibraryCopy;
use App\Models\LibraryLoan;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryCirculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_available_copy_can_be_issued_to_student(): void
    {
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();
        $copy = LibraryCopy::firstOrFail();
        $student = Student::firstOrFail();

        $this->actingAs($principal)->post(route('library.loans.issue'), [
            'copy_id' => $copy->id, 'student_id' => $student->id,
            'due_on' => today()->addDays(14)->toDateString(),
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame('issued', $copy->fresh()->status);
        $this->assertDatabaseHas('library_loans', ['library_copy_id' => $copy->id, 'student_id' => $student->id, 'status' => 'issued']);
    }

    public function test_return_releases_copy_and_calculates_overdue_fine(): void
    {
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();
        $copy = LibraryCopy::firstOrFail();
        $student = Student::firstOrFail();
        $copy->update(['status' => 'issued']);
        $loan = LibraryLoan::create(['school_id' => $student->school_id, 'library_copy_id' => $copy->id, 'student_id' => $student->id, 'issued_on' => today()->subDays(10), 'due_on' => today()->subDays(3), 'status' => 'issued', 'issued_by' => $principal->id]);

        $this->actingAs($principal)->post(route('library.loans.return', $loan))->assertRedirect();

        $this->assertSame('returned', $loan->fresh()->status);
        $this->assertSame(15000, $loan->fresh()->fine_amount);
        $this->assertSame('available', $copy->fresh()->status);
    }
}
