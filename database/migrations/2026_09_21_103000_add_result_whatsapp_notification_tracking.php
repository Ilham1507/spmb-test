<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hasil_seleksi', function (Blueprint $table) {
            $table->timestamp('whatsapp_result_notified_at')->nullable()->after('decided_at');
        });
    }

    public function down(): void
    {
        Schema::table('hasil_seleksi', function (Blueprint $table) {
            $table->dropColumn('whatsapp_result_notified_at');
        });
    }
};
