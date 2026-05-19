<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE products MODIFY COLUMN type ENUM('dish','drink','finished','extra','combo') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE products MODIFY COLUMN type ENUM('dish','drink','finished','extra') NOT NULL");
    }
};
