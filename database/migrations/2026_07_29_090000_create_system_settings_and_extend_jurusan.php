<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->string('group')->default('general')->index();
            $table->timestamps();
        });

        Schema::table('jurusan', function (Blueprint $table) {
            $table->decimal('biaya_masuk', 14, 2)->nullable()->after('quota');
            $table->string('logo_path')->nullable()->after('description');
        });

        $defaults = [
            'school_name' => 'SMK Muhammadiyah 4 Cileungsi',
            'school_short_name' => 'SMK Muhammadiyah 4',
            'portal_name' => 'SPMB Online',
            'brand_name' => 'SIMUPA',
            'accreditation' => 'B+',
            'school_address' => 'Jl. Cileungsi Kidul, Cileungsi, Bogor 16820, Jawa Barat',
            'contact_phone' => '085229056983',
            'operational_hours' => 'Senin-Sabtu, 07.00-16.00 WIB',
            'tagline' => 'Bekerja & Kuliah',
            'hero_title' => 'Wujudkan cita-citamu bersama SMK Muhammadiyah 4 Cileungsi.',
            'hero_description' => 'Bergabunglah dengan SIMUPA, Sekolah Inovasi Muhammadiyah 4, tempat calon siswa dipersiapkan untuk bekerja, kuliah, berwirausaha, dan berkarya di dunia nyata.',
            'profile_title' => 'Sekolah vokasi Islam unggulan di Bogor.',
            'profile_description' => 'SMK Muhammadiyah 4 Cileungsi memadukan pendidikan keahlian, karakter Islami, kelas industri, Teaching Factory, dan pembelajaran bahasa internasional agar siswa siap kerja, siap wirausaha, dan siap berkarya.',
            'footer_description' => 'Sekolah Inovasi Muhammadiyah 4: dari iman ke keahlian, dari sekolah ke dunia nyata.',
            'registration_cta' => 'Daftar sekarang dan raih masa depan gemilang.',
            'school_logo' => 'images/logo-sekolah.png',
        ];

        foreach ($defaults as $key => $value) {
            DB::table('system_settings')->insert([
                'key' => $key,
                'value' => $value,
                'group' => in_array($key, ['hero_title', 'hero_description', 'profile_title', 'profile_description', 'registration_cta']) ? 'landing' : 'identity',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $fees = ['KUL' => 5457000, 'DPB' => 5337000, 'LFKK' => 5457000, 'LPKC' => 5307000];
        foreach ($fees as $code => $fee) DB::table('jurusan')->where('code', $code)->update(['biaya_masuk' => $fee]);
    }

    public function down(): void
    {
        Schema::table('jurusan', fn (Blueprint $table) => $table->dropColumn(['biaya_masuk', 'logo_path']));
        Schema::dropIfExists('system_settings');
    }
};
