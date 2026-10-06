<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->timestamps();
        });

        $names = DB::table('documents')->distinct()->pluck('category')->merge([
            'Pembangunan ruang kelas', 'Renovasi atap', 'Pembangunan toilet',
            'Perbaikan lantai', 'Pengecatan bangunan', 'Pembangunan pagar',
        ])->filter()->unique();

        foreach ($names as $name) {
            DB::table('categories')->insert(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
