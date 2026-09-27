<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hasil_seleksi', function (Blueprint $table) {
            $table->foreignId('decided_by')->nullable()->after('notes')->constrained('pengguna')->nullOnDelete();
            $table->timestamp('decided_at')->nullable()->after('decided_by');
        });

        DB::table('hasil_seleksi')->orderBy('id')->each(function ($result) {
            $history = DB::table('riwayat_status_pendaftar')
                ->where('pendaftar_id', $result->applicant_id)
                ->where('status_baru', $result->status)
                ->latest('id')
                ->first();

            DB::table('hasil_seleksi')->where('id', $result->id)->update([
                'decided_by' => $history?->diubah_oleh,
                'decided_at' => $history?->created_at ?? $result->announcement_date,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('hasil_seleksi', function (Blueprint $table) {
            $table->dropConstrainedForeignId('decided_by');
            $table->dropColumn('decided_at');
        });
    }
};
