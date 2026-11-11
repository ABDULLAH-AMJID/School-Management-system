<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outbound_messages', function (Blueprint $t) {
            $t->foreignId('template_id')->nullable()->after('school_id')->constrained('message_templates')->nullOnDelete();
            $t->foreignId('student_id')->nullable()->after('template_id')->constrained()->nullOnDelete();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('approved_at')->nullable();
            $t->string('idempotency_key')->unique();
            $t->json('metadata')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('outbound_messages', function (Blueprint $t) {
            $t->dropForeign(['template_id']);
            $t->dropForeign(['student_id']);
            $t->dropForeign(['created_by']);
            $t->dropForeign(['approved_by']);
            $t->dropColumn(['template_id', 'student_id', 'created_by', 'approved_by', 'approved_at', 'idempotency_key', 'metadata']);
        });
    }
};
