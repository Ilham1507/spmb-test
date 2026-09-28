<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $phone = '082293094817';
        $userIds = Schema::hasTable('pengguna')
            ? DB::table('pengguna')->where('phone', $phone)->pluck('id')->all()
            : [];
        $applicantIds = $userIds !== [] && Schema::hasTable('pendaftar')
            ? DB::table('pendaftar')->whereIn('user_id', $userIds)->pluck('id')->all()
            : [];

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            if (Schema::hasTable('kunjungan_pendaftar')) {
                DB::table('kunjungan_pendaftar')->where('visitor_phone', $phone)->delete();
                if ($applicantIds !== []) DB::table('kunjungan_pendaftar')->whereIn('applicant_id', $applicantIds)->delete();
            }

            if ($applicantIds !== []) {
                $database = DB::getDatabaseName();
                $tables = DB::table('information_schema.columns')
                    ->selectRaw('TABLE_NAME as table_name')
                    ->where('table_schema', $database)
                    ->whereIn('column_name', ['applicant_id', 'pendaftar_id'])
                    ->pluck('table_name')->unique()->reject(fn ($table) => in_array($table, ['pendaftar', 'pendaftars'], true));
                foreach ($tables as $table) DB::table($table)->whereIn(Schema::hasColumn($table, 'applicant_id') ? 'applicant_id' : 'pendaftar_id', $applicantIds)->delete();
                DB::table('pendaftar')->whereIn('id', $applicantIds)->delete();
            }

            if ($userIds !== []) DB::table('pengguna')->whereIn('id', $userIds)->delete();
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function down(): void {}
};
