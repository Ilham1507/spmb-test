<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cbt_access_sessions')) {
            Schema::create('cbt_access_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('applicant_id')->constrained('pendaftar')->cascadeOnDelete();
                $table->foreignId('opened_by')->nullable()->constrained('pengguna')->nullOnDelete();
                $table->timestamp('opened_at')->nullable();
                $table->timestamp('closed_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->string('location_note')->nullable();
                $table->string('status', 30)->default('open');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('jawaban_cbt_pendaftar')) {
            Schema::create('jawaban_cbt_pendaftar', function (Blueprint $table) {
                $table->id();
                $table->foreignId('applicant_id')->constrained('pendaftar')->cascadeOnDelete();
                $table->foreignId('question_id')->constrained('pertanyaan_cbt')->cascadeOnDelete();
                $table->string('answer', 1)->nullable();
                $table->boolean('is_correct')->default(false);
                $table->timestamps();

                $table->unique(['applicant_id', 'question_id'], 'cbt_answer_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('jawaban_cbt_pendaftar');
        Schema::dropIfExists('cbt_access_sessions');
    }
};
