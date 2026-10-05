<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('global_offers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('enabled')->default(false)->index();
            $table->decimal('minimum_subtotal', 12, 2);
            $table->string('reward_type', 30);
            $table->decimal('reward_value', 12, 2)->nullable();
            $table->decimal('maximum_discount', 12, 2)->nullable();
            $table->unsignedInteger('priority')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('global_discount_total', 12, 2)->default(0);
            $table->decimal('shipping_discount_total', 12, 2)->default(0);
            $table->decimal('normal_shipping_charge', 12, 2)->nullable();
            $table->json('offer_snapshot')->nullable();
        });
        \Spatie\Permission\Models\Permission::findOrCreate('admin.global-offers.index', 'web');
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn(['global_discount_total', 'shipping_discount_total', 'normal_shipping_charge', 'offer_snapshot']));
        Schema::dropIfExists('global_offers');
    }
};
