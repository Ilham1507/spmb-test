<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pendaftar', function (Blueprint $table) {
            $table->string('correction_status')->nullable()->after('verification_notes');
            $table->timestamp('correction_submitted_at')->nullable()->after('correction_status');
        });

        DB::table('pendaftar')
            ->whereNotNull('verification_notes')
            ->where('verification_notes', '!=', '')
            ->update(['correction_status' => 'requested']);
    }

    public function down(): void
    {
        Schema::table('pendaftar', function (Blueprint $table) {
            $table->dropColumn(['correction_status', 'correction_submitted_at']);
        });
    }
};
