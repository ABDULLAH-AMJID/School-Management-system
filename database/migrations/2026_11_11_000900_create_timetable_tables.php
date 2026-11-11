<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('building')->nullable();
            $t->unsignedSmallInteger('capacity')->default(40);
            $t->string('type')->default('classroom');
            $t->timestamps();
            $t->unique(['school_id', 'name']);
        });
        Schema::create('teaching_assignments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $t->foreignId('section_id')->constrained()->cascadeOnDelete();
            $t->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $t->foreignId('teacher_id')->constrained('users')->restrictOnDelete();
            $t->boolean('class_teacher')->default(false);
            $t->timestamps();
            $t->unique(['academic_year_id', 'section_id', 'subject_id']);
        });
        Schema::create('timetable_periods', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->time('starts_at');
            $t->time('ends_at');
            $t->unsignedTinyInteger('sequence');
            $t->boolean('is_break')->default(false);
            $t->timestamps();
            $t->unique(['school_id', 'sequence']);
        });
        Schema::create('timetable_entries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $t->unsignedTinyInteger('weekday');
            $t->foreignId('period_id')->constrained('timetable_periods')->cascadeOnDelete();
            $t->foreignId('section_id')->constrained()->cascadeOnDelete();
            $t->foreignId('teaching_assignment_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamps();
            $t->unique(['academic_year_id', 'weekday', 'period_id', 'section_id'], 'section_slot_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_entries');
        Schema::dropIfExists('timetable_periods');
        Schema::dropIfExists('teaching_assignments');
        Schema::dropIfExists('rooms');
    }
};
