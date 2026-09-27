<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tahunAjaranId = DB::table('tahun_ajaran')
            ->where('is_active', true)
            ->value('id') ?? DB::table('tahun_ajaran')->value('id');

        if (!$tahunAjaranId) {
            return;
        }

        $now = now();
        $location = 'Kampus SMK Muhammadiyah 4 Cileungsi';

        DB::table('jadwal_spmb')
            ->where('kegiatan', 'like', 'Tes SPMB Terpadu%')
            ->delete();

        $sessions = [
            [
                'kegiatan' => 'Tes SPMB Terpadu - Sesi 1',
                'tanggal_mulai' => '2026-08-05 08:00:00',
                'tanggal_selesai' => '2026-08-05 11:00:00',
                'keterangan' => 'Sesi pagi. Jadwal final peserta akan dikonfirmasi panitia.',
            ],
            [
                'kegiatan' => 'Tes SPMB Terpadu - Sesi 2',
                'tanggal_mulai' => '2026-08-05 13:00:00',
                'tanggal_selesai' => '2026-08-05 16:00:00',
                'keterangan' => 'Sesi siang. Jadwal final peserta akan dikonfirmasi panitia.',
            ],
            [
                'kegiatan' => 'Tes SPMB Terpadu - Sesi 3',
                'tanggal_mulai' => '2026-08-06 08:00:00',
                'tanggal_selesai' => '2026-08-06 11:00:00',
                'keterangan' => 'Sesi tambahan jika kuota hari pertama penuh atau peserta berhalangan.',
            ],
        ];

        foreach ($sessions as $session) {
            DB::table('jadwal_spmb')->insert($session + [
                'tahun_ajaran_id' => $tahunAjaranId,
                'lokasi' => $location,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('jadwal_spmb')
            ->where('kegiatan', 'like', 'Tes SPMB Terpadu%')
            ->delete();
    }
};
