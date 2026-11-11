<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_invoices', function (Blueprint $t) {
            $t->foreignId('fee_installment_id')->nullable()->after('student_id')->constrained()->nullOnDelete();
        });
        Schema::create('fee_fine_applications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('fee_invoice_id')->constrained()->cascadeOnDelete();
            $t->foreignId('fee_installment_id')->constrained()->cascadeOnDelete();
            $t->bigInteger('amount');
            $t->date('applied_on');
            $t->string('status')->default('applied');
            $t->timestamps();
            $t->unique(['fee_invoice_id', 'fee_installment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_fine_applications');
        Schema::table('fee_invoices', fn (Blueprint $t) => $t->dropConstrainedForeignId('fee_installment_id'));
    }
};
