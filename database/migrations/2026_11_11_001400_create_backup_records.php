<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_records', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->nullable()->constrained()->nullOnDelete();
            $t->string('filename');
            $t->string('disk')->default('local');
            $t->unsignedBigInteger('size')->default(0);
            $t->string('checksum', 64);
            $t->string('status')->default('completed');
            $t->timestamp('started_at');
            $t->timestamp('completed_at')->nullable();
            $t->text('error')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_records');
    }
};
