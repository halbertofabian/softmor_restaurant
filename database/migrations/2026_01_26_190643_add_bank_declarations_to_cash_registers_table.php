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
        Schema::table('cash_registers', function (Blueprint $table) {
            $table->decimal('declared_card', 10, 2)->nullable()->after('closing_amount');
            $table->decimal('declared_transfer', 10, 2)->nullable()->after('declared_card');
            $table->decimal('declared_deposit', 10, 2)->nullable()->after('declared_transfer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_registers', function (Blueprint $table) {
            $table->dropColumn(['declared_card', 'declared_transfer', 'declared_deposit']);
        });
    }
};
