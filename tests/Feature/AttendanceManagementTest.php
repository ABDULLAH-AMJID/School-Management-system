<?php

namespace Tests\Feature;

use App\Models\AttendanceSession;
use App\Models\Enrollment;
use App\Models\StudentAttendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_principal_can_submit_daily_attendance(): void
    {
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();
        $enrollments = Enrollment::all();
        $statuses = $enrollments->mapWithKeys(fn ($enrollment) => [$enrollment->student_id => 'present'])->all();
        $statuses[$enrollments->first()->student_id] = 'absent';

        $this->actingAs($principal)->post('/attendance', [
            'section_id' => $enrollments->first()->section_id,
            'date' => today()->toDateString(),
            'status' => $statuses,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseCount('attendance_sessions', 1);
        $this->assertDatabaseCount('student_attendances', $enrollments->count());
        $this->assertSame('absent', StudentAttendance::first()->status);
        $this->assertNotNull(AttendanceSession::first()->submitted_at);
    }

    public function test_future_attendance_is_rejected(): void
    {
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();
        $enrollment = Enrollment::firstOrFail();

        $this->actingAs($principal)->post('/attendance', [
            'section_id' => $enrollment->section_id,
            'date' => today()->addDay()->toDateString(),
            'status' => [$enrollment->student_id => 'present'],
        ])->assertSessionHasErrors('date');
    }
}
