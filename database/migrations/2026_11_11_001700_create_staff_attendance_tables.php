<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_devices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('type');
            $t->string('serial_no');
            $t->string('location')->nullable();
            $t->string('api_key_hash')->nullable();
            $t->timestamp('last_seen_at')->nullable();
            $t->string('status')->default('inactive');
            $t->timestamps();
            $t->unique(['school_id', 'serial_no']);
        });
        Schema::create('staff_attendances', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('staff_profile_id')->constrained()->cascadeOnDelete();
            $t->date('attendance_date');
            $t->string('status')->default('present');
            $t->time('check_in')->nullable();
            $t->time('check_out')->nullable();
            $t->unsignedSmallInteger('late_minutes')->default(0);
            $t->string('source')->default('manual');
            $t->foreignId('attendance_device_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->text('remarks')->nullable();
            $t->timestamps();
            $t->unique(['staff_profile_id', 'attendance_date']);
        });
        Schema::create('attendance_device_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('attendance_device_id')->constrained()->cascadeOnDelete();
            $t->string('event_uid')->unique();
            $t->string('employee_no');
            $t->timestamp('occurred_at');
            $t->string('direction')->nullable();
            $t->json('payload')->nullable();
            $t->timestamp('processed_at')->nullable();
            $t->string('processing_status')->default('pending');
            $t->text('error')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_device_events');
        Schema::dropIfExists('staff_attendances');
        Schema::dropIfExists('attendance_devices');
    }
};
