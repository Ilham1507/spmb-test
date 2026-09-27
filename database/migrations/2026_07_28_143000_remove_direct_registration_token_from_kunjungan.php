<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kunjungan_pendaftar', function (Blueprint $table) {
            $table->dropUnique(['registration_token']);
            $table->dropColumn(['registration_token', 'registration_token_used_at']);
        });
    }

    public function down(): void
    {
        Schema::table('kunjungan_pendaftar', function (Blueprint $table) {
            $table->string('registration_token', 64)->nullable()->unique()->after('applicant_id');
            $table->timestamp('registration_token_used_at')->nullable()->after('registration_token');
        });
    }
};
