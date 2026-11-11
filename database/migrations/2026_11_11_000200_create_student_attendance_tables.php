<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guardians', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->ulid('public_id')->unique();
            $t->string('name');
            $t->string('relationship');
            $t->string('phone');
            $t->string('whatsapp_phone')->nullable();
            $t->string('email')->nullable();
            $t->string('occupation')->nullable();
            $t->boolean('whatsapp_opt_in')->default(true);
            $t->timestamps();
        });
        Schema::create('students', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->ulid('public_id')->unique();
            $t->string('admission_no');
            $t->string('first_name');
            $t->string('last_name')->nullable();
            $t->date('date_of_birth')->nullable();
            $t->string('gender', 20);
            $t->string('status')->default('active');
            $t->date('admitted_on');
            $t->string('photo_path')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->unique(['school_id', 'admission_no']);
        });
        Schema::create('guardian_student', function (Blueprint $t) {
            $t->foreignId('guardian_id')->constrained()->cascadeOnDelete();
            $t->foreignId('student_id')->constrained()->cascadeOnDelete();
            $t->boolean('primary_contact')->default(false);
            $t->boolean('fee_responsible')->default(false);
            $t->primary(['guardian_id', 'student_id']);
        });
        Schema::create('enrollments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('student_id')->constrained()->cascadeOnDelete();
            $t->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $t->foreignId('section_id')->constrained()->restrictOnDelete();
            $t->string('roll_no')->nullable();
            $t->string('status')->default('active');
            $t->timestamps();
            $t->unique(['student_id', 'academic_year_id']);
        });
        Schema::create('attendance_sessions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('section_id')->nullable()->constrained()->nullOnDelete();
            $t->date('attendance_date');
            $t->string('period')->default('daily');
            $t->foreignId('taken_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('submitted_at')->nullable();
            $t->timestamps();
            $t->unique(['section_id', 'attendance_date', 'period']);
        });
        Schema::create('student_attendances', function (Blueprint $t) {
            $t->id();
            $t->foreignId('attendance_session_id')->constrained()->cascadeOnDelete();
            $t->foreignId('student_id')->constrained()->cascadeOnDelete();
            $t->string('status', 20);
            $t->time('arrival_time')->nullable();
            $t->string('source')->default('manual');
            $t->text('remarks')->nullable();
            $t->timestamps();
            $t->unique(['attendance_session_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_attendances');
        Schema::dropIfExists('attendance_sessions');
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('guardian_student');
        Schema::dropIfExists('students');
        Schema::dropIfExists('guardians');
    }
};
