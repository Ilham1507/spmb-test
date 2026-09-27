<?php

namespace Database\Seeders;

use App\Models\Jurusan;
use App\Models\JalurPendaftaran;
use App\Models\GelombangPendaftaran;
use App\Models\JenisDokumen;
use App\Models\PengaturanSpmb;
use App\Models\TahunAjaran;
use Illuminate\Database\Seeder;

class SpmbMasterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tahunAjaran = TahunAjaran::firstOrCreate(
            ['name' => '2026/2027'],
            [
                'start_date' => '2026-07-01',
                'end_date' => '2027-06-30',
                'is_active' => true,
            ]
        );

        // 1. Seed Jurusan
        $jurusans = [
            [
                'code' => 'KUL',
                'name' => 'Kuliner',
                'description' => 'Kompetensi keahlian bidang pengolahan makanan, layanan boga, dan kewirausahaan kuliner.',
                'quota' => 50,
                'status' => 'aktif',
            ],
            [
                'code' => 'DPB',
                'name' => 'Desain dan Produksi Busana',
                'description' => 'Pembelajaran desain, produksi busana, kreativitas fashion, dan keterampilan industri busana.',
                'quota' => 50,
                'status' => 'aktif',
            ],
            [
                'code' => 'LFKK',
                'name' => 'Layanan Kefarmasian Klinis dan Komunitas',
                'description' => 'Kompetensi layanan kefarmasian, farmasi klinis, komunitas, dan praktik layanan kesehatan.',
                'quota' => 50,
                'status' => 'aktif',
            ],
            [
                'code' => 'LPKC',
                'name' => 'Layanan Penunjang Keperawatan dan Caregiving',
                'description' => 'Kompetensi layanan keperawatan dasar, caregiving, dan pendampingan layanan kesehatan.',
                'quota' => 50,
                'status' => 'aktif',
            ],
        ];

        foreach ($jurusans as $j) {
            Jurusan::updateOrCreate(['code' => $j['code']], $j);
        }

        // 2. Seed Jalur Pendaftaran
        $jalurs = [
            [
                'name' => 'Jalur Reguler',
                'quota' => 120,
                'description' => 'Pendaftaran umum dengan seleksi berkas dan nilai rapor.',
                'status' => 'aktif',
            ],
            [
                'name' => 'Jalur Prestasi',
                'quota' => 40,
                'description' => 'Pendaftaran bagi siswa berprestasi akademik atau non-akademik.',
                'status' => 'aktif',
            ],
            [
                'name' => 'Jalur Kemitraan / Yatim',
                'quota' => 20,
                'description' => 'Pendaftaran khusus bagi yatim/piatu dan kemitraan Muhammadiyah.',
                'status' => 'aktif',
            ],
        ];

        foreach ($jalurs as $jl) {
            JalurPendaftaran::firstOrCreate(['name' => $jl['name']], $jl);
        }

        // 3. Seed Gelombang Pendaftaran
        $gelombangs = [
            [
                'academic_year_id' => $tahunAjaran->id,
                'name' => 'Gelombang 1 Tahap 1',
                'start_date' => '2025-10-01',
                'end_date' => '2026-01-30',
                'quota' => 200,
                'status' => 'aktif',
            ],
            [
                'academic_year_id' => $tahunAjaran->id,
                'name' => 'Gelombang 1 Tahap 2',
                'start_date' => '2026-01-31',
                'end_date' => '2026-04-30',
                'quota' => 200,
                'status' => 'nonaktif',
            ],
            [
                'academic_year_id' => $tahunAjaran->id,
                'name' => 'Gelombang 2',
                'start_date' => '2026-04-04',
                'end_date' => '2026-06-12',
                'quota' => 200,
                'status' => 'nonaktif',
            ],
        ];

        foreach ($gelombangs as $g) {
            GelombangPendaftaran::updateOrCreate(
                ['academic_year_id' => $g['academic_year_id'], 'name' => $g['name']],
                $g
            );
        }

        // 4. Seed Jenis Dokumen
        $dokumens = [
            ['name' => 'Kartu Keluarga (KK)', 'is_required' => true],
            ['name' => 'Akta Kelahiran', 'is_required' => true],
            ['name' => 'Ijazah / Surat Keterangan Lulus (SKL)', 'is_required' => true],
            ['name' => 'Pas Foto 3x4 (Latar Merah)', 'is_required' => true],
            ['name' => 'Sertifikat Prestasi (Opsional)', 'is_required' => false],
        ];

        foreach ($dokumens as $d) {
            JenisDokumen::firstOrCreate(['name' => $d['name']], $d);
        }

        PengaturanSpmb::firstOrCreate(
            ['tahun_ajaran_id' => $tahunAjaran->id],
            [
                'nama_spmb' => 'SPMB SIMUPA SMK Muhammadiyah 4 Cileungsi Tahun Pelajaran 2026/2027',
                'tanggal_buka' => '2025-10-01',
                'tanggal_tutup' => '2026-06-12',
                'biaya_pendaftaran' => 150000,
                'maksimal_pilihan_jurusan' => 2,
                'status' => 'aktif',
            ]
        );
    }
}
