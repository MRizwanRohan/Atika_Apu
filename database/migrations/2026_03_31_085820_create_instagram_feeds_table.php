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
        Schema::create('instagram_feeds', function (Blueprint $schema) {
            $schema->id();
            $schema->string('image')->nullable();
            $schema->string('link')->nullable();
            $schema->boolean('status')->default(true);
            $schema->integer('sort_order')->default(0);
            $schema->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instagram_feeds');
    }
};
