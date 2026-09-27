<?php

namespace App\Support;

use App\Models\PengaturanSpmb;
use App\Models\TahunAjaran;
use Carbon\Carbon;

class SpmbConfiguration
{
    public static function forAcademicYear(?int $academicYearId = null): ?PengaturanSpmb
    {
        $academicYearId ??= TahunAjaran::query()->where('is_active', true)->value('id');

        return $academicYearId
            ? PengaturanSpmb::query()->where('tahun_ajaran_id', $academicYearId)->latest('id')->first()
            : null;
    }

    public static function registrationIsOpen(): bool
    {
        $configuration = self::forAcademicYear();
        if (! $configuration || $configuration->status !== 'aktif') {
            return false;
        }

        $today = now()->startOfDay();
        return $today->betweenIncluded(Carbon::parse($configuration->tanggal_buka)->startOfDay(), Carbon::parse($configuration->tanggal_tutup)->endOfDay());
    }

    public static function registrationUnavailableMessage(): string
    {
        return self::forAcademicYear()?->status === 'ditutup'
            ? 'Pendaftaran sudah ditutup oleh panitia.'
            : 'Pendaftaran belum dibuka.';
    }
}
