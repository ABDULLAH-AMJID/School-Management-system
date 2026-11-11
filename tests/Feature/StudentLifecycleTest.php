<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_profile_can_be_updated(): void
    {
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();
        $student = Student::firstOrFail();

        $this->actingAs($principal)->put(route('students.update', $student), [
            'first_name' => 'Updated', 'last_name' => 'Student', 'gender' => 'female',
            'date_of_birth' => '2012-05-10', 'cnic_b_form' => '35202-1234567-8',
            'blood_group' => 'A+', 'address' => 'Sahiwal, Punjab',
        ])->assertRedirect(route('students.show', $student));

        $this->assertDatabaseHas('students', ['id' => $student->id, 'first_name' => 'Updated', 'blood_group' => 'A+']);
    }

    public function test_guardian_can_be_linked_as_primary_fee_contact(): void
    {
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();
        $student = Student::firstOrFail();

        $this->actingAs($principal)->post(route('students.guardians.store', $student), [
            'name' => 'Parent Khan', 'relationship' => 'Father', 'phone' => '+923001112233',
            'primary_contact' => 1, 'fee_responsible' => 1,
        ])->assertRedirect()->assertSessionHas('success');

        $guardian = Guardian::firstOrFail();
        $this->assertTrue((bool) $student->guardians()->find($guardian->id)->pivot->primary_contact);
        $this->assertTrue((bool) $student->guardians()->find($guardian->id)->pivot->fee_responsible);
    }

    public function test_withdrawal_closes_active_enrollment(): void
    {
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();
        $student = Student::firstOrFail();

        $this->actingAs($principal)->post(route('students.withdraw', $student), [
            'withdrawn_on' => today()->toDateString(), 'withdrawal_reason' => 'Family relocation',
        ])->assertRedirect(route('students.show', $student));

        $this->assertSame('withdrawn', $student->fresh()->status);
        $this->assertSame(0, Enrollment::where('student_id', $student->id)->where('status', 'active')->count());
    }
}
