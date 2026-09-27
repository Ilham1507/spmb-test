<?php

namespace App\Support;

use App\Models\TagihanPendaftar;

class PaymentQuote
{
    public static function forBill(TagihanPendaftar $bill, ?array $requestedItems = null, ?int $requestedAmount = null): array
    {
        $remaining = max(0, (int) round((float) $bill->total_amount - (float) $bill->paid_amount));
        $summary = PaymentSummary::forBill($bill);
        $breakdown = collect($bill->rincian_biaya ?? [])
            ->filter(fn ($item) => filled($item['name'] ?? null) && (float) ($item['amount'] ?? 0) > 0);
        $billName = strtolower((string) $bill->jenisTagihan?->name);
        $isReRegistration = str_contains($billName, 'daftar ulang') || str_contains($billName, 'du');
        $paidByItem = collect();
        $formulirCredit = ReRegistrationFormulirCredit::forBill($bill);
        if ($breakdown->isNotEmpty()) {
            $transactions = $bill->relationLoaded('transaksi')
                ? $bill->transaksi->where('status', 'verified')
                : $bill->transaksi()->where('status', 'verified')->get();
            $paidByItem = $transactions
                ->flatMap(fn ($transaction) => collect($transaction->selected_items ?? []))
                ->groupBy('name')
                ->map(fn ($items) => (float) $items->sum('amount'));
            $formulirCredit->each(fn ($amount, $name) => $paidByItem[$name] = max((float) ($paidByItem[$name] ?? 0), (float) $amount));
        }
        $items = $breakdown->map(function ($item) use ($paidByItem) {
            $name = (string) $item['name'];
            $remainingItem = max(0, (int) round((float) $item['amount'] - (float) ($paidByItem[$name] ?? 0)));
            return ['name' => $name, 'amount' => $remainingItem];
        })->filter(fn ($item) => $item['amount'] > 0)->values();

        // Formulir is the mandatory first component of daftar ulang. Until it
        // is settled, no other component may be selected or charged.
        if ($isReRegistration) {
            $formFee = $items->first(fn ($item) => str_contains(strtolower($item['name']), 'formulir'));
            if ($formFee) $items = collect([$formFee]);
        }

        // Old payments without per-item details cannot safely be allocated by the system.
        $requiresSelection = $items->isNotEmpty() && ! $summary['unallocated'];
        if ($requiresSelection) {
            $selectedNames = collect($requestedItems ?? [])->filter(fn ($item) => is_string($item))->unique()->values();
            $selected = $items->filter(fn ($item) => $selectedNames->contains($item['name']))->values();
            $validSelection = $selectedNames->isNotEmpty() && $selected->count() === $selectedNames->count();

            $maximumAmount = (int) $selected->sum('amount');
            $amount = $maximumAmount;
            if ($requestedAmount !== null) {
                $amount = $isReRegistration && $requestedAmount >= 1 && $requestedAmount <= $maximumAmount
                    ? $requestedAmount
                    : ($requestedAmount === $maximumAmount ? $requestedAmount : 0);
            }
            $allocated = collect();
            $unallocatedAmount = $amount;
            foreach ($selected as $item) {
                if ($unallocatedAmount <= 0) break;
                $portion = min((int) $item['amount'], $unallocatedAmount);
                $allocated->push(['name' => $item['name'], 'amount' => $portion]);
                $unallocatedAmount -= $portion;
            }

            $promotion = ['amount' => $amount, 'discount' => 0, 'event' => null];
            if ($validSelection && (float) $bill->paid_amount <= 0) {
                foreach ($allocated as $allocatedItem) {
                    $itemPromotion = PromotionEvent::apply(
                        (float) $allocatedItem['amount'],
                        $billName,
                        (int) $bill->applicant_id,
                        (string) $allocatedItem['name'],
                    );
                    if ((float) ($itemPromotion['discount'] ?? 0) > 0) {
                        $promotion['discount'] += (float) $itemPromotion['discount'];
                        $promotion['event'] ??= $itemPromotion['event'];
                    }
                }
                $promotion['amount'] = max(0, $amount - $promotion['discount']);
            }
            $formulirCreditDiscount = $bill->total_amount >= $breakdown->sum('amount')
                ? (int) $formulirCredit->sum()
                : 0;

            return [
                'amount' => $validSelection ? (int) round($promotion['amount']) : 0,
                // Kredit formulir dan promo dicatat sebagai pengurang tagihan
                // yang sama, sehingga sisa tagihan tetap tepat saat diverifikasi.
                'discount' => $formulirCreditDiscount + (int) round($promotion['discount'] ?? 0),
                'promotion_name' => $promotion['event']['name'] ?? null,
                'items' => $items->all(),
                'selected_items' => $validSelection && $amount > 0 ? $allocated->all() : null,
                'requires_selection' => true,
                'valid_selection' => $validSelection && $amount > 0,
            ];
        }

        $name = $billName;
        $promotion = (float) $bill->paid_amount <= 0 && (str_contains($name, 'formulir') || str_contains($name, 'pendaftaran') || str_contains($name, 'daftar ulang'))
            ? PromotionEvent::apply($remaining, $name, (int) $bill->applicant_id) : ['amount' => $remaining];
        $amount = min($remaining, max(0, (int) round($promotion['amount'])));
        return [
            'amount' => $amount,
            'discount' => $remaining - $amount,
            'promotion_name' => $promotion['event']['name'] ?? null,
            'items' => [],
            'selected_items' => null,
            'requires_selection' => false,
            'valid_selection' => true,
        ];
    }
}
