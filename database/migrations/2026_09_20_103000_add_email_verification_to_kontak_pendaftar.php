<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kontak_pendaftar', function (Blueprint $table) {
            if (! Schema::hasColumn('kontak_pendaftar', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable()->after('email');
            }
            if (! Schema::hasColumn('kontak_pendaftar', 'email_verification_token')) {
                $table->string('email_verification_token', 80)->nullable()->unique()->after('email_verified_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('kontak_pendaftar', function (Blueprint $table) {
            $columns = array_filter(['email_verification_token', 'email_verified_at'], fn ($column) => Schema::hasColumn('kontak_pendaftar', $column));
            if ($columns) $table->dropColumn($columns);
        });
    }
};
