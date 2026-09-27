<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sekolah_asal', function (Blueprint $table) {
            if (! Schema::hasColumn('sekolah_asal', 'school_address')) {
                $table->text('school_address')->nullable()->after('npsn');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sekolah_asal', function (Blueprint $table) {
            if (Schema::hasColumn('sekolah_asal', 'school_address')) {
                $table->dropColumn('school_address');
            }
        });
    }
};
