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

        if ($tahunAjaranId) {
            $jadwals = [
                [
                    'kegiatan' => 'Verifikasi Berkas Pendaftaran',
                    'tanggal_mulai' => '2026-08-01 08:00:00',
                    'tanggal_selesai' => '2026-08-03 15:00:00',
                    'lokasi' => 'Online melalui dashboard SPMB',
                    'keterangan' => 'Panitia memeriksa kelengkapan formulir dan dokumen peserta.',
                ],
                [
                    'kegiatan' => 'Tes Akademik SPMB',
                    'tanggal_mulai' => '2026-08-05 08:00:00',
                    'tanggal_selesai' => '2026-08-05 11:00:00',
                    'lokasi' => 'Kampus SMK Muhammadiyah 4 Cileungsi',
                    'keterangan' => 'Peserta membawa kartu pendaftaran, alat tulis, dan hadir 30 menit sebelum tes.',
                ],
                [
                    'kegiatan' => 'Wawancara Calon Siswa',
                    'tanggal_mulai' => '2026-08-06 08:00:00',
                    'tanggal_selesai' => '2026-08-06 14:00:00',
                    'lokasi' => 'Ruang Panitia SPMB',
                    'keterangan' => 'Wawancara singkat bersama panitia. Jadwal sesi dapat menyesuaikan antrean peserta.',
                ],
                [
                    'kegiatan' => 'Pengumuman Hasil Seleksi',
                    'tanggal_mulai' => '2026-08-08 10:00:00',
                    'tanggal_selesai' => '2026-08-08 10:00:00',
                    'lokasi' => 'Dashboard peserta',
                    'keterangan' => 'Hasil seleksi dapat dilihat melalui dashboard masing-masing peserta.',
                ],
            ];

            foreach ($jadwals as $jadwal) {
                DB::table('jadwal_spmb')->updateOrInsert(
                    [
                        'tahun_ajaran_id' => $tahunAjaranId,
                        'kegiatan' => $jadwal['kegiatan'],
                    ],
                    $jadwal + [
                        'tahun_ajaran_id' => $tahunAjaranId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        $tests = [
            [
                'test_name' => 'Tes Akademik SPMB',
                'test_date' => '2026-08-05',
                'location' => 'Kampus SMK Muhammadiyah 4 Cileungsi',
                'description' => 'Tes kemampuan dasar. Bawa alat tulis dan kartu pendaftaran.',
            ],
            [
                'test_name' => 'Wawancara Calon Siswa',
                'test_date' => '2026-08-06',
                'location' => 'Ruang Panitia SPMB',
                'description' => 'Wawancara singkat tentang minat jurusan dan kesiapan sekolah.',
            ],
        ];

        foreach ($tests as $test) {
            DB::table('tes_masuk')->updateOrInsert(
                ['test_name' => $test['test_name']],
                $test + [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('jadwal_spmb')->whereIn('kegiatan', [
            'Verifikasi Berkas Pendaftaran',
            'Tes Akademik SPMB',
            'Wawancara Calon Siswa',
            'Pengumuman Hasil Seleksi',
        ])->delete();

        DB::table('tes_masuk')->whereIn('test_name', [
            'Tes Akademik SPMB',
            'Wawancara Calon Siswa',
        ])->delete();
    }
};
