<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            if (!Schema::hasColumn('order_details', 'product_flavor_id')) {
                $table->foreignId('product_flavor_id')->nullable()->after('product_id')->constrained('product_flavors')->nullOnDelete();
            }
            if (!Schema::hasColumn('order_details', 'flavor_name')) {
                $table->string('flavor_name')->nullable()->after('product_name');
            }
            if (!Schema::hasColumn('order_details', 'flavor_price_delta')) {
                $table->decimal('flavor_price_delta', 10, 2)->default(0)->after('price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            if (Schema::hasColumn('order_details', 'product_flavor_id')) {
                $table->dropConstrainedForeignId('product_flavor_id');
            }
            if (Schema::hasColumn('order_details', 'flavor_name')) {
                $table->dropColumn('flavor_name');
            }
            if (Schema::hasColumn('order_details', 'flavor_price_delta')) {
                $table->dropColumn('flavor_price_delta');
            }
        });
    }
};
