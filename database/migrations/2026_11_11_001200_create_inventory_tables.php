<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('phone')->nullable();
            $t->string('email')->nullable();
            $t->text('address')->nullable();
            $t->string('status')->default('active');
            $t->timestamps();
        });
        Schema::create('inventory_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('sku');
            $t->string('name');
            $t->string('category');
            $t->string('unit')->default('piece');
            $t->decimal('quantity', 12, 2)->default(0);
            $t->decimal('reorder_level', 12, 2)->default(0);
            $t->bigInteger('average_cost')->default(0);
            $t->string('location')->nullable();
            $t->boolean('active')->default(true);
            $t->timestamps();
            $t->unique(['school_id', 'sku']);
        });
        Schema::create('stock_movements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('inventory_item_id')->constrained()->restrictOnDelete();
            $t->string('type');
            $t->decimal('quantity', 12, 2);
            $t->bigInteger('unit_cost')->default(0);
            $t->string('reference')->nullable();
            $t->string('issued_to')->nullable();
            $t->text('notes')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('occurred_at');
            $t->timestamps();
        });
        Schema::create('fixed_assets', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('asset_tag');
            $t->string('name');
            $t->string('category');
            $t->date('purchased_on')->nullable();
            $t->bigInteger('purchase_cost')->default(0);
            $t->string('location')->nullable();
            $t->string('assigned_to')->nullable();
            $t->string('condition')->default('good');
            $t->string('status')->default('in_use');
            $t->timestamps();
            $t->unique(['school_id', 'asset_tag']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixed_assets');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('suppliers');
    }
};
