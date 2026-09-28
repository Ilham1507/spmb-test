<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Clear test participants and all participant-owned records. Staff accounts
     * (admin, panitia, kepala sekolah, bendahara) and master configuration stay.
     */
    public function up(): void
    {
        $protectedRoles = ['admin', 'panitia', 'kepala_sekolah', 'bendahara'];
        $protectedRoleIds = Schema::hasTable('peran')
            ? DB::table('peran')->whereIn('name', $protectedRoles)->pluck('id')->all()
            : [];
        $participantIds = Schema::hasTable('pengguna')
            ? DB::table('pengguna')->when($protectedRoleIds !== [], fn ($query) => $query->whereNotIn('role_id', $protectedRoleIds))->pluck('id')->all()
            : [];

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            // Financial records are not master data and must not survive a participant reset.
            foreach (['payment_checkouts', 'audit_pembayaran', 'bukti_pembayaran', 'log_keuangan', 'riwayat_cicilan', 'transaksi_pembayaran', 'tagihan_pendaftar'] as $table) {
                if (Schema::hasTable($table)) DB::table($table)->delete();
            }

            $database = DB::getDatabaseName();
            $applicantTables = DB::table('information_schema.columns')
                ->where('table_schema', $database)
                ->whereIn('column_name', ['applicant_id', 'pendaftar_id'])
                ->pluck('table_name')
                ->unique()
                ->reject(fn ($table) => in_array($table, ['pendaftar', 'pendaftars'], true));
            foreach ($applicantTables as $table) {
                if (Schema::hasTable($table)) DB::table($table)->delete();
            }

            foreach (['pendaftar', 'pendaftars'] as $table) {
                if (Schema::hasTable($table)) DB::table($table)->delete();
            }

            if ($participantIds !== []) {
                $userTables = DB::table('information_schema.columns')
                    ->where('table_schema', $database)
                    ->whereIn('column_name', ['pengguna_id', 'user_id'])
                    ->pluck('table_name')
                    ->unique()
                    ->reject(fn ($table) => in_array($table, ['pengguna', 'users'], true));
                foreach ($userTables as $table) {
                    if (! Schema::hasTable($table)) continue;
                    $column = Schema::hasColumn($table, 'pengguna_id') ? 'pengguna_id' : 'user_id';
                    DB::table($table)->whereIn($column, $participantIds)->delete();
                }
                DB::table('pengguna')->whereIn('id', $participantIds)->delete();
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function down(): void
    {
        // Participant test data is intentionally not recoverable from a migration rollback.
    }
};
