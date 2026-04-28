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
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('unit_cost', 10, 2)->default(0)->after('price');
        });

        Schema::table('product_variations', function (Blueprint $table) {
            $table->decimal('unit_cost', 10, 2)->default(0)->after('price');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('unit_cost', 10, 2)->default(0)->after('unit_price');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->unsignedBigInteger('expense_category_id')->nullable()->after('id');
            $table->foreign('expense_category_id')->references('id')->on('expense_categories')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['expense_category_id']);
            $table->dropColumn('expense_category_id');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('unit_cost');
        });

        Schema::table('product_variations', function (Blueprint $table) {
            $table->dropColumn('unit_cost');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('unit_cost');
        });
    }
};
