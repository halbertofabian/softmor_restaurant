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
        $tables = [
            'products', 
            'categories', 
            'preparation_areas', 
            'tables', 
            'orders', 
            'order_details',
            'inventory_movements' // Assuming this exists or will exist soon
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreignId('branch_id')->nullable()->after('tenant_id'); // Global index not strictly needed foreign key if we enforce via code, but good for reporting
                    $table->index(['tenant_id', 'branch_id']);
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'products', 
            'categories', 
            'preparation_areas', 
            'tables', 
            'orders', 
            'order_details',
            'inventory_movements'
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('branch_id');
                });
            }
        }
    }
};
