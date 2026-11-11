<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('title');
            $t->text('body');
            $t->string('audience')->default('all_staff');
            $t->date('starts_on');
            $t->date('ends_on');
            $t->boolean('pinned')->default(false);
            $t->string('status')->default('published');
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
        Schema::create('school_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('title');
            $t->text('description')->nullable();
            $t->dateTime('starts_at');
            $t->dateTime('ends_at');
            $t->string('type')->default('school');
            $t->string('audience')->default('all');
            $t->string('location')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
        Schema::create('complaints', function (Blueprint $t) {
            $t->id();
            $t->ulid('public_id')->unique();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('reference_no')->unique();
            $t->string('source');
            $t->string('complainant_name');
            $t->string('complainant_phone')->nullable();
            $t->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $t->string('category');
            $t->string('priority')->default('normal');
            $t->string('subject');
            $t->text('description');
            $t->string('status')->default('open');
            $t->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $t->text('resolution')->nullable();
            $t->timestamp('resolved_at')->nullable();
            $t->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['school_id', 'status', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
        Schema::dropIfExists('school_events');
        Schema::dropIfExists('notices');
    }
};
