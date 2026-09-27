<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alamat_pendaftar', function (Blueprint $table) {
            if (!Schema::hasColumn('alamat_pendaftar', 'distance_range')) {
                $table->string('distance_range', 50)->nullable()->after('distance_to_school');
            }
        });
    }

    public function down(): void
    {
        Schema::table('alamat_pendaftar', function (Blueprint $table) {
            if (Schema::hasColumn('alamat_pendaftar', 'distance_range')) {
                $table->dropColumn('distance_range');
            }
        });
    }
};
