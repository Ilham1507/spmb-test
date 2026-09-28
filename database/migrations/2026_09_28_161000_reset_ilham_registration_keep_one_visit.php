<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reset the requested test registration while retaining one visit record so
     * the public registration flow can be tested again without duplicate data.
     */
    public function up(): void
    {
        $phone = '082293094817';
        if (! Schema::hasTable('kunjungan_pendaftar')) {
            return;
        }

        $keepVisit = DB::table('kunjungan_pendaftar')
            ->where('visitor_phone', $phone)
            ->orderByDesc('visited_at')->orderByDesc('id')->first();
        $visitIds = DB::table('kunjungan_pendaftar')->where('visitor_phone', $phone)->pluck('id')->all();
        $keepVisitId = $keepVisit?->id;

        $userIds = Schema::hasTable('pengguna')
            ? DB::table('pengguna')->where('phone', $phone)->pluck('id')->all()
            : [];
        $applicantIds = $userIds !== [] && Schema::hasTable('pendaftar')
            ? DB::table('pendaftar')->whereIn('user_id', $userIds)->pluck('id')->all()
            : [];
        $billIds = $applicantIds !== [] && Schema::hasTable('tagihan_pendaftar')
            ? DB::table('tagihan_pendaftar')->whereIn('applicant_id', $applicantIds)->pluck('id')->all()
            : [];

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            if (Schema::hasTable('kunjungan_match_verifications') && $visitIds !== []) {
                DB::table('kunjungan_match_verifications')->whereIn('visit_id', $visitIds)->delete();
            }
            if ($billIds !== []) {
                foreach (['payment_checkouts', 'audit_pembayaran', 'bukti_pembayaran', 'riwayat_cicilan', 'transaksi_pembayaran'] as $table) {
                    if (Schema::hasTable($table) && Schema::hasColumn($table, 'bill_id')) {
                        DB::table($table)->whereIn('bill_id', $billIds)->delete();
                    }
                }
                DB::table('tagihan_pendaftar')->whereIn('id', $billIds)->delete();
            }

            if ($keepVisitId) {
                DB::table('kunjungan_pendaftar')->where('id', $keepVisitId)->update(['applicant_id' => null]);
                DB::table('kunjungan_pendaftar')->whereIn('id', $visitIds)->where('id', '!=', $keepVisitId)->delete();
            }

            if ($applicantIds !== []) {
                $database = DB::getDatabaseName();
                $tables = DB::table('information_schema.columns')->selectRaw('TABLE_NAME as table_name')
                    ->where('table_schema', $database)->whereIn('column_name', ['applicant_id', 'pendaftar_id'])
                    ->pluck('table_name')->unique()
                    ->reject(fn ($table) => in_array($table, ['pendaftar', 'pendaftars', 'kunjungan_pendaftar', 'tagihan_pendaftar'], true));
                foreach ($tables as $table) {
                    $column = Schema::hasColumn($table, 'applicant_id') ? 'applicant_id' : 'pendaftar_id';
                    DB::table($table)->whereIn($column, $applicantIds)->delete();
                }
                DB::table('pendaftar')->whereIn('id', $applicantIds)->delete();
            }
            if ($userIds !== []) DB::table('pengguna')->whereIn('id', $userIds)->delete();
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function down(): void {}
};
