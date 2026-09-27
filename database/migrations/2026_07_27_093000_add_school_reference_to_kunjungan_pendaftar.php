<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kunjungan_pendaftar', function (Blueprint $table) {
            $table->foreignId('school_reference_id')->nullable()->after('origin_school')->constrained('referensi_sekolah')->nullOnDelete();
            $table->string('origin_school_npsn', 8)->nullable()->after('school_reference_id');
        });
    }

    public function down(): void
    {
        Schema::table('kunjungan_pendaftar', function (Blueprint $table) {
            $table->dropConstrainedForeignId('school_reference_id');
            $table->dropColumn('origin_school_npsn');
        });
    }
};
