<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add columns if they don't exist
        if (!Schema::hasColumn('cash_movements', 'tenant_id')) {
            Schema::table('cash_movements', function (Blueprint $table) {
                $table->string('tenant_id')->nullable()->after('id');
            });
        }
        
        if (!Schema::hasColumn('cash_movements', 'branch_id')) {
            Schema::table('cash_movements', function (Blueprint $table) {
                $table->unsignedBigInteger('branch_id')->nullable()->after('tenant_id');
            });
        }

        // Populate existing records from their cash_register
        DB::statement('
            UPDATE cash_movements cm
            JOIN cash_registers cr ON cm.cash_register_id = cr.id
            SET cm.tenant_id = COALESCE(cm.tenant_id, cr.tenant_id),
                cm.branch_id = COALESCE(cm.branch_id, cr.branch_id)
            WHERE cm.tenant_id IS NULL OR cm.branch_id IS NULL
        ');

        // Now make them NOT NULL
        Schema::table('cash_movements', function (Blueprint $table) {
            $table->string('tenant_id')->nullable(false)->change();
            $table->unsignedBigInteger('branch_id')->nullable(false)->change();
        });
        
        // Try to add foreign key (may already exist)
        try {
            Schema::table('cash_movements', function (Blueprint $table) {
                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            });
        } catch (\Exception $e) {
            // Foreign key already exists, ignore
        }
    }

    public function down(): void
    {
        Schema::table('cash_movements', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn(['tenant_id', 'branch_id']);
        });
    }
};
