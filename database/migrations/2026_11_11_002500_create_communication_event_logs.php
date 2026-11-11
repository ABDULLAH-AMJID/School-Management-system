<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_event_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('event');
            $t->string('source_type');
            $t->string('source_id');
            $t->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('outbound_message_id')->nullable()->constrained()->nullOnDelete();
            $t->string('status');
            $t->text('detail')->nullable();
            $t->timestamps();
            $t->unique(['event', 'source_type', 'source_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_event_logs');
    }
};
