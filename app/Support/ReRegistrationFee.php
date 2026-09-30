<?php

namespace App\Support;

use App\Models\JenisTagihan;
use App\Models\Pendaftar;
use App\Models\TagihanPendaftar;
use App\Models\Jurusan;
use Illuminate\Support\Collection;

class ReRegistrationFee
{
    public static function optionsFor(Pendaftar $pendaftar, bool $includeChoices = false, bool $showAll = false): Collection
    {
        $pendaftar->loadMissing(['jurusan1', 'jurusan2', 'hasilSeleksi.major']);
        $selected = $pendaftar->hasilSeleksi?->major;
        $majors = $showAll
            ? Jurusan::where('status', 'aktif')->orderBy('name')->get()
            : ($selected
            ? collect([$selected])
            : ($includeChoices && $pendaftar->major_choice_1
                ? collect([$pendaftar->jurusan1, $pendaftar->jurusan2])->filter()
                : ($pendaftar->major_choice_1 ? collect([$pendaftar->jurusan1]) : Jurusan::where('status', 'aktif')->orderBy('name')->get())));
        $waveFees = $pendaftar->wave_id
            ? \App\Models\GelombangJurusan::where('gelombang_id', $pendaftar->wave_id)->get()->keyBy('jurusan_id')
            : collect();

        return $majors->filter()->unique('id')->map(function ($jurusan) use ($waveFees) {
            $waveFee = $waveFees->get($jurusan->id);
            return (object) [
                'jurusan' => $jurusan,
                'amount' => (float) ($waveFee?->biaya_masuk ?: $jurusan->biaya_masuk ?: 0),
                'items' => collect($waveFee?->rincian_biaya ?? [])
                    ->map(function ($item) {
                        $name = (string) ($item['name'] ?? '');
                        $item['category'] = trim((string) ($item['category'] ?? '')) ?: FeeCategory::for($name);

                        return $item;
                    })->values(),
            ];
        })->values();
    }

    public static function ensureBill(Pendaftar $pendaftar): ?TagihanPendaftar
    {
        $pendaftar->loadMissing(['hasilSeleksi.major', 'jurusan1']);
        $jurusan = $pendaftar->hasilSeleksi?->major ?? $pendaftar->jurusan1;
        $waveFee = $pendaftar->wave_id && $jurusan
            ? \App\Models\GelombangJurusan::query()
                ->where('gelombang_id', $pendaftar->wave_id)
                ->where('jurusan_id', $jurusan->id)
                ->first()
            : null;
        $breakdown = $waveFee?->rincian_biaya ?? [];
        $waveAmount = (float) ($waveFee?->biaya_masuk ?? 0);
        $amount = $waveAmount > 0 ? $waveAmount : (float) ($jurusan?->biaya_masuk ?? 0);

        if (! $jurusan || $amount <= 0) {
            return null;
        }

        $jenisTagihan = JenisTagihan::firstOrCreate(
            ['name' => 'Daftar Ulang - '.$jurusan->name],
            [
                'default_amount' => $amount,
                'description' => 'Biaya daftar ulang sesuai rincian jurusan dan gelombang.',
            ]
        );

        if ((float) $jenisTagihan->default_amount !== $amount) {
            $jenisTagihan->update(['default_amount' => $amount]);
        }

        $bill = TagihanPendaftar::firstOrCreate(
            ['applicant_id' => $pendaftar->id, 'bill_type_id' => $jenisTagihan->id],
            [
                'total_amount' => $amount,
                'paid_amount' => 0,
                'remaining_amount' => $amount,
                'due_date' => now()->addDays(14)->toDateString(),
                'status' => 'unpaid',
                'rincian_biaya' => $breakdown ?: null,
            ]
        );

        if ($bill->hasActiveCheckout() || $bill->transaksi()->where('status', 'pending')->exists()) {
            return $bill->load('jenisTagihan');
        }

        if ($bill->status !== 'paid' && (float) $bill->total_amount !== $amount) {
            $paidAmount = min((float) $bill->paid_amount, $amount);
            $bill->update([
                'total_amount' => $amount,
                'paid_amount' => $paidAmount,
                'remaining_amount' => max(0, $amount - $paidAmount),
                'status' => $paidAmount >= $amount ? 'paid' : ($paidAmount > 0 ? 'partial' : 'unpaid'),
                'rincian_biaya' => $breakdown ?: null,
            ]);
        }

        if ($bill->status !== 'paid' && $bill->rincian_biaya !== ($breakdown ?: null)) {
            $bill->update(['rincian_biaya' => $breakdown ?: null]);
        }

        return $bill->load('jenisTagihan');
    }
}
