<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('jadwal_spmb')
            ->where('available_for_student_selection', true)
            ->where('kegiatan', 'Tes SPMB')
            ->update([
                'keterangan' => 'Lokasi: Kampus E SMK Muhammadiyah 4 Cileungsi.',
                'updated_at' => now(),
            ]);
    }

    public function down(): void {}
};
