<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $guarded = ['id'];

    public static function values(): array
    {
        return Cache::remember('system_settings.values', 3600, fn () => static::query()->pluck('value', 'key')->all());
    }

    public static function putMany(array $values, string $group): void
    {
        foreach ($values as $key => $value) {
            static::query()->updateOrCreate(['key' => $key], ['value' => $value, 'group' => $group]);
        }
        Cache::forget('system_settings.values');
    }

    public static function defaults(): array
    {
        return [
            'school_name' => 'SMK Muhammadiyah 4 Cileungsi', 'school_short_name' => 'SMK Muhammadiyah 4',
            'portal_name' => 'SPMB Online', 'brand_name' => 'SIMUPA', 'accreditation' => 'B+',
            'school_address' => 'Jl. Cileungsi Kidul, Cileungsi, Bogor 16820, Jawa Barat',
            'contact_phone' => '085229056983', 'operational_hours' => 'Senin-Sabtu, 07.00-16.00 WIB',
            'tagline' => 'Bekerja & Kuliah', 'school_logo' => 'images/logo-sekolah.png', 'letterhead_path' => null,
        ];
    }

    public static function publicValues(): array
    {
        return array_merge(static::defaults(), static::values());
    }

    public static function landingSections(): array
    {
        $saved = json_decode(static::values()['landing_sections'] ?? '', true);
        if (is_array($saved) && $saved) {
            return collect($saved)->map(function (array $section) {
                $known = ['hero', 'highlights', 'jurusan', 'flow', 'contact', 'cta'];
                $section['type'] = $section['type'] ?? (in_array($section['id'] ?? '', $known, true) ? $section['id'] : 'custom');
                return $section;
            })->values()->all();
        }

        return [
            ['id'=>'hero','enabled'=>true,'layout'=>'full','eyebrow'=>'Sekolah vokasi Muhammadiyah','title'=>'Siapkan masa depanmu dari sekolah vokasi','body'=>'Kenali jurusan, program unggulan, dan pengalaman belajar di SMK Muhammadiyah 4 Cileungsi.','items'=>[]],
            ['id'=>'highlights','enabled'=>true,'layout'=>'full','eyebrow'=>'Kenapa SIMUPA?','title'=>'Belajar dekat dengan dunia nyata','body'=>'Informasi singkat yang penting untuk siswa dan orang tua.','items'=>['Kelas industri|Belajar dengan standar dan kebutuhan dunia kerja','Karakter Islami|Tumbuh dengan adab, disiplin, dan tanggung jawab','Siap masa depan|Dipersiapkan untuk kerja, kuliah, dan berwirausaha']],
            ['id'=>'profil-sekolah','enabled'=>true,'layout'=>'full','eyebrow'=>'Profil sekolah','title'=>'Visi dan misi sekolah','body'=>'Arah pendidikan SMK Muhammadiyah 4 Cileungsi untuk membentuk lulusan yang berkarakter dan kompeten.','items'=>['Visi|Menjadi SMK unggulan yang menghasilkan lulusan beriman, kompeten, mandiri, dan siap menghadapi dunia kerja serta perkembangan global.','Misi|Menyelenggarakan pembelajaran vokasi berbasis industri, membangun karakter Islami, mengembangkan kewirausahaan, dan memperluas pengalaman belajar siswa.']],
            ['id'=>'program-unggulan','enabled'=>true,'layout'=>'full','eyebrow'=>'Program unggulan','title'=>'Pengalaman belajar yang lebih luas','body'=>'Program yang membantu siswa berkembang di sekolah dan siap menghadapi dunia global.','items'=>['Go International|Mengenal wawasan global dan berkomunikasi dalam bahasa Inggris','Asrama|Lingkungan tinggal yang mendukung kemandirian dan pembinaan karakter','Teaching Factory|Belajar melalui praktik kerja nyata dengan standar industri']],
            ['id'=>'jurusan','enabled'=>true,'layout'=>'full','eyebrow'=>'Pilih jurusanmu','title'=>'Target lulusan tiap jurusan','body'=>'Lihat kompetensi dan peluang yang disiapkan di setiap jurusan.','items'=>[]],
            ['id'=>'flow','enabled'=>true,'layout'=>'full','eyebrow'=>'Alur pendaftaran','title'=>'Daftar dengan langkah yang jelas','body'=>'Ikuti tahapan berikut dan pantau prosesnya dari akunmu.','items'=>['Buat akun|Daftar dengan nomor WhatsApp aktif','Bayar formulir|Pilih metode pembayaran yang tersedia','Lengkapi data|Isi data diri dan formulir aktif','Upload dokumen|Kirim berkas sesuai persyaratan','Ikuti tes|Lihat jadwal dan hasil seleksi']],
            ['id'=>'contact','enabled'=>true,'layout'=>'half','eyebrow'=>'Butuh bantuan?','title'=>'Tanya panitia','body'=>'Hubungi sekolah jika ada bagian yang belum jelas.','items'=>[]],
            ['id'=>'cta','enabled'=>true,'layout'=>'half','eyebrow'=>'Siap mulai?','title'=>'Buat akun dan mulai perjalananmu','body'=>'Prosesnya singkat dan progresmu tersimpan otomatis.','items'=>[]],
        ];
    }
}
