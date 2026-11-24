<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_pages', function (Blueprint $table) {
            // help | about | none. NULL behaves like "help", which is where every page was listed before.
            $table->string('footer_group', 10)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('site_pages', function (Blueprint $table) {
            $table->dropColumn('footer_group');
        });
    }
};
