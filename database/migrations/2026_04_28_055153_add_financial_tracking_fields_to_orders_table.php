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
        Schema::table('orders', function (Blueprint $blueprint) {
            $blueprint->decimal('actual_courier_fee', 10, 2)->default(0)->after('shipping_cost');
            $blueprint->decimal('gateway_fee', 10, 2)->default(0)->after('actual_courier_fee');
            $blueprint->decimal('packaging_cost', 10, 2)->default(0)->after('gateway_fee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $blueprint) {
            $blueprint->dropColumn(['actual_courier_fee', 'gateway_fee', 'packaging_cost']);
        });
    }
};
