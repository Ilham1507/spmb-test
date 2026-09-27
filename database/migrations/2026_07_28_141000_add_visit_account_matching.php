<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kunjungan_pendaftar', function (Blueprint $table) {
            $table->string('visitor_phone', 20)->nullable()->after('full_name');
            $table->string('normalized_full_name', 150)->nullable()->after('full_name')->index();
        });

        DB::table('kunjungan_pendaftar')->orderBy('id')->each(function ($visit) {
            $normalized = mb_strtolower(trim((string) $visit->full_name), 'UTF-8');
            $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;
            DB::table('kunjungan_pendaftar')->where('id', $visit->id)->update([
                'normalized_full_name' => $normalized,
            ]);
        });

        Schema::create('kunjungan_match_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained('pendaftar')->cascadeOnDelete();
            $table->foreignId('visit_id')->constrained('kunjungan_pendaftar')->cascadeOnDelete();
            $table->string('status', 20)->default('pending');
            $table->string('otp_hash')->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['applicant_id', 'visit_id'], 'visit_match_applicant_visit_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kunjungan_match_verifications');
        Schema::table('kunjungan_pendaftar', function (Blueprint $table) {
            $table->dropIndex(['normalized_full_name']);
            $table->dropColumn(['normalized_full_name', 'visitor_phone']);
        });
    }
};
