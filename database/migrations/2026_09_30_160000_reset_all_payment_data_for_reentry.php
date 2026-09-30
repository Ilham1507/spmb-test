<?php

use App\Models\TagihanPendaftar;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * The school requested a clean payment retest: keep applicants and bills,
     * but remove every payment record and return every bill to its unpaid state.
     */
    public function up(): void
    {
        $proofFiles = [];

        DB::transaction(function () use (&$proofFiles): void {
            if (! Schema::hasTable('transaksi_pembayaran')) {
                return;
            }

            $transactions = DB::table('transaksi_pembayaran')->get([
                'id', 'bill_id', 'proof_file', 'bill_total_snapshot',
            ]);
            $proofFiles = $transactions->pluck('proof_file')->filter()->unique()->values()->all();
            $billTotals = $transactions
                ->groupBy('bill_id')
                ->map(fn ($records) => (float) $records->max('bill_total_snapshot'));

            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            try {
                foreach ([
                    'payment_checkouts', 'audit_pembayaran', 'bukti_pembayaran',
                    'log_keuangan', 'riwayat_cicilan', 'transaksi_pembayaran',
                ] as $table) {
                    if (Schema::hasTable($table)) {
                        DB::table($table)->delete();
                    }
                }
            } finally {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }

            TagihanPendaftar::query()->lockForUpdate()->get()->each(function (TagihanPendaftar $bill) use ($billTotals): void {
                $total = (float) ($billTotals->get($bill->id) ?: $bill->total_amount);
                $bill->update([
                    'total_amount' => $total,
                    'paid_amount' => 0,
                    'remaining_amount' => $total,
                    'status' => $total > 0 ? 'unpaid' : 'paid',
                ]);
            });
        });

        foreach ($proofFiles as $path) {
            foreach (['local', 'public'] as $disk) {
                if (Storage::disk($disk)->exists($path)) {
                    Storage::disk($disk)->delete($path);
                }
            }
        }
    }

    public function down(): void {}
};
