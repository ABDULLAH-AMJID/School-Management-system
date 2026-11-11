<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_runs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('from_academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $t->foreignId('to_academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $t->foreignId('from_section_id')->constrained('sections')->restrictOnDelete();
            $t->foreignId('to_section_id')->nullable()->constrained('sections')->restrictOnDelete();
            $t->string('status')->default('draft');
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
            $t->unique(['from_academic_year_id', 'to_academic_year_id', 'from_section_id']);
        });
        Schema::create('promotion_decisions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('promotion_run_id')->constrained()->cascadeOnDelete();
            $t->foreignId('student_id')->constrained()->restrictOnDelete();
            $t->string('decision');
            $t->foreignId('destination_section_id')->nullable()->constrained('sections')->restrictOnDelete();
            $t->string('new_roll_no')->nullable();
            $t->text('remarks')->nullable();
            $t->timestamps();
            $t->unique(['promotion_run_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_decisions');
        Schema::dropIfExists('promotion_runs');
    }
};
