<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $academicYearId = DB::table('tahun_ajaran')->where('is_active', true)->value('id')
            ?? DB::table('tahun_ajaran')->value('id');
        $sessions = [
            ['Januari', '2027-01-09 08:00:00', '2027-01-09 16:00:00'],
            ['Februari', '2027-02-13 08:00:00', '2027-02-13 16:00:00'],
            ['Maret', '2027-03-13 08:00:00', '2027-03-13 16:00:00'],
            ['April', '2027-04-10 08:00:00', '2027-04-10 16:00:00'],
            ['Mei', '2027-05-08 08:00:00', '2027-05-08 16:00:00'],
            ['Juni', '2027-06-12 08:00:00', '2027-06-12 16:00:00'],
        ];

        foreach ($sessions as [$month, $start, $end]) {
            $existing = DB::table('jadwal_spmb')->where('kegiatan', 'like', "%{$month}%")->first();
            $data = [
                'tahun_ajaran_id' => $academicYearId,
                'kegiatan' => "Tes SPMB Terpadu - {$month}",
                'tanggal_mulai' => $start,
                'tanggal_selesai' => $end,
                'keterangan' => 'Tes masuk SPMB, hadir 30 menit sebelum sesi dimulai.',
                'available_for_student_selection' => true,
                'updated_at' => now(),
            ];
            if ($existing) {
                DB::table('jadwal_spmb')->where('id', $existing->id)->update($data);
            } else {
                $data['created_at'] = now();
                DB::table('jadwal_spmb')->insert($data);
            }
        }

        DB::table('pendaftar')->where('registration_number', 'like', 'SPMB2026-%')
            ->update(['registration_number' => DB::raw("REPLACE(registration_number, 'SPMB2026-', 'SPMB2027-')")]);
        if (Schema::hasTable('riwayat_nomor_pendaftaran')) {
            DB::table('riwayat_nomor_pendaftaran')->where('nomor_pendaftaran', 'like', 'SPMB2026-%')
                ->update(['nomor_pendaftaran' => DB::raw("REPLACE(nomor_pendaftaran, 'SPMB2026-', 'SPMB2027-')")]);
        }
    }

    public function down(): void
    {
        DB::table('pendaftar')->where('registration_number', 'like', 'SPMB2027-%')
            ->update(['registration_number' => DB::raw("REPLACE(registration_number, 'SPMB2027-', 'SPMB2026-')")]);
        if (Schema::hasTable('riwayat_nomor_pendaftaran')) {
            DB::table('riwayat_nomor_pendaftaran')->where('nomor_pendaftaran', 'like', 'SPMB2027-%')
                ->update(['nomor_pendaftaran' => DB::raw("REPLACE(nomor_pendaftaran, 'SPMB2027-', 'SPMB2026-')")]);
        }
    }
};
