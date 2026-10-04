<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('pengguna', 'remember_token')) {
            Schema::table('pengguna', fn (Blueprint $table) => $table->rememberToken());
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pengguna', 'remember_token')) {
            Schema::table('pengguna', fn (Blueprint $table) => $table->dropRememberToken());
        }
    }
};
