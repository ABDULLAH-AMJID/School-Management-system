<?php

namespace Tests\Feature;

use App\Models\TeachingAssignment;
use App\Models\TimetableEntry;
use App\Models\TimetablePeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimetableTest extends TestCase
{
    use RefreshDatabase;

    public function test_principal_can_add_valid_timetable_entry(): void
    {
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();
        $assignment = TeachingAssignment::firstOrFail();
        $period = TimetablePeriod::where('is_break', false)->skip(1)->firstOrFail();

        $this->actingAs($principal)->post(route('timetable.store'), [
            'weekday' => 2, 'period_id' => $period->id, 'section_id' => $assignment->section_id,
            'teaching_assignment_id' => $assignment->id,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('timetable_entries', ['weekday' => 2, 'period_id' => $period->id]);
    }

    public function test_teacher_double_booking_is_rejected(): void
    {
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();
        $existing = TimetableEntry::firstOrFail();

        $this->actingAs($principal)->post(route('timetable.store'), [
            'weekday' => $existing->weekday, 'period_id' => $existing->period_id,
            'section_id' => $existing->section_id, 'teaching_assignment_id' => $existing->teaching_assignment_id,
        ])->assertSessionHasErrors('teaching_assignment_id');

        $this->assertDatabaseCount('timetable_entries', 1);
    }
}
