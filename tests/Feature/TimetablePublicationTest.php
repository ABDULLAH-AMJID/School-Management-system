<?php

namespace Tests\Feature;

use App\Jobs\GenerateTimetableNotifications;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\TimetableEntry;
use App\Models\TimetablePublication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TimetablePublicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_timetable_publication_is_versioned_and_unchanged_snapshot_is_idempotent(): void
    {
        $this->seed();
        Queue::fake();
        $u = User::where('email', 'principal@school.test')->firstOrFail();
        $entry = TimetableEntry::firstOrFail();
        $section = Section::findOrFail($entry->section_id);
        $this->actingAs($u)->post(route('timetable.publish', $section))->assertRedirect();
        $p = TimetablePublication::firstOrFail();
        $this->assertSame(1, $p->version);
        Queue::assertPushed(GenerateTimetableNotifications::class);
        $this->actingAs($u)->post(route('timetable.publish', $section))->assertRedirect();
        $this->assertDatabaseCount('timetable_publications', 1);
    }

    public function test_empty_timetable_cannot_be_published(): void
    {
        $this->seed();
        $u = User::where('email', 'principal@school.test')->firstOrFail();
        $section = Section::create(['grade_level_id' => GradeLevel::firstOrFail()->id, 'name' => 'Empty Test Section', 'capacity' => 30]);
        $this->actingAs($u)->post(route('timetable.publish', $section))->assertStatus(422);
    }
}
