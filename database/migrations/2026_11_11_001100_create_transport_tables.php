<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('registration_no');
            $t->string('make_model');
            $t->unsignedSmallInteger('capacity');
            $t->string('driver_name');
            $t->string('driver_phone');
            $t->string('status')->default('active');
            $t->date('fitness_expires_on')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'registration_no']);
        });
        Schema::create('transport_routes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $t->string('name');
            $t->string('code');
            $t->time('departure_time')->nullable();
            $t->time('return_time')->nullable();
            $t->string('status')->default('active');
            $t->timestamps();
            $t->unique(['school_id', 'code']);
        });
        Schema::create('transport_stops', function (Blueprint $t) {
            $t->id();
            $t->foreignId('transport_route_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->unsignedSmallInteger('sequence');
            $t->time('pickup_time')->nullable();
            $t->time('dropoff_time')->nullable();
            $t->bigInteger('monthly_fee')->default(0);
            $t->timestamps();
            $t->unique(['transport_route_id', 'sequence']);
        });
        Schema::create('student_transport_assignments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('student_id')->constrained()->cascadeOnDelete();
            $t->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $t->foreignId('transport_route_id')->constrained()->restrictOnDelete();
            $t->foreignId('transport_stop_id')->constrained()->restrictOnDelete();
            $t->date('starts_on');
            $t->date('ends_on')->nullable();
            $t->string('status')->default('active');
            $t->timestamps();
            $t->unique(['student_id', 'academic_year_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_transport_assignments');
        Schema::dropIfExists('transport_stops');
        Schema::dropIfExists('transport_routes');
        Schema::dropIfExists('vehicles');
    }
};
