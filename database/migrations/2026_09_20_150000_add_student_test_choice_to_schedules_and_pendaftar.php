<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jadwal_spmb', function (Blueprint $table) {
            $table->boolean('available_for_student_selection')->default(false)->after('keterangan');
        });

        DB::table('jadwal_spmb')->where('kegiatan', 'like', '%Tes SPMB%')
            ->update(['available_for_student_selection' => true]);

        Schema::table('pendaftar', function (Blueprint $table) {
            $table->foreignId('preferred_test_schedule_id')->nullable()->after('admission_path_id')
                ->constrained('jadwal_spmb')->nullOnDelete();
            $table->timestamp('preferred_test_selected_at')->nullable()->after('preferred_test_schedule_id');
        });
    }

    public function down(): void
    {
        Schema::table('pendaftar', function (Blueprint $table) {
            $table->dropForeign(['preferred_test_schedule_id']);
            $table->dropColumn(['preferred_test_schedule_id', 'preferred_test_selected_at']);
        });
        Schema::table('jadwal_spmb', function (Blueprint $table) {
            $table->dropColumn('available_for_student_selection');
        });
    }
};
