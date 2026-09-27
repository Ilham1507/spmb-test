<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kunjungan_pendaftar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->nullable()->constrained('pendaftar')->nullOnDelete();
            $table->string('full_name');
            $table->string('origin_school');
            $table->string('major_interest');
            $table->dateTime('visited_at');
            $table->foreignId('received_by')->constrained('pengguna')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['visited_at', 'received_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kunjungan_pendaftar');
    }
};
