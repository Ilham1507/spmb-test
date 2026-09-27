<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $testDate = '2026-08-05';
        $testLocation = 'Kampus SMK Muhammadiyah 4 Cileungsi';
        $now = now();

        DB::table('tes_masuk')->whereIn('test_name', [
            'Tes Akademik SPMB',
            'Wawancara Calon Siswa',
        ])->delete();

        $tests = [
            [
                'test_name' => 'Baca Tulis Quran',
                'description' => 'Peserta membaca ayat pendek dan menulis huruf hijaiyah sesuai arahan penguji.',
            ],
            [
                'test_name' => 'Tes Ukuran Seragam',
                'description' => 'Pengukuran kebutuhan seragam seperti S, M, L, XL, dan ukuran lain yang sesuai.',
            ],
            [
                'test_name' => 'Tes Kesehatan',
                'description' => 'Pemeriksaan tensi, berat badan, dan buta warna.',
            ],
            [
                'test_name' => 'Tes CBT',
                'description' => 'Tes berbasis komputer menggunakan HP pribadi dan koneksi internet.',
            ],
            [
                'test_name' => 'Wawancara Orang Tua',
                'description' => 'Wawancara singkat bersama orang tua/wali calon siswa.',
            ],
        ];

        foreach ($tests as $test) {
            DB::table('tes_masuk')->updateOrInsert(
                ['test_name' => $test['test_name']],
                $test + [
                    'test_date' => $testDate,
                    'location' => $testLocation,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $tahunAjaranId = DB::table('tahun_ajaran')
            ->where('is_active', true)
            ->value('id') ?? DB::table('tahun_ajaran')->value('id');

        if ($tahunAjaranId) {
            DB::table('jadwal_spmb')->whereIn('kegiatan', [
                'Tes Akademik SPMB',
                'Wawancara Calon Siswa',
            ])->delete();

            DB::table('jadwal_spmb')->updateOrInsert(
                [
                    'tahun_ajaran_id' => $tahunAjaranId,
                    'kegiatan' => 'Tes SPMB Terpadu',
                ],
                [
                    'tahun_ajaran_id' => $tahunAjaranId,
                    'kegiatan' => 'Tes SPMB Terpadu',
                    'tanggal_mulai' => $testDate . ' 08:00:00',
                    'tanggal_selesai' => $testDate . ' 14:00:00',
                    'lokasi' => $testLocation,
                    'keterangan' => 'Tes dilakukan dalam satu hari: BTQ, ukuran seragam, kesehatan, CBT, dan wawancara orang tua.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('tes_masuk')->whereIn('test_name', [
            'Baca Tulis Quran',
            'Tes Ukuran Seragam',
            'Tes Kesehatan',
            'Tes CBT',
            'Wawancara Orang Tua',
        ])->delete();

        DB::table('jadwal_spmb')->where('kegiatan', 'Tes SPMB Terpadu')->delete();
    }
};
