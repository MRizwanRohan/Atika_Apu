<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->string('subtitle', 120)->nullable();
            $table->string('slug')->unique();
            $table->string('image');
            $table->longText('content')->nullable();
            $table->boolean('status')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['status', 'sort_order']);
        });

        Schema::create('recipe_section_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(true);
            $table->string('label', 60)->nullable();
            $table->string('title', 100)->nullable();
            $table->string('banner')->nullable();
            $table->string('banner_link')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_section_settings');
        Schema::dropIfExists('recipes');
    }
};
