<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_templates', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('name'); // e.g., "Ventas Semanales", "Top Productos"
            $table->text('description')->nullable();
            $table->json('keywords'); // Keywords to match questions
            $table->text('base_sql'); // SQL template with placeholders
            $table->json('parameters_schema'); // Define what parameters are needed
            $table->string('default_chart_type'); // Default visualization
            $table->json('chart_config')->nullable();
            $table->integer('usage_count')->default(0); // Track popularity
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_templates');
    }
};
