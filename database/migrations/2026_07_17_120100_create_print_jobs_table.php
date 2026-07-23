<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->json('payload');
            $table->json('order_detail_ids')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->text('error')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('printed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'branch_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_jobs');
    }
};
