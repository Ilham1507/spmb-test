<?php

namespace App\Support;

use App\Models\TagihanPendaftar;
use App\Models\TransaksiPembayaran;
use Illuminate\Validation\ValidationException;

class VerifiedPayment
{
    // Call inside the approval transaction while holding the bill row lock.
    public static function apply(TagihanPendaftar $bill, TransaksiPembayaran $transaction): void
    {
        $discount = (float) ($transaction->discount_amount ?? 0);
        if ($discount > 0 && ((float) $bill->total_amount !== (float) $transaction->bill_total_snapshot || (float) $bill->paid_amount > 0)) {
            throw ValidationException::withMessages(['payment' => 'Tagihan berubah setelah bukti dikirim. Cocokkan nominal dan potongan terlebih dahulu.']);
        }
        $total = (float) $bill->total_amount - $discount;
        $paid = (float) $bill->paid_amount + (float) $transaction->amount;
        if ($paid > $total || $discount < 0 || (float) $transaction->amount <= 0) {
            throw ValidationException::withMessages(['payment' => 'Nominal pembayaran tidak cocok dengan sisa tagihan.']);
        }
        $bill->update(['total_amount' => $total, 'paid_amount' => $paid,
            'remaining_amount' => $total - $paid, 'status' => $paid >= $total ? 'paid' : 'partial']);
    }
}
