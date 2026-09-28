<?php

namespace App\Support;

use App\Models\KunjunganPendaftar;
use App\Models\Pendaftar;

class KunjunganMatcher
{
    public static function candidateFor(Pendaftar $pendaftar): ?KunjunganPendaftar
    {
        if ($pendaftar->kunjungan()->exists()) {
            return null;
        }

        $normalizedName = FullNameNormalizer::normalize($pendaftar->user?->name);
        $phone = preg_replace('/\D+/', '', (string) $pendaftar->user?->phone) ?? '';
        if ($normalizedName === '' || $phone === '') {
            return null;
        }

        $candidates = KunjunganPendaftar::with(['penerima', 'referensiSekolah'])
            ->whereNull('applicant_id')
            ->where('normalized_full_name', $normalizedName)
            ->where('visitor_phone', $phone)
            ->whereDoesntHave('matchVerifications', fn ($query) => $query
                ->where('applicant_id', $pendaftar->id)
                ->where('status', 'dismissed'))
            ->latest('visited_at')
            ->limit(2)
            ->get();

        // Hubungkan otomatis hanya jika nama lengkap menghasilkan satu kandidat unik.
        return $candidates->count() === 1 ? $candidates->first() : null;
    }
}
