<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_agents', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_agents');
    }
};
