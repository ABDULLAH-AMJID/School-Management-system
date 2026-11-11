<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_structures', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $t->foreignId('grade_level_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->boolean('active')->default(true);
            $t->timestamps();
            $t->unique(['academic_year_id', 'grade_level_id', 'name']);
        });
        Schema::create('fee_installments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('fee_structure_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->date('issue_date');
            $t->date('due_date');
            $t->bigInteger('amount');
            $t->bigInteger('fine_amount')->default(0);
            $t->unsignedSmallInteger('fine_grace_days')->default(0);
            $t->timestamps();
        });
        Schema::create('fee_generation_runs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('fee_installment_id')->constrained()->cascadeOnDelete();
            $t->foreignId('generated_by')->constrained('users')->restrictOnDelete();
            $t->unsignedInteger('invoice_count');
            $t->bigInteger('total_amount');
            $t->timestamp('generated_at');
            $t->timestamps();
            $t->unique('fee_installment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_generation_runs');
        Schema::dropIfExists('fee_installments');
        Schema::dropIfExists('fee_structures');
    }
};
