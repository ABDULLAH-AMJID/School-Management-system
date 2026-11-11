<?php

namespace Tests\Feature;

use App\Models\StaffAttendance;
use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_principal_can_submit_staff_attendance(): void
    {
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();
        $staff = StaffProfile::firstOrFail();

        $this->actingAs($principal)->post(route('staff-attendance.store'), [
            'date' => today()->toDateString(),
            'status' => [$staff->id => 'late'],
            'check_in' => [$staff->id => '08:15'],
            'check_out' => [$staff->id => '14:00'],
        ])->assertRedirect()->assertSessionHas('success');

        $record = StaffAttendance::firstOrFail();
        $this->assertSame('late', $record->status);
        $this->assertSame(15, $record->late_minutes);
        $this->assertSame('manual', $record->source);
    }

    public function test_future_staff_attendance_is_rejected(): void
    {
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();
        $staff = StaffProfile::firstOrFail();

        $this->actingAs($principal)->post(route('staff-attendance.store'), [
            'date' => today()->addDay()->toDateString(), 'status' => [$staff->id => 'present'],
        ])->assertSessionHasErrors('date');

        $this->assertDatabaseCount('staff_attendances', 0);
    }
}
