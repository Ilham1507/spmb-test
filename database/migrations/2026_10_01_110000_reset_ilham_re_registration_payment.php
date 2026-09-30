<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Reset only Ilham Sompe's DU payment attempt so it can be submitted
     * again. The applicant, selected major, fee breakdown, and registration
     * fee are intentionally retained.
     */
    public function up(): void
    {
        if (! Schema::hasTable('tagihan_pendaftar') || ! Schema::hasTable('transaksi_pembayaran')) {
            return;
        }

        DB::transaction(function (): void {
            $billIds = DB::table('tagihan_pendaftar as bill')
                ->join('pendaftar as applicant', 'applicant.id', '=', 'bill.applicant_id')
                ->join('jenis_tagihan as type', 'type.id', '=', 'bill.bill_type_id')
                ->where('applicant.registration_number', 'SPMB2028-0010')
                ->where(function ($query): void {
                    $query->whereRaw('LOWER(type.name) LIKE ?', ['%daftar ulang%'])
                        ->orWhereRaw('LOWER(type.name) LIKE ?', ['%du%']);
                })
                ->lockForUpdate()
                ->pluck('bill.id');

            if ($billIds->isEmpty()) {
                return;
            }

            $transactionIds = DB::table('transaksi_pembayaran')
                ->whereIn('bill_id', $billIds)
                ->lockForUpdate()
                ->pluck('id');

            $proofFiles = DB::table('transaksi_pembayaran')
                ->whereIn('id', $transactionIds)
                ->whereNotNull('proof_file')
                ->pluck('proof_file');

            foreach (['audit_pembayaran', 'bukti_pembayaran', 'log_keuangan'] as $table) {
                if (Schema::hasTable($table) && $transactionIds->isNotEmpty()) {
                    DB::table($table)->whereIn('transaksi_id', $transactionIds)->delete();
                }
            }

            if (Schema::hasTable('payment_checkouts')) {
                DB::table('payment_checkouts')
                    ->whereIn('bill_id', $billIds)
                    ->orWhereIn('transaction_id', $transactionIds)
                    ->delete();
            }

            if (Schema::hasTable('riwayat_cicilan')) {
                DB::table('riwayat_cicilan')->whereIn('tagihan_id', $billIds)->delete();
            }

            if ($transactionIds->isNotEmpty()) {
                DB::table('transaksi_pembayaran')->whereIn('id', $transactionIds)->delete();
            }

            DB::table('tagihan_pendaftar')
                ->whereIn('id', $billIds)
                ->update([
                    'paid_amount' => 0,
                    'remaining_amount' => DB::raw('total_amount'),
                    'status' => 'unpaid',
                    'updated_at' => now(),
                ]);

            foreach ($proofFiles as $proofFile) {
                Storage::disk('local')->delete((string) $proofFile);
            }
        });
    }

    public function down(): void
    {
        // A user-requested payment reset cannot be reconstructed safely.
    }
};
