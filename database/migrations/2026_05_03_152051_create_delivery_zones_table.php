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
        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['district', 'upazila']);
            $table->string('name');
            $table->string('bn_name')->nullable();
            $table->string('json_id')->index(); // ID from the JSON hierarchy
            $table->unsignedBigInteger('parent_id')->nullable()->index(); // For upazilas, links to district delivery_zones.id
            $table->decimal('charge', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_zones');
    }
};
