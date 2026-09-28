<?php

namespace App\Support;

use App\Models\Pendaftar;
use App\Models\BiodataPendaftar;
use App\Models\KontakPendaftar;
use App\Models\TahunAjaran;
use App\Models\GelombangPendaftaran;
use App\Models\User;

class PendaftarSetup
{
    public static function getOrCreateFor(User $user): Pendaftar
    {
        $academicYearId = TahunAjaran::where('is_active', true)->value('id') ?? TahunAjaran::query()->value('id');

        // wave_id is mandatory. Prefer the active wave, then a configured wave
        // for the year; create a safe draft wave if an administrator has not
        // configured one yet so a newly registered participant never gets 500.
        $wave = WaveAssignment::findForAcademicYear($academicYearId)
            ?? GelombangPendaftaran::query()->when($academicYearId, fn ($query) => $query->where('academic_year_id', $academicYearId))->latest('end_date')->first()
            ?? GelombangPendaftaran::query()->latest('end_date')->first();
        if (! $wave && $academicYearId) {
            $wave = GelombangPendaftaran::create([
                'academic_year_id' => $academicYearId,
                'name' => 'Gelombang Otomatis',
                'start_date' => now()->toDateString(),
                'end_date' => now()->addYear()->toDateString(),
                'quota' => 0,
                'status' => 'aktif',
            ]);
        }
        if (! $wave) {
            throw new \RuntimeException('Tahun ajaran atau gelombang pendaftaran belum dikonfigurasi.');
        }

        $pendaftar = Pendaftar::firstOrCreate(
            ['user_id' => $user->id],
            [
                'academic_year_id' => $academicYearId,
                // wave_id wajib diisi di database, jadi tentukan gelombang
                // sebelum membuat draft pendaftar baru.
                'wave_id' => $wave->id,
                'admission_path_id' => null,
                'major_choice_1' => null,
                'registration_status' => 'draft',
            ]
        );

        BiodataPendaftar::updateOrCreate(
            ['applicant_id' => $pendaftar->id],
            ['full_name' => $user->name]
        );

        KontakPendaftar::updateOrCreate(
            ['applicant_id' => $pendaftar->id],
            ['phone' => $user->phone]
        );

        $bill = RegistrationFee::ensureBill($pendaftar);
        WaveAssignment::sync($pendaftar, $bill);
        RegistrationNumber::ensure($pendaftar);

        return $pendaftar;
    }
}
