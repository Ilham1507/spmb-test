<?php

use App\Models\TagihanPendaftar;
use App\Models\TransaksiPembayaran;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Remove only the latest test payments for Ilham's registered account. */
    public function up(): void
    {
        DB::transaction(function (): void {
            $transactions = TransaksiPembayaran::query()
                ->whereHas('tagihan.pendaftar', fn ($query) => $query->where('registration_number', 'SPMB2028-0006'))
                ->lockForUpdate()
                ->get();

            $billIds = $transactions->pluck('bill_id')->filter()->unique();
            $transactions->each->delete();

            TagihanPendaftar::query()
                ->whereIn('id', $billIds)
                ->lockForUpdate()
                ->get()
                ->each(function (TagihanPendaftar $bill): void {
                    $remaining = max(0, (float) $bill->total_amount);
                    $bill->update([
                        'paid_amount' => 0,
                        'remaining_amount' => $remaining,
                        'status' => $remaining > 0 ? 'unpaid' : 'paid',
                    ]);
                });
        });
    }

    public function down(): void {}
};
