<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_concessions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('student_id')->constrained()->restrictOnDelete();
            $t->foreignId('fee_invoice_id')->constrained()->cascadeOnDelete();
            $t->string('type');
            $t->bigInteger('amount');
            $t->text('reason');
            $t->string('status')->default('pending');
            $t->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $t->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('reviewed_at')->nullable();
            $t->text('review_note')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_concessions');
    }
};
