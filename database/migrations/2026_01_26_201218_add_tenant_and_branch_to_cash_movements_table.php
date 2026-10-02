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
        DB::table('cash_movements')
            ->whereNull('tenant_id')
            ->orWhereNull('branch_id')
            ->orderBy('id')
            ->each(function ($movement) {
                $register = DB::table('cash_registers')->where('id', $movement->cash_register_id)->first();
                if ($register) {
                    DB::table('cash_movements')->where('id', $movement->id)->update([
                        'tenant_id' => $movement->tenant_id ?? $register->tenant_id,
                        'branch_id' => $movement->branch_id ?? $register->branch_id,
                    ]);
                }
            });

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
