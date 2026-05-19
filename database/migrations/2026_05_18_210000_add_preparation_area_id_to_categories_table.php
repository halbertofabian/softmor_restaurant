<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'preparation_area_id')) {
                $table->foreignId('preparation_area_id')->nullable()->after('name')->constrained('preparation_areas')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'preparation_area_id')) {
                $table->dropConstrainedForeignId('preparation_area_id');
            }
        });
    }
};
