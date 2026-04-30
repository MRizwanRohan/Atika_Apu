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
        Schema::create('sms_logs', function (Blueprint $customer) {
            $customer->id();
            $customer->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            $customer->string('phone');
            $customer->text('message');
            $customer->string('status')->default('success'); // success, failed
            $customer->string('error_message')->nullable();
            $customer->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};
