<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recipe_section_settings', function (Blueprint $table) {
            $table->string('description', 240)->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('recipe_section_settings', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
