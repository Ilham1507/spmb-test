<?php

namespace Tests\Unit;

use App\Services\{ExecutiveReportService, ExecutiveReportWorkbook};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Tests\TestCase;

class ExecutiveReportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => str_repeat('a', 32)]);
        foreach ([
            'tahun_ajaran' => ['name', 'start_date', 'is_active'], 'jurusan' => ['name', 'quota'],
            'jalur_pendaftaran' => ['name'], 'gelombang_pendaftaran' => ['name', 'quota', 'start_date'],
            'peran' => ['name'], 'pengguna' => ['name', 'phone', 'role_id'], 'pendaftar' => ['user_id', 'academic_year_id', 'registration_number', 'registration_status', 'major_choice_1', 'major_choice_2', 'admission_path_id', 'wave_id'],
            'biodata_pendaftar' => ['applicant_id', 'full_name'], 'sekolah_asal' => ['applicant_id', 'school_name'],
            'hasil_seleksi' => ['applicant_id', 'major_id', 'status'], 'tagihan_pendaftar' => ['applicant_id', 'bill_type_id', 'total_amount'],
            'jenis_tagihan' => ['name'], 'transaksi_pembayaran' => ['bill_id', 'amount', 'status', 'verified_by', 'treasurer_received_by'],
            'minat_promosi' => ['full_name', 'student_phone', 'school_name', 'major_interest', 'interested_major_id', 'submitted_at'],
            'tes_masuk' => ['test_name'], 'peserta_tes' => ['applicant_id', 'test_id', 'attendance', 'score'],
        ] as $name => $columns) {
            Schema::create($name, function (Blueprint $table) use ($columns) {
                $table->id();
                foreach ($columns as $column) $table->string($column)->nullable();
                $table->timestamps();
            });
        }
        DB::table('peran')->insert([['id' => 1, 'name' => 'peserta'], ['id' => 2, 'name' => 'panitia']]);
        DB::table('tahun_ajaran')->insert([['id' => 1, 'name' => '2026/2027', 'start_date' => '2026-07-01', 'is_active' => 0], ['id' => 2, 'name' => '2027/2028', 'start_date' => '2027-07-01', 'is_active' => 1]]);
        DB::table('jurusan')->insert([['id' => 1, 'name' => 'Kuliner', 'quota' => 40], ['id' => 2, 'name' => 'Busana', 'quota' => 40]]);
        DB::table('jalur_pendaftaran')->insert([['id' => 1, 'name' => 'Reguler'], ['id' => 2, 'name' => 'Beasiswa Prestasi']]);
        DB::table('gelombang_pendaftaran')->insert(['id' => 1, 'name' => 'Gelombang 1', 'quota' => 80, 'start_date' => '2026-09-01']);
        foreach ([
            [1, 2, 'accepted', 1, 2, '2026-10-01 00:00:00'],
            [2, 2, 're_registered', 2, 1, '2026-10-31 23:59:59'],
            [3, 2, 'draft', null, null, '2026-11-01 00:00:00'],
            [4, 1, 'accepted', 1, 1, '2026-10-05 08:00:00'],
        ] as [$id, $year, $status, $major, $path, $date]) {
            DB::table('pengguna')->insert(['id' => $id, 'role_id' => 1, 'name' => $id === 1 ? '=FAKE()' : 'Siswa '.$id, 'phone' => '081234567890']);
            DB::table('pendaftar')->insert(['id' => $id, 'user_id' => $id, 'academic_year_id' => $year, 'registration_number' => 'SPMB-'.$id, 'registration_status' => $status, 'major_choice_1' => $major, 'admission_path_id' => $path, 'wave_id' => 1, 'created_at' => $date]);
            DB::table('sekolah_asal')->insert(['applicant_id' => $id, 'school_name' => $id === 2 ? 'smp satu' : 'SMP Satu']);
        }
        DB::table('hasil_seleksi')->insert([['applicant_id' => 1, 'major_id' => 2, 'status' => 'accepted'], ['applicant_id' => 2, 'major_id' => 2, 'status' => 'accepted']]);
        DB::table('jenis_tagihan')->insert(['id' => 1, 'name' => 'Daftar ulang']);
        DB::table('tagihan_pendaftar')->insert(['id' => 1, 'applicant_id' => 1, 'bill_type_id' => 1, 'total_amount' => 1000]);
        DB::table('transaksi_pembayaran')->insert([['bill_id' => 1, 'amount' => 400, 'status' => 'verified'], ['bill_id' => 1, 'amount' => 600, 'status' => 'pending']]);
        DB::table('minat_promosi')->insert(['full_name' => 'Peminat baru', 'student_phone' => '08100000000', 'school_name' => 'SMP Dua', 'interested_major_id' => 1, 'major_interest' => 'Kuliner', 'submitted_at' => '2026-10-01 08:00:00']);
    }

    public function test_default_year_and_totals_reconcile(): void
    {
        $data = app(ExecutiveReportService::class)->data(Request::create('/'));
        $this->assertSame(3, $data['totalApplicants']);
        $this->assertSame(2, $data['accepted']);
        $this->assertSame(1, $data['reRegistered']);
        $this->assertSame(1, $data['scholarshipRows']->count());
        $this->assertSame(400.0, $data['verifiedRevenue']);
        $this->assertSame(600.0, $data['rows']->firstWhere('id', 1)['remaining']);
        $this->assertSame(3, $data['majorSummary']->sum('applicants'));
        $this->assertSame(2, $data['majorSummary']->firstWhere('name', 'Busana')->accepted);
        $this->assertSame(0, $data['majorSummary']->firstWhere('name', 'Kuliner')->accepted);
        $this->assertSame(1, $data['schoolSummary']->count());
    }

    public function test_promoted_staff_are_excluded_without_deleting_registration_history(): void
    {
        DB::table('pengguna')->where('id', 2)->update(['role_id' => 2]);
        $data = app(ExecutiveReportService::class)->data(Request::create('/'));
        $this->assertSame(2, $data['totalApplicants']);
        $this->assertSame(1, $data['accepted']);
        $this->assertSame(0, $data['reRegistered']);
        $this->assertSame(2, $data['majorSummary']->sum('applicants'));
        $this->assertDatabaseHas('pendaftar', ['id' => 2, 'registration_number' => 'SPMB-2']);
        DB::table('pengguna')->where('id', 2)->update(['role_id' => 1]);
        $this->assertSame(3, app(ExecutiveReportService::class)->data(Request::create('/'))['totalApplicants']);
    }

    public function test_month_includes_last_day_and_excludes_next_month(): void
    {
        $data = app(ExecutiveReportService::class)->data(Request::create('/?month=2026-10'));
        $this->assertSame(2, $data['totalApplicants']);
        $this->assertSame(1, $data['monthSummary']->count());
        $this->assertSame(2, $data['monthSummary']->first()->applicants);
    }

    public function test_filters_and_promotional_population_are_distinct(): void
    {
        $data = app(ExecutiveReportService::class)->data(Request::create('/?month=2026-10&path_id=2&status=accepted&major_id=1'));
        $this->assertSame(1, $data['totalApplicants']);
        $this->assertSame(1, $data['interests']->count());
        $this->assertSame('Peminat baru', $data['interests']->first()->full_name);
        $all = app(ExecutiveReportService::class)->data(Request::create('/?academic_year_id=0'));
        $this->assertSame(4, $all['totalApplicants']);
    }

    public function test_real_xlsx_has_all_sheets_and_literal_user_content(): void
    {
        $data = app(ExecutiveReportService::class)->data(Request::create('/?month=2026-10'));
        $book = app(ExecutiveReportWorkbook::class)->build($data);
        $this->assertSame(15, $book->getSheetCount());
        $this->assertSame('Ringkasan', $book->getSheet(0)->getTitle());
        $sheet = $book->getSheetByName('Pendaftar');
        $this->assertSame('C6', $sheet->getFreezePane());
        $this->assertSame('s', $sheet->getCell('C6')->getDataType());
        $this->assertSame('081234567890', $sheet->getCell('C6')->getValue());
        $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('B7')->getDataType());
        $this->assertSame('=FAKE()', $sheet->getCell('B7')->getValue());
        $this->assertSame(DataType::TYPE_NUMERIC, $sheet->getCell('E6')->getDataType());
        $path = tempnam(sys_get_temp_dir(), 'spmb-report-');
        try {
            IOFactory::createWriter($book, 'Xlsx')->save($path);
            $reopened = IOFactory::load($path);
            $this->assertSame(15, $reopened->getSheetCount());
            $this->assertSame('=FAKE()', $reopened->getSheetByName('Pendaftar')->getCell('B7')->getValue());
            $this->assertSame('s', $reopened->getSheetByName('Pendaftar')->getCell('B7')->getDataType());
        } finally { unlink($path); }
    }

    public function test_empty_report_still_exports_all_sheets(): void
    {
        $data = app(ExecutiveReportService::class)->data(Request::create('/?month=2030-01'));
        $this->assertSame(0, $data['totalApplicants']);
        $this->assertSame(0.0, $data['verifiedRevenue']);
        $this->assertSame(15, app(ExecutiveReportWorkbook::class)->build($data)->getSheetCount());
    }

    public function test_headmaster_can_view_and_export_but_participant_cannot(): void
    {
        $head = new \App\Models\User(['name' => 'Kepala Sekolah']);
        $head->id = 99;
        $head->setRelation('role', new \App\Models\Peran(['name' => 'kepala_sekolah']));
        if (getenv('SPMB_REPORT_PREVIEW')) {
            \Illuminate\Support\Facades\URL::forceScheme('http');
            \Illuminate\Support\Facades\URL::forceRootUrl('http://127.0.0.1:8766');
        }
        $response = $this->actingAs($head)->get('/kepala-sekolah/laporan-eksekutif?month=2026-10');
        $response->assertOk()->assertSee('Sekolah asal terbanyak')->assertSee('Minat jurusan saat promosi')->assertSee('Unduh Excel')->assertDontSee('(15 sheet)');
        // Attachment responses do not navigate or fire pageshow. Never leave
        // the global navigation overlay blocking the report after a download.
        foreach (['pdf', 'excel'] as $format) {
            $this->assertMatchesRegularExpression(
                '~<a href="[^"]*laporan-eksekutif/'.$format.'[^"]*" data-no-loading download~',
                $response->getContent()
            );
        }
        if (getenv('SPMB_REPORT_PREVIEW')) {
            file_put_contents(base_path('output/executive-report-preview.html'), $response->getContent());
        }
        $this->get('/kepala-sekolah/laporan-eksekutif/excel?month=2026-10')->assertOk()->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $head->setRelation('role', new \App\Models\Peran(['name' => 'peserta']));
        $this->actingAs($head)->get('/kepala-sekolah/laporan-eksekutif')->assertRedirect(route('login'));
    }
}
