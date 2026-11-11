<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_invoices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('student_id')->constrained()->restrictOnDelete();
            $t->string('number')->unique();
            $t->date('issue_date');
            $t->date('due_date');
            $t->bigInteger('subtotal');
            $t->bigInteger('discount')->default(0);
            $t->bigInteger('fine')->default(0);
            $t->bigInteger('total');
            $t->bigInteger('paid')->default(0);
            $t->string('status')->default('issued');
            $t->timestamps();
        });
        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('fee_invoice_id')->nullable()->constrained()->nullOnDelete();
            $t->string('receipt_no')->unique();
            $t->bigInteger('amount');
            $t->string('method');
            $t->string('reference')->nullable();
            $t->timestamp('received_at');
            $t->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('status')->default('posted');
            $t->timestamps();
        });
        Schema::create('message_templates', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('event');
            $t->string('name');
            $t->text('body');
            $t->string('mode')->default('approval');
            $t->time('quiet_from')->nullable();
            $t->time('quiet_until')->nullable();
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('outbound_messages', function (Blueprint $t) {
            $t->id();
            $t->ulid('public_id')->unique();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('provider')->default('whatsapp_web');
            $t->string('recipient');
            $t->string('event');
            $t->text('body');
            $t->string('status')->default('pending_approval');
            $t->timestamp('scheduled_for')->nullable();
            $t->timestamp('sent_at')->nullable();
            $t->string('provider_message_id')->nullable();
            $t->unsignedTinyInteger('attempts')->default(0);
            $t->text('last_error')->nullable();
            $t->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('event');
            $t->string('auditable_type')->nullable();
            $t->string('auditable_id')->nullable();
            $t->json('old_values')->nullable();
            $t->json('new_values')->nullable();
            $t->string('ip_address', 45)->nullable();
            $t->text('user_agent')->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['auditable_type', 'auditable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('outbound_messages');
        Schema::dropIfExists('message_templates');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('fee_invoices');
    }
};
