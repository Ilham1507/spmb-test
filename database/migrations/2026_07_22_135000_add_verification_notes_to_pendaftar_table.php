<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('pendaftar', 'verification_notes')) {
            Schema::table('pendaftar', function (Blueprint $table) {
                $table->text('verification_notes')->nullable()->after('registration_status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pendaftar', 'verification_notes')) {
            Schema::table('pendaftar', function (Blueprint $table) {
                $table->dropColumn('verification_notes');
            });
        }
    }
};
