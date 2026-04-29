<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add new enum value 'received_no_stock' to status column
        DB::statement("ALTER TABLE `purchases` MODIFY `status` ENUM('pending','received','received_no_stock') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum values
        DB::statement("ALTER TABLE `purchases` MODIFY `status` ENUM('pending','received') NOT NULL DEFAULT 'pending'");
    }
};
