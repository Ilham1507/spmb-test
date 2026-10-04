<?php

namespace Tests\Unit;

use App\Models\Pendaftar;
use App\Models\BiodataPendaftar;
use App\Models\AlamatPendaftar;
use App\Models\DataAyah;
use App\Models\DataIbu;
use App\Models\KontakPendaftar;
use App\Models\SekolahAsal;
use App\Models\TahunAjaran;
use App\Models\Jurusan;
use App\Support\FormFieldCatalog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RegistrationPdfTest extends TestCase
{
    public function test_pdf_is_standalone_with_correct_year_and_fixed_receipt_style_footer(): void
    {
        Schema::create('system_settings', function ($table) {
            $table->string('key');
            $table->text('value')->nullable();
        });
        $student = new Pendaftar(['registration_number' => 'SPMB2028-UJI', 'registration_status' => 'submitted']);
        foreach (['biodata', 'alamat', 'dataAyah', 'dataIbu', 'dataWali', 'sekolahAsal', 'jurusan1', 'jurusan2', 'jalurPendaftaran', 'kontak', 'tahunAjaran', 'gelombangPendaftaran'] as $relation) {
            $student->setRelation($relation, null);
        }
        $student->setRelation('tahunAjaran', new TahunAjaran(['name' => '2027/2028']));
        $student->setRelation('jurusan1', new Jurusan(['name' => 'Desain dan Produksi Busana']));
        $student->setRelation('jurusan2', new Jurusan(['name' => 'Kuliner']));
        $student->setRelation('biodata', new BiodataPendaftar(['full_name' => 'SISWA CONTOH - BUKAN DATA ASLI', 'nisn' => '0000000000', 'gender' => 'L', 'birth_place' => 'Bogor', 'birth_date' => '2010-04-10', 'religion' => 'Islam', 'nik' => '0000000000000000', 'no_kk' => '0000000000000000']));
        $student->setRelation('alamat', new AlamatPendaftar(['address' => 'Jalan Contoh Nomor 10, Perumahan Contoh, Kecamatan Cileungsi', 'rt' => '001', 'rw' => '002', 'village' => 'Cileungsi Kidul', 'district' => 'Cileungsi', 'city' => 'Bogor', 'province' => 'Jawa Barat', 'postal_code' => '16820', 'distance_range' => '1001 - 3000 meter']));
        foreach (['dataAyah' => DataAyah::class, 'dataIbu' => DataIbu::class] as $relation => $class) {
            $student->setRelation($relation, new $class(['name' => 'Orang Tua Contoh', 'nik' => '0000000000000000', 'education' => 'SMA/Sederajat', 'occupation' => 'Wiraswasta', 'income' => '500 Ribu - 1 Juta', 'phone' => '080000000000']));
        }
        $student->setRelation('kontak', new KontakPendaftar(['phone' => '080000000000', 'email' => 'contoh@example.com', 'email_verified_at' => now()]));
        $student->setRelation('sekolahAsal', new SekolahAsal(['school_name' => 'SMP CONTOH CILEUNGSI', 'npsn' => '00000000', 'school_address' => 'Jalan Contoh, Kabupaten Bogor', 'graduation_year' => '2027']));
        $html = view('peserta.cetak.pdf', ['pendaftar' => $student, 'groups' => FormFieldCatalog::groups(), 'enabledFields' => FormFieldCatalog::keys(), 'settings' => ['school_name' => 'SMK Muhammadiyah 4 Cileungsi', 'school_address' => 'Cileungsi, Bogor']])->render();
        $this->assertStringContainsString('Tahun Pelajaran 2027/2028', $html);
        $this->assertStringContainsString('Menunggu pemeriksaan', $html);
        $this->assertStringContainsString('10 April 2010', $html);
        $this->assertStringContainsString('position: fixed; bottom: -10mm;', $html);
        $this->assertStringContainsString('background: #102d61; color: #fff;', $html);
        $this->assertStringNotContainsString('global-loading', $html);
        $this->assertStringNotContainsString('Ilham Sompe &amp; Team', $html);
        $pdf = Pdf::loadHTML($html)->setPaper('a4')->output();
        $this->assertStringStartsWith('%PDF-', $pdf);
        if ($directory = getenv('REGISTRATION_PDF_PREVIEW_DIR')) {
            file_put_contents($directory.'/Formulir Pendaftaran - CONTOH.pdf', $pdf);
        }
    }

    public function test_all_registration_download_links_bypass_navigation_loading(): void
    {
        foreach (['peserta/cetak/index', 'peserta/formulir/index', 'peserta/dashboard/index', 'panitia/pendaftar/show'] as $view) {
            $source = file_get_contents(resource_path('views/'.$view.'.blade.php'));
            preg_match_all('/<a\b[^>]*href="[^"\n]*(?:peserta\.pdf|pendaftar\.pdf|\$pdfUrl)[^"\n]*"[^>]*>/u', $source, $links);
            $this->assertNotEmpty($links[0], $view);
            foreach ($links[0] as $link) {
                $this->assertStringContainsString('data-no-loading', $link);
                $this->assertStringContainsString(' download', $link);
            }
        }
    }
}
