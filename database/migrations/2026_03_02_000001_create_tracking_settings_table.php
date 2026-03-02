<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracking_settings', function (Blueprint $table) {
            $table->id();
            $table->string('pixel_id')->nullable()->comment('Meta Pixel ID');
            $table->text('access_token')->nullable()->comment('Meta Conversion API Access Token');
            $table->string('test_event_code', 50)->nullable()->comment('Test Event Code from Meta Events Manager');
            $table->string('api_version', 10)->default('v22.0')->comment('Meta Graph API version');
            $table->boolean('pixel_enabled')->default(false)->comment('Enable/disable browser-side Meta Pixel');
            $table->boolean('capi_enabled')->default(false)->comment('Enable/disable server-side Conversion API');
            $table->boolean('test_mode')->default(false)->comment('Use test_event_code for testing');
            $table->enum('purchase_fire_mode', [
                'on_order_created',
                'on_confirmed',
                'on_delivered',
                'manual_only',
                'disabled',
            ])->default('on_order_created')->comment('When to auto-fire Purchase event');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracking_settings');
    }
};
