<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $setting = DB::table('system_settings')->where('key', 'landing_sections')->first();

        if (! $setting) {
            return;
        }

        $sections = json_decode($setting->value, true) ?: [];
        $sectionsById = collect($sections)->keyBy('id');

        $updates = [
            'hero' => [
                'eyebrow' => 'Sekolah vokasi Muhammadiyah',
                'title' => 'Sekolah vokasi untuk masa depanmu',
                'body' => 'Kenali jurusan, program unggulan, dan pengalaman belajar di SMK Muhammadiyah 4 Cileungsi.',
            ],
            'highlights' => [
                'eyebrow' => 'Kenapa SIMUPA?',
                'title' => 'Belajar dekat dengan dunia nyata',
                'body' => 'Informasi singkat yang penting untuk siswa dan orang tua.',
                'items' => [
                    ['title' => 'Kelas berbasis industri', 'body' => 'Belajar dengan praktik dan standar yang dekat dengan dunia kerja.'],
                    ['title' => 'Karakter Islami', 'body' => 'Membangun kompetensi sekaligus akhlak dan kemandirian siswa.'],
                    ['title' => 'Siap melangkah', 'body' => 'Bekal untuk bekerja, berwirausaha, atau melanjutkan pendidikan.'],
                ],
            ],
            'jurusan' => [
                'eyebrow' => 'Pilih jurusanmu',
                'title' => 'Target lulusan tiap jurusan',
                'body' => 'Lihat kompetensi dan peluang yang disiapkan di setiap jurusan.',
            ],
            'flow' => [
                'eyebrow' => 'Alur pendaftaran',
                'title' => 'Daftar dengan langkah yang jelas',
                'body' => 'Ikuti tahapan berikut dan pantau prosesnya dari akunmu.',
                'items' => [
                    ['title' => 'Buat akun', 'body' => 'Daftarkan akun calon siswa.'],
                    ['title' => 'Pilih jurusan', 'body' => 'Tentukan kompetensi keahlian yang diminati.'],
                    ['title' => 'Lengkapi data', 'body' => 'Isi data dan unggah dokumen yang diperlukan.'],
                    ['title' => 'Pantau proses', 'body' => 'Lihat status pendaftaran dari satu portal.'],
                ],
            ],
            'contact' => [
                'title' => 'Butuh bantuan? Hubungi panitia',
                'body' => 'Kami siap membantu menjawab pertanyaan siswa dan orang tua.',
            ],
            'cta' => [
                'eyebrow' => 'Mulai kenal sekolah',
                'title' => 'Temukan pilihan terbaik untuk masa depanmu',
                'body' => 'Lihat informasi sekolah dan daftar saat kamu sudah siap.',
            ],
        ];

        foreach ($updates as $id => $values) {
            if ($sectionsById->has($id)) {
                $sectionsById[$id] = array_merge($sectionsById[$id], $values);
            }
        }

        $customSections = [
            [
                'id' => 'profil-sekolah',
                'type' => 'custom',
                'enabled' => true,
                'layout' => 'full',
                'eyebrow' => 'Profil sekolah',
                'title' => 'Visi dan misi sekolah',
                'body' => 'Arah pendidikan SMK Muhammadiyah 4 Cileungsi untuk membentuk lulusan yang berkarakter dan kompeten.',
                'items' => [
                    ['title' => 'Visi', 'body' => 'Menjadi SMK unggulan yang menghasilkan lulusan beriman, kompeten, mandiri, dan siap menghadapi dunia kerja serta perkembangan global.'],
                    ['title' => 'Misi', 'body' => 'Menyelenggarakan pembelajaran vokasi berbasis industri, membangun karakter Islami, mengembangkan kewirausahaan, dan memperluas pengalaman belajar siswa.'],
                ],
            ],
            [
                'id' => 'program-unggulan',
                'type' => 'custom',
                'enabled' => true,
                'layout' => 'full',
                'eyebrow' => 'Program unggulan',
                'title' => 'Pengalaman belajar yang lebih luas',
                'body' => 'Program pilihan untuk memperkuat kompetensi, karakter, dan kesiapan siswa.',
                'items' => [
                    ['title' => 'Go International', 'body' => 'Membuka wawasan global dan membiasakan siswa berkomunikasi dalam bahasa Inggris.'],
                    ['title' => 'Boarding School', 'body' => 'Pilihan asrama dengan pembinaan kemandirian, kedisiplinan, dan karakter Islami.'],
                    ['title' => 'Teaching Factory', 'body' => 'Belajar melalui proses kerja nyata dengan suasana dan standar dunia industri.'],
                ],
            ],
        ];

        $result = [];
        $insertedProfile = false;
        foreach ($sections as $section) {
            if ($section['id'] === 'highlights') {
                $result[] = $sectionsById[$section['id']];
                $result[] = $customSections[0];
                $result[] = $customSections[1];
                $insertedProfile = true;
                continue;
            }

            $result[] = $sectionsById[$section['id']] ?? $section;
        }

        if (! $insertedProfile) {
            $result = array_merge([$customSections[0], $customSections[1]], $result);
        }

        DB::table('system_settings')
            ->where('key', 'landing_sections')
            ->update(['value' => json_encode(array_values($result), JSON_UNESCAPED_UNICODE)]);
    }

    public function down(): void
    {
        // Content-only migration; the administrator can edit landing sections from the panel.
    }
};
