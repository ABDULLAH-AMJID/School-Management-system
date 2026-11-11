<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('code');
            $t->string('name');
            $t->string('type');
            $t->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();
            $t->boolean('active')->default(true);
            $t->timestamps();
            $t->unique(['school_id', 'code']);
        });
        Schema::create('journal_entries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('number')->unique();
            $t->date('entry_date');
            $t->string('reference_type')->nullable();
            $t->string('reference_id')->nullable();
            $t->text('description');
            $t->string('status')->default('posted');
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('reversal_of_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $t->timestamp('posted_at')->nullable();
            $t->timestamps();
        });
        Schema::create('journal_lines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('journal_entry_id')->constrained()->cascadeOnDelete();
            $t->foreignId('account_id')->constrained()->restrictOnDelete();
            $t->bigInteger('debit')->default(0);
            $t->bigInteger('credit')->default(0);
            $t->string('memo')->nullable();
            $t->timestamps();
        });
        Schema::create('fee_invoice_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('fee_invoice_id')->constrained()->cascadeOnDelete();
            $t->string('description');
            $t->bigInteger('quantity')->default(1);
            $t->bigInteger('unit_amount');
            $t->bigInteger('total');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_invoice_items');
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('accounts');
    }
};
