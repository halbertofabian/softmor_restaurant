<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            if (!Schema::hasColumn('order_details', 'is_combo_component')) {
                $table->boolean('is_combo_component')->default(false)->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            if (Schema::hasColumn('order_details', 'is_combo_component')) {
                $table->dropColumn('is_combo_component');
            }
        });
    }
};
