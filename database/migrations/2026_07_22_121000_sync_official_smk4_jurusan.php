<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $official = [
            [
                'code' => 'KUL',
                'name' => 'Kuliner',
                'description' => 'Program keahlian bidang pengolahan makanan, layanan boga, dan kewirausahaan kuliner.',
                'quota' => 50,
                'status' => 'aktif',
            ],
            [
                'code' => 'DPB',
                'name' => 'Desain dan Produksi Busana',
                'description' => 'Program keahlian desain, produksi busana, kreativitas fashion, dan keterampilan industri busana.',
                'quota' => 50,
                'status' => 'aktif',
            ],
            [
                'code' => 'LFKK',
                'name' => 'Layanan Kefarmasian Klinis dan Komunitas',
                'description' => 'Program keahlian layanan kefarmasian, farmasi klinis, komunitas, dan praktik layanan kesehatan.',
                'quota' => 50,
                'status' => 'aktif',
            ],
            [
                'code' => 'LPKC',
                'name' => 'Layanan Penunjang Keperawatan dan Caregiving',
                'description' => 'Program keahlian layanan keperawatan dasar, caregiving, dan pendampingan layanan kesehatan.',
                'quota' => 50,
                'status' => 'aktif',
            ],
        ];

        foreach ($official as $major) {
            DB::table('jurusan')->updateOrInsert(
                ['code' => $major['code']],
                array_merge($major, [
                    'updated_at' => now(),
                    'created_at' => now(),
                ])
            );
        }

        $officialIds = DB::table('jurusan')
            ->whereIn('code', collect($official)->pluck('code')->all())
            ->pluck('id');
        $fallbackMajorId = DB::table('jurusan')->where('code', 'KUL')->value('id') ?? $officialIds->first();

        DB::table('pendaftar')
            ->whereNotNull('major_choice_1')
            ->whereNotIn('major_choice_1', $officialIds)
            ->update(['major_choice_1' => $fallbackMajorId]);

        DB::table('pendaftar')
            ->whereNotNull('major_choice_2')
            ->whereNotIn('major_choice_2', $officialIds)
            ->update(['major_choice_2' => null]);

        DB::table('jurusan')
            ->whereNotIn('code', collect($official)->pluck('code')->all())
            ->delete();
    }

    public function down(): void
    {
        // Data master jurusan resmi tidak dikembalikan ke jurusan lama.
    }
};
