<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $t) {
            $t->string('cnic_b_form')->nullable();
            $t->string('blood_group', 5)->nullable();
            $t->text('medical_notes')->nullable();
            $t->string('previous_school')->nullable();
            $t->text('address')->nullable();
            $t->date('withdrawn_on')->nullable();
            $t->string('withdrawal_reason')->nullable();
        });
        Schema::create('student_documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('student_id')->constrained()->cascadeOnDelete();
            $t->string('type');
            $t->string('name');
            $t->string('path');
            $t->string('mime_type');
            $t->unsignedBigInteger('size');
            $t->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_documents');
        Schema::table('students', fn (Blueprint $t) => $t->dropColumn(['cnic_b_form', 'blood_group', 'medical_notes', 'previous_school', 'address', 'withdrawn_on', 'withdrawal_reason']));
    }
};
