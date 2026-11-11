<?php

namespace Tests\Feature;

use App\Models\FeeInvoice;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_search_returns_students_but_not_finance_records(): void
    {
        $this->seed();
        $teacher = User::where('email', 'teacher@school.test')->firstOrFail();
        $student = Student::firstOrFail();
        $this->actingAs($teacher)->getJson(route('search', ['q' => $student->admission_no]))->assertOk()->assertJsonFragment(['type' => 'Student', 'title' => $student->full_name])->assertJsonMissing(['type' => 'Invoice']);
        $invoice = FeeInvoice::firstOrFail();
        $this->actingAs($teacher)->getJson(route('search', ['q' => $invoice->number]))->assertOk()->assertJsonCount(0, 'results');
    }

    public function test_principal_can_search_invoice_and_staff_records(): void
    {
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();
        $invoice = FeeInvoice::firstOrFail();
        $this->actingAs($principal)->getJson(route('search', ['q' => $invoice->number]))->assertOk()->assertJsonFragment(['type' => 'Invoice', 'title' => $invoice->number]);
        $teacher = User::where('email', 'teacher@school.test')->firstOrFail();
        $this->actingAs($principal)->getJson(route('search', ['q' => $teacher->email]))->assertOk()->assertJsonFragment(['type' => 'Staff', 'title' => $teacher->name]);
    }

    public function test_short_search_query_is_rejected(): void
    {
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();
        $this->actingAs($principal)->getJson(route('search', ['q' => 'a']))->assertUnprocessable()->assertJsonValidationErrors('q');
    }
}
