<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\School;
use App\Models\Section;
use App\Models\TeachingAssignment;
use App\Models\TimetablePeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CrossSchoolIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function foreignSchool(): School
    {
        return School::create(['public_id' => (string) Str::ulid(), 'name' => 'Foreign School', 'code' => 'FOREIGN', 'board' => 'Punjab Boards', 'timezone' => 'Asia/Karachi', 'currency' => 'PKR']);
    }

    public function test_promotion_rejects_foreign_destination_section(): void
    {
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();
        $localYear = AcademicYear::where('school_id', $principal->school_id)->firstOrFail();
        $toYear = AcademicYear::create(['school_id' => $principal->school_id, 'name' => 'Next Isolation Year', 'starts_on' => '2027-04-01', 'ends_on' => '2028-03-31', 'status' => 'draft', 'is_current' => false]);
        $localSection = Section::whereHas('gradeLevel', fn ($q) => $q->where('school_id', $principal->school_id))->firstOrFail();
        $foreign = $this->foreignSchool();
        $grade = GradeLevel::create(['school_id' => $foreign->id, 'name' => 'Foreign Grade', 'code' => 'FG', 'sequence' => 1]);
        $foreignSection = Section::create(['grade_level_id' => $grade->id, 'name' => 'A', 'capacity' => 30]);
        $this->actingAs($principal)->post(route('promotions.create'), ['from_year_id' => $localYear->id, 'to_year_id' => $toYear->id, 'from_section_id' => $localSection->id, 'to_section_id' => $foreignSection->id])->assertForbidden();
        $this->assertDatabaseCount('promotion_runs', 0);
    }

    public function test_timetable_rejects_foreign_period(): void
    {
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();
        $assignment = TeachingAssignment::firstOrFail();
        $foreign = $this->foreignSchool();
        $period = TimetablePeriod::create(['school_id' => $foreign->id, 'name' => 'Foreign Period', 'starts_at' => '08:00', 'ends_at' => '08:40', 'sequence' => 1, 'is_break' => false]);
        $this->actingAs($principal)->post(route('timetable.store'), ['weekday' => 6, 'period_id' => $period->id, 'section_id' => $assignment->section_id, 'teaching_assignment_id' => $assignment->id])->assertForbidden();
    }

    public function test_complaint_rejects_foreign_assignee(): void
    {
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();
        $foreign = $this->foreignSchool();
        $foreignUser = User::create(['school_id' => $foreign->id, 'public_id' => (string) Str::ulid(), 'name' => 'Foreign Staff', 'email' => 'foreign@example.test', 'status' => 'active', 'password' => 'password']);
        $this->actingAs($principal)->post(route('engagement.complaints.store'), ['source' => 'parent', 'complainant_name' => 'Parent', 'category' => 'General', 'priority' => 'normal', 'subject' => 'Isolation test', 'description' => 'Must not cross school boundary', 'assigned_to' => $foreignUser->id])->assertForbidden();
        $this->assertDatabaseCount('complaints',0);
    }
}
