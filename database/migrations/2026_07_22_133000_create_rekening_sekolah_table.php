<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rekening_sekolah')) {
            return;
        }

        Schema::create('rekening_sekolah', function (Blueprint $table) {
            $table->id();
            $table->string('nama_bank', 100);
            $table->string('nomor_rekening', 100);
            $table->string('atas_nama', 150);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Tabel ini bisa berasal dari database import lama, jadi rollback tidak menghapus data rekening operasional.
    }
};
