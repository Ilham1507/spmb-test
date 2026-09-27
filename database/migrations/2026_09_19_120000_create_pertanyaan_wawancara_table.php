<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pertanyaan_wawancara')) {
            return;
        }

        Schema::create('pertanyaan_wawancara', function (Blueprint $table) {
            $table->id();
            $table->text('question');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        $now = now();
        DB::table('pertanyaan_wawancara')->insert([
            ['question' => 'Apa alasan utama memilih SMK Muhammadiyah 4 Cileungsi?', 'sort_order' => 1, 'status' => true, 'created_at' => $now, 'updated_at' => $now],
            ['question' => 'Bagaimana dukungan keluarga terhadap pilihan jurusan anak?', 'sort_order' => 2, 'status' => true, 'created_at' => $now, 'updated_at' => $now],
            ['question' => 'Bagaimana kebiasaan belajar dan tanggung jawab anak di rumah?', 'sort_order' => 3, 'status' => true, 'created_at' => $now, 'updated_at' => $now],
            ['question' => 'Adakah kebutuhan kesehatan atau pendampingan yang perlu sekolah ketahui?', 'sort_order' => 4, 'status' => true, 'created_at' => $now, 'updated_at' => $now],
            ['question' => 'Bagaimana komitmen keluarga untuk kehadiran, tata tertib, dan komunikasi dengan sekolah?', 'sort_order' => 5, 'status' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('pertanyaan_wawancara');
    }
};
