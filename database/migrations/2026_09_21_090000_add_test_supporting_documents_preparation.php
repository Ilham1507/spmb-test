<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $key = 'test_preparation_items';
        $saved = json_decode((string) DB::table('system_settings')->where('key', $key)->value('value'), true);
        $saved = is_array($saved) ? $saved : [
            ['title' => 'HP & internet', 'body' => 'Bawa HP yang cukup baterai dan pastikan internet dapat digunakan.'],
            ['title' => 'Berpakaian rapi', 'body' => 'Gunakan pakaian yang rapi dan sopan saat hadir di sekolah.'],
            ['title' => 'Bersama orang tua/wali', 'body' => 'Datang bersama orang tua atau wali untuk mengikuti proses tes.'],
        ];
        if (! collect($saved)->contains(fn ($item) => strcasecmp((string) ($item['title'] ?? ''), 'Berkas pendukung') === 0)) {
            $saved[] = ['title' => 'Berkas pendukung', 'body' => 'Bawa Kartu Keluarga, Akta Kelahiran, dan sertifikat prestasi jika ada.'];
        }
        DB::table('system_settings')->updateOrInsert(['key' => $key], ['value' => json_encode($saved, JSON_UNESCAPED_UNICODE), 'group' => 'test', 'updated_at' => now(), 'created_at' => now()]);
    }
    public function down(): void {}
};
