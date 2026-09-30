<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('gelombang_jurusan')) {
            Schema::create('gelombang_jurusan', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('gelombang_id');
                $table->unsignedBigInteger('jurusan_id');
                $table->integer('kuota')->default(0);
                $table->decimal('biaya_masuk', 14, 2)->nullable();
                $table->json('rincian_biaya')->nullable();
                $table->timestamps();
                $table->unique(['gelombang_id', 'jurusan_id']);
            });

            return;
        }

        Schema::table('gelombang_jurusan', function (Blueprint $table) {
            if (! Schema::hasColumn('gelombang_jurusan', 'biaya_masuk')) {
                $table->decimal('biaya_masuk', 14, 2)->nullable();
            }
            if (! Schema::hasColumn('gelombang_jurusan', 'rincian_biaya')) {
                $table->json('rincian_biaya')->nullable();
            }
        });
    }

    public function down(): void
    {
        // This repair is intentionally non-destructive for live fee data.
    }
};
