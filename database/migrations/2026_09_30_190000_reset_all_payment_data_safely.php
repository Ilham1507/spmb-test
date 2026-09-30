<?php

use App\Models\TagihanPendaftar;
use App\Models\TransaksiPembayaran;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Clear every recorded payment for a clean school retest while preserving
     * user, applicant, visit, bill, and fee configuration data.
     */
    public function up(): void
    {
        if (! Schema::hasTable('transaksi_pembayaran')) {
            return;
        }

        DB::transaction(function (): void {
            // Delete children first so this migration works with the existing
            // MySQL foreign keys and never needs global FK checks.
            foreach (['payment_checkouts', 'audit_pembayaran', 'bukti_pembayaran', 'log_keuangan', 'riwayat_cicilan'] as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->delete();
                }
            }

            TransaksiPembayaran::query()->delete();

            TagihanPendaftar::query()->lockForUpdate()->get()->each(function (TagihanPendaftar $bill): void {
                $total = max(0, (float) $bill->total_amount);
                $bill->update([
                    'paid_amount' => 0,
                    'remaining_amount' => $total,
                    'status' => $total > 0 ? 'unpaid' : 'paid',
                ]);
            });
        });
    }

    public function down(): void {}
};
