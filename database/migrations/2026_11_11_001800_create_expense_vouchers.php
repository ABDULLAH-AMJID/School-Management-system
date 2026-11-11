<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_vouchers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('number')->unique();
            $t->date('expense_date');
            $t->foreignId('expense_account_id')->constrained('accounts')->restrictOnDelete();
            $t->foreignId('payment_account_id')->constrained('accounts')->restrictOnDelete();
            $t->string('payee');
            $t->bigInteger('amount');
            $t->text('description');
            $t->string('payment_method');
            $t->string('reference')->nullable();
            $t->string('status')->default('pending');
            $t->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('approved_at')->nullable();
            $t->text('rejection_reason')->nullable();
            $t->foreignId('journal_entry_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamps();
            $t->index(['school_id', 'expense_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_vouchers');
    }
};
