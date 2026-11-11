<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_preferences', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('recipient');
            $t->string('channel')->default('whatsapp');
            $t->string('event')->default('*');
            $t->boolean('opted_out')->default(false);
            $t->text('reason')->nullable();
            $t->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('consented_at')->nullable();
            $t->timestamp('opted_out_at')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'recipient', 'channel', 'event']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_preferences');
    }
};
