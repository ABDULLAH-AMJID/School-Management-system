<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_reconciliations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
            $t->date('statement_date');
            $t->bigInteger('statement_balance');
            $t->bigInteger('ledger_balance');
            $t->bigInteger('difference');
            $t->string('status')->default('draft');
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
            $t->unique(['account_id', 'statement_date']);
        });
        Schema::create('bank_reconciliation_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('bank_reconciliation_id')->constrained()->cascadeOnDelete();
            $t->date('transaction_date');
            $t->string('reference')->nullable();
            $t->text('description');
            $t->bigInteger('amount');
            $t->string('type');
            $t->boolean('cleared')->default(false);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_reconciliation_items');
        Schema::dropIfExists('bank_reconciliations');
    }
};
