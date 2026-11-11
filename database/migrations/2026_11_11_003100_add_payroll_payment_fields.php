<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_runs', function (Blueprint $t) {
            $t->foreignId('payment_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $t->foreignId('payment_journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $t->string('payment_reference')->nullable();
            $t->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('paid_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('payroll_runs', function (Blueprint $t) {
            $t->dropConstrainedForeignId('payment_account_id');
            $t->dropConstrainedForeignId('payment_journal_entry_id');
            $t->dropColumn('payment_reference');
            $t->dropConstrainedForeignId('paid_by');
            $t->dropColumn('paid_at');
        });
    }
};
