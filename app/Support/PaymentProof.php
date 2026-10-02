<?php

namespace App\Support;

use App\Models\TransaksiPembayaran;

class PaymentProof
{
    public static function isReRegistration(TransaksiPembayaran $transaction): bool
    {
        $name = strtolower((string) $transaction->tagihan?->jenisTagihan?->name);

        return str_contains($name, 'daftar ulang') || (bool) preg_match('/(^|\s)du(\s|$)/', $name);
    }

    public static function filename(TransaksiPembayaran $transaction): string
    {
        $kind = self::isReRegistration($transaction) ? 'Daftar Ulang' : 'Formulir';
        $number = $transaction->tagihan?->pendaftar?->registration_number ?: $transaction->id;
        $number = preg_replace('/[\\\\\/:*?"<>|\x00-\x1F]/', '-', (string) $number);

        return 'Bukti Pembayaran '.$kind.' - '.$number.'.pdf';
    }
}
