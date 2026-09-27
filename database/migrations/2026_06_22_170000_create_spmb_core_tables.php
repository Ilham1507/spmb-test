<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Jurusans
        Schema::create('jurusans', function (Blueprint $table) {
            $table->id();
            $table->string('nama_jurusan');
            $table->string('kode_jurusan', 20)->unique();
            $table->text('deskripsi')->nullable();
            $table->integer('kuota')->default(0);
            $table->timestamps();
        });

        // 2. Jalur Pendaftarans
        Schema::create('jalur_pendaftarans', function (Blueprint $table) {
            $table->id();
            $table->string('nama_jalur');
            $table->text('deskripsi')->nullable();
            $table->decimal('potongan_biaya', 12, 2)->default(0.00);
            $table->timestamps();
        });

        // 3. Gelombang Pendaftarans
        Schema::create('gelombang_pendaftarans', function (Blueprint $table) {
            $table->id();
            $table->string('nama_gelombang');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 4. Data Walis
        Schema::create('data_walis', function (Blueprint $table) {
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

        // 5. Jenis Dokumens
        Schema::create('jenis_dokumens', function (Blueprint $table) {
            $table->id();
            $table->string('nama_dokumen');
            $table->boolean('is_wajib')->default(true);
            $table->timestamps();
        });

        // 6. Dokumen Pendaftars
        Schema::create('dokumen_pendaftars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pendaftar_id')->constrained('pendaftars')->cascadeOnDelete();
            $table->foreignId('jenis_dokumen_id')->constrained('jenis_dokumens')->cascadeOnDelete();
            $table->string('file_path');
            $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        // Add foreign keys & choices to pendaftars table
        Schema::table('pendaftars', function (Blueprint $table) {
            $table->foreignId('gelombang_pendaftaran_id')->nullable()->constrained('gelombang_pendaftarans')->nullOnDelete();
            $table->foreignId('jalur_pendaftaran_id')->nullable()->constrained('jalur_pendaftarans')->nullOnDelete();
            $table->foreignId('jurusan_id_1')->nullable()->constrained('jurusans')->nullOnDelete();
            $table->foreignId('jurusan_id_2')->nullable()->constrained('jurusans')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pendaftars', function (Blueprint $table) {
            $table->dropForeign(['gelombang_pendaftaran_id']);
            $table->dropForeign(['jalur_pendaftaran_id']);
            $table->dropForeign(['jurusan_id_1']);
            $table->dropForeign(['jurusan_id_2']);
            $table->dropColumn(['gelombang_pendaftaran_id', 'jalur_pendaftaran_id', 'jurusan_id_1', 'jurusan_id_2']);
        });

        Schema::dropIfExists('dokumen_pendaftars');
        Schema::dropIfExists('jenis_dokumens');
        Schema::dropIfExists('data_walis');
        Schema::dropIfExists('gelombang_pendaftarans');
        Schema::dropIfExists('jalur_pendaftarans');
        Schema::dropIfExists('jurusans');
    }
};
