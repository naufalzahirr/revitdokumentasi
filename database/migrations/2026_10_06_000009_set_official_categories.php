<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            DB::table('categories')->delete();
            foreach (config('categories.defaults') as $name) {
                DB::table('categories')->insert(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
            }
        });
    }

    public function down(): void
    {
        // Category names already assigned to notes are not rewritten on rollback.
    }
};
