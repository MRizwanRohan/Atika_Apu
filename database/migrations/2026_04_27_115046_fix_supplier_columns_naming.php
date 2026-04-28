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
        Schema::table('suppliers', function (Blueprint $table) {
            if (Schema::hasColumn('suppliers', 'status')) {
                $table->renameColumn('status', 'is_active');
            }
            if (Schema::hasColumn('suppliers', 'balance')) {
                $table->renameColumn('balance', 'opening_balance');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            if (Schema::hasColumn('suppliers', 'is_active')) {
                $table->renameColumn('is_active', 'status');
            }
            if (Schema::hasColumn('suppliers', 'opening_balance')) {
                $table->renameColumn('opening_balance', 'balance');
            }
        });
    }
};
