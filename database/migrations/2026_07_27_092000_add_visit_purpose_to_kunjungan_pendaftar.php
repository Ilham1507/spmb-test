<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kunjungan_pendaftar', function (Blueprint $table) {
            $table->string('visit_purpose', 30)->default('information')->after('applicant_id');
        });
    }

    public function down(): void
    {
        Schema::table('kunjungan_pendaftar', function (Blueprint $table) {
            $table->dropColumn('visit_purpose');
        });
    }
};
