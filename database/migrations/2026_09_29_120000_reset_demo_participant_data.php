<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reset only operational test data so the public registration flow can be
     * tested from a clean state. Staff accounts and all master configuration
     * (fees, majors, schedules, settings) remain untouched.
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
            // Public promotion submissions and visit records are test data.
            foreach (['minat_promosi', 'kunjungan_match_verifications', 'kunjungan_pendaftar'] as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->delete();
                }
            }

            // Payments are participant-owned; keeping them would cause a new
            // applicant with the same details to look as though they had paid.
            foreach (['payment_checkouts', 'audit_pembayaran', 'bukti_pembayaran', 'riwayat_cicilan', 'transaksi_pembayaran', 'tagihan_pendaftar'] as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->delete();
                }
            }

            $database = DB::getDatabaseName();
            $applicantTables = DB::table('information_schema.columns')
                ->selectRaw('TABLE_NAME as table_name')
                ->where('table_schema', $database)
                ->whereIn('column_name', ['applicant_id', 'pendaftar_id'])
                ->pluck('table_name')
                ->unique()
                ->reject(fn ($table) => in_array($table, ['pendaftar', 'pendaftars'], true));

            foreach ($applicantTables as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->delete();
                }
            }

            foreach (['pendaftar', 'pendaftars'] as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->delete();
                }
            }

            if ($participantIds !== []) {
                $userTables = DB::table('information_schema.columns')
                    ->selectRaw('TABLE_NAME as table_name')
                    ->where('table_schema', $database)
                    ->whereIn('column_name', ['pengguna_id', 'user_id'])
                    ->pluck('table_name')
                    ->unique()
                    ->reject(fn ($table) => in_array($table, ['pengguna', 'users'], true));

                foreach ($userTables as $table) {
                    if (! Schema::hasTable($table)) {
                        continue;
                    }

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
        // Deliberate test-data reset: deleted records cannot be restored by rollback.
    }
};
