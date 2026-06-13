<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'processing', 'cancelled', 'shipped', 'delivered', 'returned', 'incomplete', 'on_hold', 'confirmed', 'partially_delivered', 'received_china', 'received_bd_airport', 'received_bd_warehouse') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'processing', 'cancelled', 'shipped', 'delivered', 'returned', 'incomplete', 'on_hold', 'confirmed', 'partially_delivered') DEFAULT 'pending'");
    }
};
