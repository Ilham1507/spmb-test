<?php

namespace App\Support;

use App\Models\JenisTagihan;
use App\Models\Pendaftar;
use App\Models\PengaturanSpmb;
use App\Models\TagihanPendaftar;

class RegistrationFee
{
    public static function billFor(?Pendaftar $pendaftar): ?TagihanPendaftar
    {
        if (!$pendaftar) {
            return null;
        }

        return TagihanPendaftar::with(['jenisTagihan', 'transaksi' => fn ($query) => $query->latest()])
            ->where('applicant_id', $pendaftar->id)
            ->whereHas('jenisTagihan', function ($query) {
                $query->where('name', 'like', '%formulir%')
                    ->orWhere('name', 'like', '%pendaftaran%');
            })
            ->orderBy('id')
            ->first();
    }

    public static function ensureBill(Pendaftar $pendaftar): TagihanPendaftar
    {
        $existing = self::billFor($pendaftar);

        if ($existing) {
            return $existing;
        }

        $amount = (float) (PengaturanSpmb::query()
            ->where('tahun_ajaran_id', $pendaftar->academic_year_id)
            ->latest('id')
            ->value('biaya_pendaftaran') ?? 0);

        $jenisTagihan = JenisTagihan::firstOrCreate(
            ['name' => 'Uang Formulir Pendaftaran'],
            [
                'default_amount' => $amount,
                'description' => 'Biaya formulir awal yang wajib dilunasi sebelum peserta mengisi formulir pendaftaran.',
            ]
        );

        return TagihanPendaftar::firstOrCreate(
            [
                'applicant_id' => $pendaftar->id,
                'bill_type_id' => $jenisTagihan->id,
            ],
            [
                'total_amount' => $amount,
                'paid_amount' => 0,
                'remaining_amount' => $amount,
                'due_date' => now()->addDays(7)->toDateString(),
                'status' => $amount > 0 ? 'unpaid' : 'paid',
            ]
        );
    }

    public static function isPaid(?TagihanPendaftar $tagihan): bool
    {
        if (!$tagihan) {
            return false;
        }

        return $tagihan->status === 'paid' || (float) $tagihan->remaining_amount <= 0;
    }

    public static function hasPendingVerification(?TagihanPendaftar $tagihan): bool
    {
        return (bool) $tagihan?->transaksi?->contains('status', 'pending');
    }
}
