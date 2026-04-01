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
        // Add new columns to orders
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('paid_amount', 10, 2)->default(0.00)->after('total');
            $table->decimal('return_charge', 10, 2)->default(0.00)->after('paid_amount');
        });

        // Add new columns to order_items
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('is_pre_order');
            $table->integer('returned_quantity')->default(0)->after('status');
        });

        // Safe ENUM modification for MariaDB/MySQL
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'processing', 'cancelled', 'shipped', 'delivered', 'returned', 'incomplete', 'on_hold', 'confirmed', 'partially_delivered') DEFAULT 'pending'");
            DB::statement("ALTER TABLE orders MODIFY COLUMN payment_status ENUM('unpaid', 'paid', 'refunded', 'failed', 'partially_paid') DEFAULT 'unpaid'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['paid_amount', 'return_charge']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['status', 'returned_quantity']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'processing', 'cancelled', 'shipped', 'delivered', 'returned', 'incomplete', 'on_hold', 'confirmed') DEFAULT 'pending'");
            DB::statement("ALTER TABLE orders MODIFY COLUMN payment_status ENUM('unpaid', 'paid', 'refunded', 'failed') DEFAULT 'unpaid'");
        }
    }
};
