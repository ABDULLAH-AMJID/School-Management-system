<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->index(['school_id', 'status'], 'users_school_status_idx'));
        Schema::table('students', function (Blueprint $t) {
            $t->index(['school_id', 'status'], 'students_school_status_idx');
            $t->index(['school_id', 'first_name', 'last_name'], 'students_school_name_idx');
        });
        Schema::table('enrollments', fn (Blueprint $t) => $t->index(['section_id', 'status'], 'enrollments_section_status_idx'));
        Schema::table('attendance_sessions', fn (Blueprint $t) => $t->index(['school_id', 'attendance_date'], 'attendance_school_date_idx'));
        Schema::table('student_attendances', fn (Blueprint $t) => $t->index(['student_id', 'status'], 'student_attendance_status_idx'));
        Schema::table('fee_invoices', function (Blueprint $t) {
            $t->index(['school_id', 'status', 'due_date'], 'fee_school_status_due_idx');
            $t->index(['student_id', 'status'], 'fee_student_status_idx');
        });
        Schema::table('payments', fn (Blueprint $t) => $t->index(['school_id', 'received_at'], 'payments_school_date_idx'));
        Schema::table('outbound_messages', fn (Blueprint $t) => $t->index(['school_id', 'status', 'scheduled_for'], 'messages_dispatch_idx'));
        Schema::table('audit_logs', fn (Blueprint $t) => $t->index(['school_id', 'created_at'], 'audit_school_date_idx'));
        Schema::table('journal_entries', fn (Blueprint $t) => $t->index(['school_id', 'status', 'entry_date'], 'journal_school_date_idx'));
        Schema::table('complaints', fn (Blueprint $t) => $t->index(['school_id', 'status', 'priority'], 'complaints_workqueue_idx'));
        Schema::table('leave_requests', fn (Blueprint $t) => $t->index(['status', 'starts_on'], 'leave_status_date_idx'));
        Schema::table('timetable_entries', fn (Blueprint $t) => $t->index(['academic_year_id', 'section_id', 'weekday'], 'timetable_lookup_idx'));
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropIndex('users_school_status_idx'));
        Schema::table('students', function (Blueprint $t) {
            $t->dropIndex('students_school_status_idx');
            $t->dropIndex('students_school_name_idx');
        });
        Schema::table('enrollments', fn (Blueprint $t) => $t->dropIndex('enrollments_section_status_idx'));
        Schema::table('attendance_sessions', fn (Blueprint $t) => $t->dropIndex('attendance_school_date_idx'));
        Schema::table('student_attendances', fn (Blueprint $t) => $t->dropIndex('student_attendance_status_idx'));
        Schema::table('fee_invoices', function (Blueprint $t) {
            $t->dropIndex('fee_school_status_due_idx');
            $t->dropIndex('fee_student_status_idx');
        });
        Schema::table('payments', fn (Blueprint $t) => $t->dropIndex('payments_school_date_idx'));
        Schema::table('outbound_messages', fn (Blueprint $t) => $t->dropIndex('messages_dispatch_idx'));
        Schema::table('audit_logs', fn (Blueprint $t) => $t->dropIndex('audit_school_date_idx'));
        Schema::table('journal_entries', fn (Blueprint $t) => $t->dropIndex('journal_school_date_idx'));
        Schema::table('complaints', fn (Blueprint $t) => $t->dropIndex('complaints_workqueue_idx'));
        Schema::table('leave_requests', fn (Blueprint $t) => $t->dropIndex('leave_status_date_idx'));
        Schema::table('timetable_entries',fn (Blueprint $t) => $t->dropIndex('timetable_lookup_idx'));
    }
};
