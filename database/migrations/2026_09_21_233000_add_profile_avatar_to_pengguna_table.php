<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengguna', function (Blueprint $table) {
            $table->string('avatar_choice', 24)->default('character_1')->after('role_id');
            $table->string('profile_photo_path')->nullable()->after('avatar_choice');
        });
    }

    public function down(): void
    {
        Schema::table('pengguna', function (Blueprint $table) {
            $table->dropColumn(['avatar_choice', 'profile_photo_path']);
        });
    }
};
