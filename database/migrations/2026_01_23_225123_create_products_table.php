<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['dish', 'drink', 'finished', 'extra']);
            $table->decimal('price', 10, 2);
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->foreignId('preparation_area_id')->constrained('preparation_areas')->cascadeOnDelete();
            $table->boolean('controls_inventory')->default(false);
            $table->integer('stock')->default(0);
            $table->integer('min_stock')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
