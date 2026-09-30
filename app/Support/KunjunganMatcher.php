<?php

namespace App\Support;

use App\Models\KunjunganPendaftar;
use App\Models\Pendaftar;

class KunjunganMatcher
{
    /**
     * Find a visit only when the candidate's full name and WhatsApp number
     * both match. A single field is not enough to link a visit automatically.
     */
    public static function automaticMatch(string $name, string $phone): ?KunjunganPendaftar
    {
        $normalizedName = FullNameNormalizer::normalize($name);
        $phone = preg_replace('/\D+/', '', $phone) ?? '';

        if ($normalizedName === '' || ! preg_match('/^08[0-9]{8,13}$/', $phone)) {
            return null;
        }

        $candidates = KunjunganPendaftar::query()
            ->whereNull('applicant_id')
            ->where('normalized_full_name', $normalizedName)
            ->where(fn ($query) => $query
                ->where('visitor_phone', $phone)
                ->orWhere('parent_phone', $phone))
            ->latest('visited_at')
            ->limit(2)
            ->get();

        return $candidates->count() === 1 ? $candidates->first() : null;
    }

    /** Link an unambiguous visit without asking the participant to choose it. */
    public static function linkFor(Pendaftar $pendaftar): ?KunjunganPendaftar
    {
        if ($pendaftar->kunjungan()->exists()) {
            return null;
        }

        $visit = self::automaticMatch(
            (string) $pendaftar->user?->name,
            (string) $pendaftar->user?->phone,
        );

        if (! $visit) {
            return null;
        }

        $linked = KunjunganPendaftar::query()
            ->whereKey($visit->id)
            ->whereNull('applicant_id')
            ->update(['applicant_id' => $pendaftar->id]);

        return $linked ? $visit : null;
    }

    public static function candidateFor(Pendaftar $pendaftar): ?KunjunganPendaftar
    {
        if ($pendaftar->kunjungan()->exists()) {
            return null;
        }

        return self::automaticMatch(
            (string) $pendaftar->user?->name,
            (string) $pendaftar->user?->phone,
        );
    }

    public static function matchedContact(KunjunganPendaftar $kunjungan, ?string $accountPhone): string
    {
        $phone = preg_replace('/\D+/', '', (string) $accountPhone) ?? '';

        return preg_replace('/\D+/', '', (string) $kunjungan->parent_phone) === $phone
            ? 'Nomor WhatsApp orang tua/wali'
            : 'Nomor WhatsApp siswa';
    }
}
