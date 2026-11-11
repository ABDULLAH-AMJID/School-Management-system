<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateway_transactions', function (Blueprint $t) {
            $t->id();
            $t->ulid('public_id')->unique();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('fee_invoice_id')->constrained()->restrictOnDelete();
            $t->string('provider');
            $t->string('idempotency_key')->unique();
            $t->string('provider_reference')->nullable()->unique();
            $t->bigInteger('amount');
            $t->string('currency', 3)->default('PKR');
            $t->string('status')->default('created');
            $t->string('checkout_url')->nullable();
            $t->json('request_payload')->nullable();
            $t->json('response_payload')->nullable();
            $t->text('failure_reason')->nullable();
            $t->timestamp('expires_at')->nullable();
            $t->timestamp('paid_at')->nullable();
            $t->timestamps();
            $t->index(['school_id', 'status']);
        });
        Schema::create('payment_webhook_events', function (Blueprint $t) {
            $t->id();
            $t->string('provider');
            $t->string('event_id');
            $t->string('signature_hash');
            $t->json('payload');
            $t->string('status')->default('received');
            $t->timestamp('processed_at')->nullable();
            $t->text('error')->nullable();
            $t->timestamps();
            $t->unique(['provider', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_events');
        Schema::dropIfExists('payment_gateway_transactions');
    }
};
