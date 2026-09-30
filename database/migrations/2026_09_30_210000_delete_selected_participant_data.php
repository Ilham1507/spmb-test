<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /** Permanently remove only the participant records explicitly selected by the administrator. */
    public function up(): void
    {
        if (! Schema::hasTable('pengguna') || ! Schema::hasTable('pendaftar')) {
            return;
        }

        // Include the spelling seen in the account and the spelling supplied
        // by the administrator, but do not use broad LIKE matching.
        $names = [
            'ilham sompe',
            'julia sari',
            'annabella evita indriyanti',
            'annabela evita indriyanti',
        ];
        $placeholders = implode(',', array_fill(0, count($names), '?'));
        $byName = fn ($query, string $column) => $query->whereRaw("LOWER(TRIM(`{$column}`)) IN ({$placeholders})", $names);

        $userIds = $byName(DB::table('pengguna'), 'name')->pluck('id')->all();
        $applicantIds = $userIds === []
            ? []
            : DB::table('pendaftar')->whereIn('user_id', $userIds)->pluck('id')->all();

        if (Schema::hasTable('biodata_pendaftar')) {
            $applicantIds = array_values(array_unique(array_merge(
                $applicantIds,
                $byName(DB::table('biodata_pendaftar'), 'full_name')->pluck('applicant_id')->all(),
            )));
        }

        if ($applicantIds !== []) {
            $userIds = array_values(array_unique(array_merge(
                $userIds,
                DB::table('pendaftar')->whereIn('id', $applicantIds)->whereNotNull('user_id')->pluck('user_id')->all(),
            )));
        }

        $billIds = $applicantIds !== [] && Schema::hasTable('tagihan_pendaftar')
            ? DB::table('tagihan_pendaftar')->whereIn('applicant_id', $applicantIds)->pluck('id')->all()
            : [];
        $transactionIds = $billIds !== [] && Schema::hasTable('transaksi_pembayaran')
            ? DB::table('transaksi_pembayaran')->whereIn('bill_id', $billIds)->pluck('id')->all()
            : [];

        // Remove uploaded evidence as well as its database record.
        if ($applicantIds !== [] && Schema::hasTable('dokumen_pendaftar')) {
            DB::table('dokumen_pendaftar')->whereIn('applicant_id', $applicantIds)->pluck('file_path')
                ->filter()->each(fn ($path) => Storage::disk('public')->delete($path));
        }
        if ($transactionIds !== [] && Schema::hasTable('transaksi_pembayaran')) {
            DB::table('transaksi_pembayaran')->whereIn('id', $transactionIds)->pluck('proof_file')
                ->filter()->each(function ($path): void {
                    Storage::disk('local')->delete($path);
                    Storage::disk('public')->delete($path);
                });
        }
        if ($transactionIds !== [] && Schema::hasTable('bukti_pembayaran')) {
            DB::table('bukti_pembayaran')->whereIn('transaksi_id', $transactionIds)->pluck('file_path')
                ->filter()->each(function ($path): void {
                    Storage::disk('local')->delete($path);
                    Storage::disk('public')->delete($path);
                });
        }
        if ($billIds !== [] && Schema::hasTable('riwayat_cicilan')) {
            DB::table('riwayat_cicilan')->whereIn('tagihan_id', $billIds)->pluck('bukti_transfer')
                ->filter()->each(function ($path): void {
                    Storage::disk('local')->delete($path);
                    Storage::disk('public')->delete($path);
                });
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            $database = DB::getDatabaseName();

            // Transaction children do not carry applicant_id themselves.
            if ($transactionIds !== []) {
                foreach (['transaction_id', 'transaksi_id'] as $column) {
                    $transactionTables = DB::table('information_schema.columns')
                        ->where('table_schema', $database)->where('column_name', $column)
                        ->pluck('TABLE_NAME')->unique()
                        ->reject(fn ($table) => $table === 'transaksi_pembayaran');
                    foreach ($transactionTables as $table) {
                        DB::table($table)->whereIn($column, $transactionIds)->delete();
                    }
                }
            }
            if ($billIds !== []) {
                foreach (['bill_id', 'tagihan_id'] as $column) {
                    $billTables = DB::table('information_schema.columns')
                        ->where('table_schema', $database)->where('column_name', $column)
                        ->pluck('TABLE_NAME')->unique()
                        ->reject(fn ($table) => in_array($table, ['tagihan_pendaftar', 'transaksi_pembayaran'], true));
                    foreach ($billTables as $table) {
                        DB::table($table)->whereIn($column, $billIds)->delete();
                    }
                }
                DB::table('transaksi_pembayaran')->whereIn('bill_id', $billIds)->delete();
            }

            if ($applicantIds !== []) {
                $applicantTables = DB::table('information_schema.columns')
                    ->where('table_schema', $database)
                    ->whereIn('column_name', ['applicant_id', 'pendaftar_id'])
                    ->pluck('TABLE_NAME')->unique()
                    ->reject(fn ($table) => in_array($table, ['pendaftar', 'pendaftars'], true));
                foreach ($applicantTables as $table) {
                    $column = Schema::hasColumn($table, 'applicant_id') ? 'applicant_id' : 'pendaftar_id';
                    DB::table($table)->whereIn($column, $applicantIds)->delete();
                }
                DB::table('pendaftar')->whereIn('id', $applicantIds)->delete();
            }

            // Visits and promotion interests can exist before an account is
            // created, so delete their explicitly named standalone records.
            foreach (['kunjungan_pendaftar', 'minat_promosi'] as $table) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, 'full_name')) {
                    $byName(DB::table($table), 'full_name')->delete();
                }
            }

            if ($userIds !== []) {
                $userTables = DB::table('information_schema.columns')
                    ->where('table_schema', $database)
                    ->whereIn('column_name', ['pengguna_id', 'user_id'])
                    ->pluck('TABLE_NAME')->unique()
                    ->reject(fn ($table) => in_array($table, ['pengguna', 'users', 'pendaftar'], true));
                foreach ($userTables as $table) {
                    $column = Schema::hasColumn($table, 'pengguna_id') ? 'pengguna_id' : 'user_id';
                    DB::table($table)->whereIn($column, $userIds)->delete();
                }
                DB::table('pengguna')->whereIn('id', $userIds)->delete();
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function down(): void
    {
        // Confirmed permanent deletion; the removed records cannot be restored.
    }
};
