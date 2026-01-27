<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_reports', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('user_id');
            $table->text('question'); // User's original question
            $table->text('interpretation')->nullable(); // AI's interpretation
            $table->text('sql_query')->nullable(); // Generated SQL query
            $table->json('parameters')->nullable(); // Extracted parameters (dates, filters, etc)
            $table->json('result_data')->nullable(); // Query results
            $table->string('chart_type')->nullable(); // bar, line, pie, table, etc
            $table->json('chart_config')->nullable(); // Chart.js configuration
            $table->boolean('is_favorite')->default(false);
            $table->unsignedBigInteger('template_id')->nullable(); // Link to template if matched
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_reports');
    }
};
