<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $existing = DB::table('categories')->where('slug', 'sauce')->first();

        if ($existing) {
            DB::table('categories')->where('id', $existing->id)->update(['deleted_at' => null]);

            return;
        }

        DB::table('categories')->insert([
            'name' => 'Sauce',
            'slug' => 'sauce',
            'status' => 'active',
            'order' => 0,
            'parent_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // The Sauce category is permanent; products depend on it.
    }
};
