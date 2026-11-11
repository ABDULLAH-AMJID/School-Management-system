<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_books', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('isbn')->nullable();
            $t->string('title');
            $t->string('author');
            $t->string('publisher')->nullable();
            $t->string('category');
            $t->unsignedSmallInteger('publication_year')->nullable();
            $t->string('shelf')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'isbn']);
        });
        Schema::create('library_copies', function (Blueprint $t) {
            $t->id();
            $t->foreignId('library_book_id')->constrained()->cascadeOnDelete();
            $t->string('accession_no')->unique();
            $t->string('barcode')->unique();
            $t->string('condition')->default('good');
            $t->string('status')->default('available');
            $t->date('acquired_on')->nullable();
            $t->bigInteger('cost')->default(0);
            $t->timestamps();
        });
        Schema::create('library_loans', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('library_copy_id')->constrained()->restrictOnDelete();
            $t->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('staff_profile_id')->nullable()->constrained()->nullOnDelete();
            $t->date('issued_on');
            $t->date('due_on');
            $t->date('returned_on')->nullable();
            $t->bigInteger('fine_amount')->default(0);
            $t->string('status')->default('issued');
            $t->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('returned_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['school_id', 'status', 'due_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_loans');
        Schema::dropIfExists('library_copies');
        Schema::dropIfExists('library_books');
    }
};
