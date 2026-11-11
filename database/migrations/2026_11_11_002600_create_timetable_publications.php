<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timetable_publications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $t->foreignId('section_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('version');
            $t->string('snapshot_hash', 64);
            $t->foreignId('published_by')->constrained('users')->restrictOnDelete();
            $t->timestamp('published_at');
            $t->timestamps();
            $t->unique(['academic_year_id', 'section_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_publications');
    }
};
