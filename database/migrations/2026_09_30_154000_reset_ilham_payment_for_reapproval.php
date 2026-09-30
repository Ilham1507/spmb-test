<?php

use App\Models\TagihanPendaftar;
use App\Models\TransaksiPembayaran;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /** Return the failed-notification transaction to the panitia approval queue. */
    public function up(): void
    {
        DB::transaction(function (): void {
            $transaction = TransaksiPembayaran::query()
                ->where('status', 'verified')
                ->whereHas('tagihan.pendaftar', fn ($query) => $query->where('registration_number', 'SPMB2028-0006'))
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $transaction) {
                Log::warning('Reset approval Ilham dilewati: transaksi terverifikasi tidak ditemukan.');
                return;
            }

            $bill = TagihanPendaftar::query()->lockForUpdate()->find($transaction->bill_id);
            if (! $bill) {
                Log::warning('Reset approval Ilham dilewati: tagihan tidak ditemukan.', ['transaction_id' => $transaction->id]);
                return;
            }

            $originalTotal = (float) ($transaction->bill_total_snapshot ?? $bill->total_amount + $transaction->discount_amount);
            $paid = max(0, (float) $bill->paid_amount - (float) $transaction->amount);
            $remaining = max(0, $originalTotal - $paid);

            $bill->update([
                'total_amount' => $originalTotal,
                'paid_amount' => $paid,
                'remaining_amount' => $remaining,
                'status' => $paid <= 0 ? 'unpaid' : 'partial',
            ]);

            $transaction->update([
                'status' => 'pending',
                'verified_by' => null,
                'verified_at' => null,
            ]);
        });
    }

    public function down(): void {}
};
