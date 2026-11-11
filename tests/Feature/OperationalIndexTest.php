<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OperationalIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_high_volume_operational_indexes_exist(): void
    {
        $expected = ['students' => ['students_school_status_idx', 'students_school_name_idx'], 'fee_invoices' => ['fee_school_status_due_idx', 'fee_student_status_idx'], 'outbound_messages' => ['messages_dispatch_idx'], 'audit_logs' => ['audit_school_date_idx'], 'journal_entries' => ['journal_school_date_idx']];
        foreach ($expected as $table => $names) {
            $indexes = collect(DB::select("PRAGMA index_list('$table')"))->pluck('name');
            foreach ($names as $name) {
                $this->assertTrue($indexes->contains($name), "Missing $name on $table");
            }
        }
    }

    public function test_work_queue_indexes_exist(): void
    {
        $expected = ['complaints' => 'complaints_workqueue_idx', 'leave_requests' => 'leave_status_date_idx', 'timetable_entries' => 'timetable_lookup_idx', 'attendance_sessions' => 'attendance_school_date_idx'];
        foreach ($expected as $table => $name) {
            $this->assertTrue(collect(DB::select("PRAGMA index_list('$table')"))->pluck('name')->contains($name));
        }
    }
}
