<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Tabel utama pendaftar ──────────────────────────────
        Schema::create('pendaftars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('no_pendaftaran')->unique()->nullable();
            $table->enum('status', ['draft', 'submitted', 'verified', 'accepted', 'rejected'])->default('draft');
            $table->timestamps();
        });

        // ── Biodata ────────────────────────────────────────────
        Schema::create('biodata_pendaftars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pendaftar_id')->constrained('pendaftars')->cascadeOnDelete();
            $table->string('nama_lengkap');
            $table->string('nisn', 20)->nullable();
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('agama')->nullable();
            $table->string('no_hp', 20)->nullable();
            $table->timestamps();
        });

        // ── Alamat ─────────────────────────────────────────────
        Schema::create('alamat_pendaftars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pendaftar_id')->constrained('pendaftars')->cascadeOnDelete();
            $table->text('alamat');
            $table->string('rt', 5)->nullable();
            $table->string('rw', 5)->nullable();
            $table->string('kelurahan')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('kota')->nullable();
            $table->string('provinsi')->nullable();
            $table->string('kode_pos', 10)->nullable();
            $table->timestamps();
        });

        // ── Data Ayah ──────────────────────────────────────────
        Schema::create('data_ayahs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pendaftar_id')->constrained('pendaftars')->cascadeOnDelete();
            $table->string('nama');
            $table->string('nik', 20)->nullable();
            $table->string('pekerjaan')->nullable();
            $table->string('pendidikan')->nullable();
            $table->string('penghasilan')->nullable();
            $table->string('no_hp', 20)->nullable();
            $table->timestamps();
        });

        // ── Data Ibu ───────────────────────────────────────────
        Schema::create('data_ibus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pendaftar_id')->constrained('pendaftars')->cascadeOnDelete();
            $table->string('nama');
            $table->string('nik', 20)->nullable();
            $table->string('pekerjaan')->nullable();
            $table->string('pendidikan')->nullable();
            $table->string('penghasilan')->nullable();
            $table->string('no_hp', 20)->nullable();
            $table->timestamps();
        });

        // ── Sekolah Asal ───────────────────────────────────────
        Schema::create('sekolah_asals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pendaftar_id')->constrained('pendaftars')->cascadeOnDelete();
            $table->string('nama_sekolah');
            $table->string('npsn', 20)->nullable();
            $table->string('alamat_sekolah')->nullable();
            $table->integer('tahun_lulus')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sekolah_asals');
        Schema::dropIfExists('data_ibus');
        Schema::dropIfExists('data_ayahs');
        Schema::dropIfExists('alamat_pendaftars');
        Schema::dropIfExists('biodata_pendaftars');
        Schema::dropIfExists('pendaftars');
    }
};
