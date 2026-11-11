<?php

namespace App\Jobs;

use App\Models\Enrollment;
use App\Models\Section;
use App\Models\TimetablePublication;
use App\Models\User;
use App\Services\Messaging\TriggerStudentEventMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateTimetableNotifications implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $publicationId) {}

    public function handle(TriggerStudentEventMessage $messenger): void
    {
        $p = TimetablePublication::findOrFail($this->publicationId);
        $section = Section::with('gradeLevel')->findOrFail($p->section_id);
        $actor = User::findOrFail($p->published_by);
        Enrollment::with('student')->where(['academic_year_id' => $p->academic_year_id, 'section_id' => $p->section_id, 'status' => 'active'])->chunkById(200, function ($rows) use ($messenger, $p, $section, $actor) {
            foreach ($rows as $row) {
                $messenger->handle('timetable_published', 'timetable_publication', $p->id, $row->student, $actor, ['class_name' => $section->gradeLevel->name.' '.$section->name, 'timetable_version' => $p->version]);
            }
        });
    }
}
