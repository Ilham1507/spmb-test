<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ukuran_seragam')) {
            Schema::create('ukuran_seragam', function (Blueprint $table) {
                $table->id();
                $table->string('code', 20)->unique();
                $table->string('name', 100);
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('status')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('item_pemeriksaan_kesehatan')) {
            Schema::create('item_pemeriksaan_kesehatan', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100)->unique();
                $table->string('unit', 30)->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('status')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('hasil_ukur_seragam_pendaftar')) {
            Schema::create('hasil_ukur_seragam_pendaftar', function (Blueprint $table) {
                $table->id();
                $table->foreignId('applicant_id')->constrained('pendaftar')->cascadeOnDelete();
                $table->foreignId('uniform_size_id')->nullable()->constrained('ukuran_seragam')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('hasil_pemeriksaan_kesehatan_pendaftar')) {
            Schema::create('hasil_pemeriksaan_kesehatan_pendaftar', function (Blueprint $table) {
                $table->id();
                $table->foreignId('applicant_id')->constrained('pendaftar')->cascadeOnDelete();
                $table->unsignedBigInteger('health_check_item_id');
                $table->string('result_value', 100)->nullable();
                $table->string('result_status', 50)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('health_check_item_id', 'health_item_result_fk')
                    ->references('id')
                    ->on('item_pemeriksaan_kesehatan')
                    ->cascadeOnDelete();
                $table->unique(['applicant_id', 'health_check_item_id'], 'health_result_unique');
            });
        }

        if (Schema::hasTable('peserta_tes') && !Schema::hasColumn('peserta_tes', 'schedule_id')) {
            Schema::table('peserta_tes', function (Blueprint $table) {
                $table->foreignId('schedule_id')->nullable()->after('test_id')->constrained('jadwal_spmb')->nullOnDelete();
            });
        }

        $now = now();
        $sizes = [
            ['code' => 'S', 'name' => 'S', 'sort_order' => 1],
            ['code' => 'M', 'name' => 'M', 'sort_order' => 2],
            ['code' => 'L', 'name' => 'L', 'sort_order' => 3],
            ['code' => 'XL', 'name' => 'XL', 'sort_order' => 4],
            ['code' => 'XXL', 'name' => 'XXL', 'sort_order' => 5],
            ['code' => 'CUSTOM', 'name' => 'Ukuran khusus', 'sort_order' => 6],
        ];

        foreach ($sizes as $size) {
            DB::table('ukuran_seragam')->updateOrInsert(
                ['code' => $size['code']],
                $size + ['status' => true, 'created_at' => $now, 'updated_at' => $now]
            );
        }

        $healthItems = [
            ['name' => 'Tensi', 'unit' => 'mmHg', 'sort_order' => 1],
            ['name' => 'Berat Badan', 'unit' => 'kg', 'sort_order' => 2],
            ['name' => 'Buta Warna', 'unit' => null, 'sort_order' => 3],
        ];

        foreach ($healthItems as $item) {
            DB::table('item_pemeriksaan_kesehatan')->updateOrInsert(
                ['name' => $item['name']],
                $item + ['status' => true, 'created_at' => $now, 'updated_at' => $now]
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('peserta_tes') && Schema::hasColumn('peserta_tes', 'schedule_id')) {
            Schema::table('peserta_tes', function (Blueprint $table) {
                $table->dropConstrainedForeignId('schedule_id');
            });
        }

        Schema::dropIfExists('hasil_pemeriksaan_kesehatan_pendaftar');
        Schema::dropIfExists('hasil_ukur_seragam_pendaftar');
        Schema::dropIfExists('item_pemeriksaan_kesehatan');
        Schema::dropIfExists('ukuran_seragam');
    }
};
