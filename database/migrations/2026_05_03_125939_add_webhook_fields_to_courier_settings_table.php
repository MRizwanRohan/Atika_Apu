<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courier_settings', function (Blueprint $table) {
            // Pathao Webhook
            $table->enum('pathao_webhook_enabled', ['yes', 'no'])->default('no')->after('StoreId');
            $table->string('pathao_webhook_token')->nullable()->after('pathao_webhook_enabled');
            $table->json('pathao_status_mapping')->nullable()->after('pathao_webhook_token');

            // Steadfast Webhook
            $table->enum('steadfast_webhook_enabled', ['yes', 'no'])->default('no')->after('steadfast_is_enabled');
            $table->string('steadfast_webhook_token')->nullable()->after('steadfast_webhook_enabled');
            $table->json('steadfast_status_mapping')->nullable()->after('steadfast_webhook_token');
        });
    }

    public function down(): void
    {
        Schema::table('courier_settings', function (Blueprint $table) {
            $table->dropColumn([
                'pathao_webhook_enabled',
                'pathao_webhook_token',
                'pathao_status_mapping',
                'steadfast_webhook_enabled',
                'steadfast_webhook_token',
                'steadfast_status_mapping',
            ]);
        });
    }
};
