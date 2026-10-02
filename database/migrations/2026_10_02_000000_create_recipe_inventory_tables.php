<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->foreignId('branch_id')->index()->constrained('branches')->cascadeOnDelete();
            $table->string('name');
            $table->string('base_unit', 10);
            $table->decimal('stock', 15, 3)->default(0);
            $table->decimal('min_stock', 15, 3)->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->index(['tenant_id', 'branch_id', 'status']);
        });

        Schema::create('product_recipe_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->decimal('quantity', 15, 3);
            $table->string('tenant_id')->index();
            $table->foreignId('branch_id')->index()->constrained('branches')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['product_id', 'inventory_item_id']);
        });

        Schema::create('inventory_item_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->foreignId('order_detail_id')->nullable()->constrained('order_details')->restrictOnDelete();
            $table->foreignId('reversed_movement_id')->nullable()->constrained('inventory_item_movements')->restrictOnDelete();
            $table->string('type', 20);
            $table->decimal('quantity', 15, 3);
            $table->decimal('previous_stock', 15, 3);
            $table->decimal('new_stock', 15, 3);
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tenant_id')->index();
            $table->foreignId('branch_id')->index()->constrained('branches')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['order_detail_id', 'inventory_item_id', 'type'], 'inventory_movement_detail_item_type_unique');
            $table->unique('reversed_movement_id');
        });

        Schema::table('order_details', function (Blueprint $table) {
            $table->foreignId('parent_order_detail_id')->nullable()->after('order_id')
                ->constrained('order_details')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_order_detail_id');
        });
        Schema::dropIfExists('inventory_item_movements');
        Schema::dropIfExists('product_recipe_items');
        Schema::dropIfExists('inventory_items');
    }
};
