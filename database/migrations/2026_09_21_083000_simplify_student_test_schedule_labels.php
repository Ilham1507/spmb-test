<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('jadwal_spmb')
            ->where('available_for_student_selection', true)
            ->where('kegiatan', 'like', 'Tes SPMB Terpadu%')
            ->update(['kegiatan' => 'Tes SPMB', 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Nama sesi lama tidak dipulihkan karena tanggal adalah pembeda utama untuk siswa.
    }
};
