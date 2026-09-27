<?php

namespace App\Support;

use App\Models\NomorPendaftaranCounter;
use App\Models\Pendaftar;
use App\Models\RiwayatNomorPendaftaran;
use App\Models\TahunAjaran;
use Illuminate\Support\Facades\DB;

class RegistrationNumber
{
    public static function ensure(Pendaftar $pendaftar): string
    {
        if ($pendaftar->registration_number) {
            return $pendaftar->registration_number;
        }

        return DB::transaction(function () use ($pendaftar) {
            $tahunAjaranId = $pendaftar->academic_year_id
                ?? TahunAjaran::where('is_active', true)->value('id')
                ?? TahunAjaran::query()->value('id');

            $counter = NomorPendaftaranCounter::where('tahun_ajaran_id', $tahunAjaranId)
                ->lockForUpdate()
                ->first();

            if (!$counter) {
                $lastNumber = Pendaftar::where('academic_year_id', $tahunAjaranId)
                    ->whereNotNull('registration_number')
                    ->pluck('registration_number')
                    ->map(fn ($number) => (int) substr((string) $number, -4))
                    ->max() ?? 0;

                $counter = NomorPendaftaranCounter::create([
                    'tahun_ajaran_id' => $tahunAjaranId,
                    'nomor_terakhir' => $lastNumber,
                ]);
            }

            $counter->nomor_terakhir = ((int) $counter->nomor_terakhir) + 1;
            $counter->save();

            $year = TahunAjaran::find($tahunAjaranId)?->name;
            // Nomor pendaftaran memakai tahun akhir, mis. 2026/2027 menjadi SPMB2027.
            preg_match_all('/\d{4}/', (string) $year, $matches);
            $yearCode = !empty($matches[0]) ? end($matches[0]) : date('Y');
            $number = 'SPMB' . $yearCode . '-' . str_pad((string) $counter->nomor_terakhir, 4, '0', STR_PAD_LEFT);

            $pendaftar->registration_number = $number;
            $pendaftar->save();

            RiwayatNomorPendaftaran::create([
                'pendaftar_id' => $pendaftar->id,
                'nomor_pendaftaran' => $number,
                'dibuat_pada' => now(),
            ]);

            return $number;
        });
    }
}

