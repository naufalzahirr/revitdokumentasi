<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->nullable()->unique();
            $table->string('role', 20)->default('editor');
            $table->boolean('is_active')->default(true);
            $table->boolean('must_change_password')->default(true);
            $table->unsignedInteger('auth_version')->default(1);
        });
        // Preserve existing users without giving them administrator access.
        foreach (DB::table('users')->get(['id']) as $user) {
            DB::table('users')->where('id', $user->id)->update(['username' => 'user'.$user->id]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'role', 'is_active', 'must_change_password', 'auth_version']);
        });
    }
};
