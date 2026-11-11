<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_sessions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $t->foreignId('term_id')->nullable()->constrained()->nullOnDelete();
            $t->string('name');
            $t->string('type')->default('internal');
            $t->date('starts_on');
            $t->date('ends_on');
            $t->string('status')->default('draft');
            $t->timestamp('published_at')->nullable();
            $t->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
        Schema::create('assessment_components', function (Blueprint $t) {
            $t->id();
            $t->foreignId('exam_session_id')->constrained()->cascadeOnDelete();
            $t->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $t->foreignId('grade_level_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->unsignedSmallInteger('max_marks');
            $t->decimal('weight_percent', 5, 2)->default(100);
            $t->unsignedSmallInteger('pass_marks')->default(33);
            $t->timestamps();
            $t->unique(['exam_session_id', 'subject_id', 'grade_level_id', 'name'], 'assessment_component_unique');
        });
        Schema::create('student_marks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('assessment_component_id')->constrained()->cascadeOnDelete();
            $t->foreignId('student_id')->constrained()->cascadeOnDelete();
            $t->decimal('marks', 7, 2)->nullable();
            $t->string('status')->default('present');
            $t->text('remarks')->nullable();
            $t->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('entered_at')->nullable();
            $t->timestamps();
            $t->unique(['assessment_component_id', 'student_id']);
        });
        Schema::create('grade_scales', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->decimal('minimum_percent', 5, 2);
            $t->decimal('maximum_percent', 5, 2);
            $t->string('grade', 10);
            $t->decimal('grade_point', 4, 2)->nullable();
            $t->string('remark')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_scales');
        Schema::dropIfExists('student_marks');
        Schema::dropIfExists('assessment_components');
        Schema::dropIfExists('exam_sessions');
    }
};
