<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_profiles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('employee_no');
            $t->string('designation');
            $t->string('department');
            $t->date('joined_on');
            $t->string('employment_type')->default('permanent');
            $t->bigInteger('basic_salary')->default(0);
            $t->string('bank_account')->nullable();
            $t->string('status')->default('active');
            $t->timestamps();
            $t->unique(['school_id', 'employee_no']);
            $t->unique('user_id');
        });
        Schema::create('leave_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('staff_profile_id')->constrained()->cascadeOnDelete();
            $t->string('type');
            $t->date('starts_on');
            $t->date('ends_on');
            $t->decimal('days', 5, 1);
            $t->text('reason');
            $t->string('status')->default('pending');
            $t->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('reviewed_at')->nullable();
            $t->text('review_note')->nullable();
            $t->timestamps();
        });
        Schema::create('payroll_runs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('period');
            $t->date('period_start');
            $t->date('period_end');
            $t->string('status')->default('draft');
            $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('approved_at')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'period']);
        });
        Schema::create('payslips', function (Blueprint $t) {
            $t->id();
            $t->foreignId('payroll_run_id')->constrained()->cascadeOnDelete();
            $t->foreignId('staff_profile_id')->constrained()->restrictOnDelete();
            $t->bigInteger('basic_salary');
            $t->bigInteger('allowances')->default(0);
            $t->bigInteger('deductions')->default(0);
            $t->bigInteger('net_salary');
            $t->string('status')->default('draft');
            $t->timestamp('paid_at')->nullable();
            $t->string('payment_reference')->nullable();
            $t->timestamps();
            $t->unique(['payroll_run_id', 'staff_profile_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslips');
        Schema::dropIfExists('payroll_runs');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('staff_profiles');
    }
};
