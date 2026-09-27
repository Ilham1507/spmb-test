<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verifikasi_perubahan_whatsapp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('pengguna')->cascadeOnDelete();
            $table->string('phone', 15)->unique();
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('last_sent_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verifikasi_perubahan_whatsapp');
    }
};
