<?php

namespace App\Support;

use App\Models\TagihanPendaftar;

class PaymentSummary
{
    public static function forBill(TagihanPendaftar $bill): array
    {
        $transactions = $bill->transaksi->sortByDesc(fn ($trx) => ($trx->payment_date ?? $trx->created_at ?? '').sprintf('%012d', $trx->id));
        $verified = $transactions->where('status', 'verified');
        $pending = $transactions->where('status', 'pending');
        $formulirCredit = ReRegistrationFormulirCredit::forBill($bill);
        $unallocated = $verified->contains(fn ($trx) => empty($trx->selected_items)) && $bill->status !== 'paid';
        $items = collect($bill->rincian_biaya ?? [])->map(function ($item) use ($bill, $verified, $pending, $unallocated, $formulirCredit) {
            $name = (string) ($item['name'] ?? 'Biaya');
            $matches = fn ($trx) => collect($trx->selected_items ?? [])->contains('name', $name);
            $paid = max((float) ($formulirCredit[$name] ?? 0), $verified->filter($matches)->sum(fn ($trx) => collect($trx->selected_items ?? [])->where('name', $name)->sum('amount')));
            $amount = (float) ($item['amount'] ?? 0);
            $status = match (true) {
                $bill->status === 'paid', $amount > 0 && $paid >= $amount => 'Lunas',
                $pending->contains($matches) => 'Menunggu dicek',
                $paid > 0 => 'Dibayar sebagian',
                $unallocated => 'Perlu dicocokkan',
                default => 'Belum dibayar',
            };

            return [
                'name' => $name,
                'category' => trim((string) ($item['category'] ?? '')) ?: 'Lainnya',
                'amount' => $amount,
                'status' => $status,
            ];
        });

        return ['items' => $items, 'last' => $verified->first(), 'pending' => $pending->sum('amount'), 'unallocated' => $unallocated];
    }
}
