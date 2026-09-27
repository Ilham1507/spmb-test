<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pendaftar', function (Blueprint $table) {
            $table->unsignedBigInteger('admission_path_id')->nullable()->change();
            $table->unsignedBigInteger('major_choice_1')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Nilai pilihan tidak boleh dipaksakan kembali karena akun draft dapat masih kosong.
    }
};
