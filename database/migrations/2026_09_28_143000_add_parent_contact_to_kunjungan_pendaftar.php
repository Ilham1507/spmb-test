<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kunjungan_pendaftar', function (Blueprint $table) {
            $table->string('parent_name')->nullable()->after('visitor_phone');
            $table->string('parent_phone')->nullable()->after('parent_name');
        });
    }

    public function down(): void
    {
        Schema::table('kunjungan_pendaftar', function (Blueprint $table) {
            $table->dropColumn(['parent_name', 'parent_phone']);
        });
    }
};
