<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('featured_product_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(false);
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('badge', 40)->nullable();
            $table->string('description', 300)->nullable();
            $table->json('highlights')->nullable();
            $table->string('bg_color', 9)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('featured_product_settings');
    }
};
