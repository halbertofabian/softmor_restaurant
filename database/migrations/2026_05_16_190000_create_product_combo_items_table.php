<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_combo_items')) {
            return;
        }

        Schema::create('product_combo_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('combo_product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('component_product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('default_flavor_id')->nullable()->constrained('product_flavors')->nullOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->uuid('tenant_id');
            $table->unsignedBigInteger('branch_id');
            $table->timestamps();

            $table->index(['combo_product_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_combo_items');
    }
};
