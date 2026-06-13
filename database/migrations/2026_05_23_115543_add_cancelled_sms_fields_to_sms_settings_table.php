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
        Schema::table('sms_settings', function (Blueprint $table) {
            $table->boolean('order_cancel_sms_enabled')->default(false)->after('order_create_sms_enabled');
            $table->text('order_cancel_sms_template')->nullable()->after('order_create_sms_template');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sms_settings', function (Blueprint $table) {
            $table->dropColumn(['order_cancel_sms_enabled', 'order_cancel_sms_template']);
        });
    }
};
