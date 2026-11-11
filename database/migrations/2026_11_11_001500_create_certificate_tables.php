<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_templates', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('type');
            $t->string('title');
            $t->text('body');
            $t->boolean('active')->default(true);
            $t->timestamps();
            $t->unique(['school_id', 'type']);
        });
        Schema::create('issued_certificates', function (Blueprint $t) {
            $t->id();
            $t->ulid('public_id')->unique();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('certificate_template_id')->constrained()->restrictOnDelete();
            $t->foreignId('student_id')->constrained()->restrictOnDelete();
            $t->string('serial_no')->unique();
            $t->string('verification_code', 40)->unique();
            $t->string('title');
            $t->text('rendered_body');
            $t->json('metadata')->nullable();
            $t->date('issued_on');
            $t->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('revoked_at')->nullable();
            $t->text('revocation_reason')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issued_certificates');
        Schema::dropIfExists('certificate_templates');
    }
};
