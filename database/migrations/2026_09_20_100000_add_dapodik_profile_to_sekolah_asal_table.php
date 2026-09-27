<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sekolah_asal', function (Blueprint $table) {
            if (! Schema::hasColumn('sekolah_asal', 'referensi_sekolah_id')) {
                $table->unsignedBigInteger('referensi_sekolah_id')->nullable()->after('applicant_id');
            }
            if (! Schema::hasColumn('sekolah_asal', 'bentuk_pendidikan')) {
                $table->string('bentuk_pendidikan', 30)->nullable()->after('npsn');
            }
            if (! Schema::hasColumn('sekolah_asal', 'status_sekolah')) {
                $table->string('status_sekolah', 30)->nullable()->after('bentuk_pendidikan');
            }
            if (! Schema::hasColumn('sekolah_asal', 'desa_kelurahan')) {
                $table->string('desa_kelurahan')->nullable()->after('school_address');
            }
            if (! Schema::hasColumn('sekolah_asal', 'kecamatan')) {
                $table->string('kecamatan')->nullable()->after('desa_kelurahan');
            }
            if (! Schema::hasColumn('sekolah_asal', 'kabupaten_kota')) {
                $table->string('kabupaten_kota')->nullable()->after('kecamatan');
            }
            if (! Schema::hasColumn('sekolah_asal', 'provinsi')) {
                $table->string('provinsi')->nullable()->after('kabupaten_kota');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sekolah_asal', function (Blueprint $table) {
            $columns = ['referensi_sekolah_id', 'bentuk_pendidikan', 'status_sekolah', 'desa_kelurahan', 'kecamatan', 'kabupaten_kota', 'provinsi'];
            $existing = array_filter($columns, fn ($column) => Schema::hasColumn('sekolah_asal', $column));

            if ($existing) {
                $table->dropColumn($existing);
            }
        });
    }
};
