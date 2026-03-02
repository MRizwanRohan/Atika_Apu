<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meta_event_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->string('event_name', 100)->comment('Purchase, AddToCart, ViewContent, etc.');
            $table->string('event_id', 100)->unique()->comment('SHA-256 deduplication ID');
            $table->string('trigger', 50)->default('system')
                ->comment('on_order_created|on_confirmed|on_delivered|manual|browser');
            $table->enum('status', ['pending', 'sent', 'failed', 'skipped'])->default('pending');
            $table->json('payload')->nullable()->comment('Request payload sent to Meta');
            $table->json('response')->nullable()->comment('Meta API response');
            $table->string('fired_by', 50)->default('system')
                ->comment('system | admin | user_id');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
            $table->index(['order_id', 'event_name']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meta_event_logs');
    }
};
