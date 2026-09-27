<?php

namespace App\Support;

use App\Models\Pendaftar;
use App\Models\BiodataPendaftar;
use App\Models\KontakPendaftar;
use App\Models\TahunAjaran;
use App\Models\User;

class PendaftarSetup
{
    public static function getOrCreateFor(User $user): Pendaftar
    {
        $academicYearId = TahunAjaran::where('is_active', true)->value('id') ?? TahunAjaran::query()->value('id');

        $pendaftar = Pendaftar::firstOrCreate(
            ['user_id' => $user->id],
            [
                'academic_year_id' => $academicYearId,
                // wave_id wajib diisi di database, jadi tentukan gelombang
                // sebelum membuat draft pendaftar baru.
                'wave_id' => WaveAssignment::findForAcademicYear($academicYearId)?->id,
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
