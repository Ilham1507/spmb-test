<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kunjungan_pendaftar', function (Blueprint $table) {
            $table->foreignId('interested_major_id')->nullable()->after('major_interest')->constrained('jurusan')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('kunjungan_pendaftar', function (Blueprint $table) {
            $table->dropConstrainedForeignId('interested_major_id');
        });
    }
};
