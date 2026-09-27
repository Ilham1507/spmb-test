<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referensi_sekolah', function (Blueprint $table) {
            $table->id();
            $table->string('npsn', 8)->unique();
            $table->string('nama');
            $table->string('bentuk_pendidikan', 30)->nullable();
            $table->string('status', 30)->nullable();
            $table->text('alamat')->nullable();
            $table->string('desa_kelurahan')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('kabupaten_kota')->nullable();
            $table->string('provinsi')->nullable();
            $table->timestamps();

            $table->index('nama');
            $table->index('kabupaten_kota');
            $table->index('bentuk_pendidikan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referensi_sekolah');
    }
};
