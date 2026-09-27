<?php

namespace App\Support;

use App\Models\GelombangPendaftaran;
use App\Models\Pendaftar;
use App\Models\TagihanPendaftar;

class WaveAssignment
{
    /**
     * Tentukan gelombang secara otomatis. Gelombang dikunci setelah uang
     * formulir lunas; sebelum lunas, pendaftar berpindah saat batas gelombang lewat.
     */
    public static function sync(Pendaftar $pendaftar, ?TagihanPendaftar $bill = null): Pendaftar
    {
        $bill ??= RegistrationFee::billFor($pendaftar);

        if (RegistrationFee::isPaid($bill)) {
            return $pendaftar;
        }

        $today = now()->toDateString();
        $current = $pendaftar->wave_id
            ? GelombangPendaftaran::find($pendaftar->wave_id)
            : null;

        // Perpanjangan tanggal oleh admin otomatis mempertahankan gelombang lama.
        if ($current && $current->end_date && $current->end_date >= $today) {
            self::syncDueDate($bill, $current);
            return $pendaftar;
        }

        $wave = self::findForAcademicYear($pendaftar->academic_year_id, $today);

        if ($wave && $pendaftar->wave_id !== $wave->id) {
            $pendaftar->forceFill(['wave_id' => $wave->id])->save();
        }

        if ($wave) {
            self::syncDueDate($bill, $wave);
        }

        return $pendaftar->refresh();
    }

    public static function findForAcademicYear(?int $academicYearId, ?string $today = null): ?GelombangPendaftaran
    {
        $today ??= now()->toDateString();
        $base = GelombangPendaftaran::query()
            ->when($academicYearId, fn ($query, $yearId) => $query->where('academic_year_id', $yearId));

        return (clone $base)
            ->where('status', 'aktif')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->orderBy('start_date')
            ->first()
            ?? (clone $base)
                ->where('status', 'aktif')
                ->whereDate('end_date', '>=', $today)
                ->orderBy('start_date')
                ->first()
            ?? (clone $base)
                ->whereDate('end_date', '>=', $today)
                ->orderBy('start_date')
                ->first()
            ?? (clone $base)
                ->where('status', 'aktif')
                ->latest('end_date')
                ->first();
    }

    private static function syncDueDate(?TagihanPendaftar $bill, GelombangPendaftaran $wave): void
    {
        if ($bill && $wave->end_date && $bill->status !== 'paid') {
            $bill->forceFill(['due_date' => $wave->end_date])->save();
        }
    }
}
