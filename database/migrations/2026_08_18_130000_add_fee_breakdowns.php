<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gelombang_jurusan', function (Blueprint $table) {
            $table->json('rincian_biaya')->nullable()->after('biaya_masuk');
        });

        Schema::table('tagihan_pendaftar', function (Blueprint $table) {
            $table->json('rincian_biaya')->nullable()->after('remaining_amount');
        });
    }

    public function down(): void
    {
        Schema::table('tagihan_pendaftar', fn (Blueprint $table) => $table->dropColumn('rincian_biaya'));
        Schema::table('gelombang_jurusan', fn (Blueprint $table) => $table->dropColumn('rincian_biaya'));
    }
};
