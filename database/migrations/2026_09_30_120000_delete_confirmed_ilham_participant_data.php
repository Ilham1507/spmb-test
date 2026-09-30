<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove the two participant test accounts explicitly confirmed by the
     * administrator. Staff, master data, and every other participant remain
     * untouched.
     */
    public function up(): void
    {
        $phone = '082293094817';
        $registrationNumbers = ['SPMB2028-0004', 'SPMB2028-0005'];

        if (! Schema::hasTable('pengguna') || ! Schema::hasTable('pendaftar')) {
            return;
        }

        $userIds = DB::table('pengguna')
            ->where('phone', $phone)
            ->whereIn('name', ['Ilham Sompe', 'Sompe'])
            ->pluck('id')
            ->all();

        $applicantIds = DB::table('pendaftar')
            ->whereIn('registration_number', $registrationNumbers)
            ->orWhereIn('user_id', $userIds)
            ->pluck('id')
            ->all();

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            // These records can exist before or independently of a completed
            // registration, so remove them by the confirmed WhatsApp number.
            foreach (['kunjungan_pendaftar' => 'visitor_phone', 'minat_promosi' => 'student_phone'] as $table => $column) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                    DB::table($table)->where($column, $phone)->delete();
                }
            }

            if ($applicantIds !== []) {
                $database = DB::getDatabaseName();
                $tables = DB::table('information_schema.columns')
                    ->selectRaw('TABLE_NAME as table_name')
                    ->where('table_schema', $database)
                    ->whereIn('column_name', ['applicant_id', 'pendaftar_id'])
                    ->pluck('table_name')
                    ->unique()
                    ->reject(fn (string $table) => in_array($table, ['pendaftar', 'pendaftars'], true));

                foreach ($tables as $table) {
                    $column = Schema::hasColumn($table, 'applicant_id') ? 'applicant_id' : 'pendaftar_id';
                    DB::table($table)->whereIn($column, $applicantIds)->delete();
                }

                DB::table('pendaftar')->whereIn('id', $applicantIds)->delete();
            }

            if ($userIds !== []) {
                DB::table('pengguna')->whereIn('id', $userIds)->delete();
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function down(): void
    {
        // This is a confirmed permanent deletion and cannot be restored by rollback.
    }
};
