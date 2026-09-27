<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gelombang_jurusan', function (Blueprint $table) {
            $table->decimal('biaya_masuk', 14, 2)->nullable()->after('kuota');
        });
    }

    public function down(): void
    {
        Schema::table('gelombang_jurusan', function (Blueprint $table) {
            $table->dropColumn('biaya_masuk');
        });
    }
};
