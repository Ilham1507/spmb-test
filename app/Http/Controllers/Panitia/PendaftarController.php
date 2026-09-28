<?php

namespace App\Http\Controllers\Panitia;

use App\Http\Controllers\Controller;
use App\Models\Pendaftar;
use App\Models\RiwayatStatusPendaftar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Support\RegistrationFee;
use App\Support\FormFieldCatalog;
use App\Support\Pagination;
use App\Models\SystemSetting;
use App\Services\WhatsappCloudApiService;
use Illuminate\Support\Facades\Log;
use Throwable;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PendaftarController extends Controller
{
    public function index(Request $request)
    {
        $pendaftars = $this->filteredPendaftarQuery($request)
            ->with(['biodata', 'jurusan1', 'gelombangPendaftaran'])
            ->latest()
            ->paginate(Pagination::perPage())
            ->withQueryString();

        return view('panitia.pendaftar.index', compact('pendaftars'));
    }

    public function export(Request $request)
    {
        $pendaftars = $this->filteredPendaftarQuery($request)
            ->with([
                'user', 'biodata', 'alamat', 'dataAyah', 'dataIbu', 'dataWali',
                'sekolahAsal', 'jurusan1', 'jurusan2', 'jalurPendaftaran',
                'gelombangPendaftaran', 'kontak', 'dokumenPendaftars.jenisDokumen', 'preferredTestSchedule',
                'hasilSeleksi.major', 'pesertaTes.tes', 'healthChecks.item', 'uniformMeasurement.size',
            ])
            ->latest()
            ->get();

        $spreadsheet = new Spreadsheet();
        $dataSheet = $spreadsheet->getActiveSheet();
        $dataSheet->setTitle('Data Pendaftar');

        $configuredFields = [];
        $groups = FormFieldCatalog::groups();
        $formOrder = ['Biodata', 'Alamat', 'Ayah', 'Ibu', 'Wali', 'Kontak', 'Sekolah Asal', 'Pilihan Jurusan', 'Dokumen Pendukung'];
        foreach ($formOrder as $group) {
            foreach ($groups[$group] ?? [] as $key => $label) {
                $configuredFields[] = compact('group', 'key', 'label');
            }
        }
        foreach (array_diff(array_keys($groups), $formOrder) as $group) {
            foreach ($groups[$group] as $key => $label) {
                $configuredFields[] = compact('group', 'key', 'label');
            }
        }

        $enabledFields = FormFieldCatalog::enabled();
        $headerGroups = [[
            'label' => 'Data Pendaftaran',
            'fields' => ['No. Pendaftaran'],
        ]];
        foreach ($formOrder as $group) {
            $fields = array_values(array_filter($configuredFields, fn (array $field) => $field['group'] === $group));
            if ($fields) {
                $headerGroups[] = [
                    'label' => $group,
                    'fields' => array_map(
                        fn (array $field) => $field['label'] . (in_array($field['key'], $enabledFields, true) ? '' : ' (Tidak aktif)'),
                        $fields
                    ),
                ];
            }
        }
        foreach (array_diff(array_keys($groups), $formOrder) as $group) {
            $fields = array_values(array_filter($configuredFields, fn (array $field) => $field['group'] === $group));
            if ($fields) {
                $headerGroups[] = [
                    'label' => $group,
                    'fields' => array_map(
                        fn (array $field) => $field['label'] . (in_array($field['key'], $enabledFields, true) ? '' : ' (Tidak aktif)'),
                        $fields
                    ),
                ];
            }
        }
        $headerGroups = array_merge($headerGroups, [
            ['label' => 'Proses Pendaftaran', 'fields' => ['Status Formulir', 'Status Revisi', 'Gelombang', 'Jalur Pendaftaran', 'Jadwal Tes Dipilih']],
            ['label' => 'Pilihan Pendaftaran', 'fields' => ['Jurusan Pilihan 1', 'Jurusan Pilihan 2']],
            ['label' => 'Hasil Seleksi', 'fields' => ['Status Seleksi', 'Jurusan Diterima', 'Catatan Seleksi']],
            ['label' => 'Data Sistem', 'fields' => ['Tanggal Pendaftaran', 'Terakhir Diperbarui']],
        ]);

        $groupHeader = [];
        $fieldHeader = [];
        $column = 1;
        foreach ($headerGroups as $headerGroup) {
            $count = count($headerGroup['fields']);
            $startColumn = Coordinate::stringFromColumnIndex($column);
            $endColumn = Coordinate::stringFromColumnIndex($column + $count - 1);
            $groupHeader = array_merge($groupHeader, array_fill(0, $count, $headerGroup['label']));
            $fieldHeader = array_merge($fieldHeader, $headerGroup['fields']);
            if ($count > 1) {
                $dataSheet->mergeCells($startColumn . '1:' . $endColumn . '1');
            }
            $column += $count;
        }
        $headers = $fieldHeader;
        $dataSheet->fromArray([$groupHeader, $fieldHeader], null, 'A1');

        $row = 3;
        foreach ($pendaftars as $pendaftar) {
            $selection = $pendaftar->hasilSeleksi;
            $values = [$pendaftar->registration_number];
            foreach ($configuredFields as $field) {
                $values[] = $this->formFieldExportValue($pendaftar, $field['key']);
            }
            $values = array_merge($values, [
                $this->registrationStatusLabel($pendaftar->registration_status),
                $this->correctionStatusLabel($pendaftar->correction_status),
                $pendaftar->gelombangPendaftaran?->name,
                $pendaftar->jalurPendaftaran?->name,
                $pendaftar->preferredTestSchedule?->kegiatan,
                $pendaftar->jurusan1?->name,
                $pendaftar->jurusan2?->name,
                $this->selectionStatusLabel($selection?->status),
                $selection?->major?->name,
                $selection?->notes,
                $pendaftar->created_at?->format('d-m-Y H:i'),
                $pendaftar->updated_at?->format('d-m-Y H:i'),
            ]);

            $this->writeExportRow($dataSheet, $row++, $values);
        }
        $this->formatExportSheet($dataSheet, count($headers), max($row - 1, 2), 2);

        $testSheet = $spreadsheet->createSheet();
        $testSheet->setTitle('Hasil Tes');
        $testHeaders = ['No. Pendaftaran', 'Nama Lengkap', 'Jenis Tes', 'Tanggal Tes', 'Jadwal Tes', 'Kehadiran', 'Nilai/Hasil', 'Status Hasil', 'Catatan'];
        $testSheet->fromArray([$testHeaders], null, 'A1');
        $testRow = 2;

        foreach ($pendaftars as $pendaftar) {
            $base = [$pendaftar->registration_number, $pendaftar->biodata?->full_name];
            $hasResult = false;
            foreach ($pendaftar->pesertaTes as $result) {
                $hasResult = true;
                $this->writeExportRow($testSheet, $testRow++, [
                    ...$base,
                    $result->tes?->test_name ?? 'Tes SPMB',
                    $result->tes?->test_date ? \Illuminate\Support\Carbon::parse($result->tes->test_date)->format('d-m-Y') : '',
                    $result->schedule_id ? 'Jadwal #' . $result->schedule_id : '',
                    $result->attendance ? 'Hadir' : 'Tidak hadir',
                    $result->score ?? '', '', $result->notes,
                ]);
            }
            foreach ($pendaftar->healthChecks as $result) {
                $hasResult = true;
                $this->writeExportRow($testSheet, $testRow++, [
                    ...$base, 'Tes Kesehatan - ' . ($result->item?->name ?? 'Pemeriksaan'), '', '',
                    'Hadir', $result->result_value, $result->result_status, $result->notes,
                ]);
            }
            if ($pendaftar->uniformMeasurement) {
                $hasResult = true;
                $this->writeExportRow($testSheet, $testRow++, [
                    ...$base, 'Tes Ukuran Seragam', '', '', 'Hadir', $pendaftar->uniformMeasurement->size?->name, '', $pendaftar->uniformMeasurement->notes,
                ]);
            }
            if (! $hasResult) {
                $this->writeExportRow($testSheet, $testRow++, [...$base, 'Belum ada hasil tes', '', '', '', '', '', '']);
            }
        }
        $this->formatExportSheet($testSheet, count($testHeaders), max($testRow - 1, 1));

        $filename = 'data-pendaftar-spmb-' . now()->format('Ymd-His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            IOFactory::createWriter($spreadsheet, 'Xlsx')->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function show(Pendaftar $pendaftar)
    {
        $pendaftar->load([
            'user.loginHistory', 'biodata', 'alamat', 'dataAyah', 'dataIbu', 'dataWali',
            'sekolahAsal', 'jurusan1', 'jurusan2', 'jalurPendaftaran',
            'gelombangPendaftaran', 'dokumenPendaftars.jenisDokumen', 'kontak', 'preferredTestSchedule',
            'pesertaTes.tes', 'healthChecks.item', 'uniformMeasurement.size',
        ]);

        $registrationFeeBill = RegistrationFee::billFor($pendaftar);
        $registrationFeePaid = RegistrationFee::isPaid($registrationFeeBill);

        return view('panitia.pendaftar.show', compact('pendaftar', 'registrationFeeBill', 'registrationFeePaid'))
            ->with('requiredStatuses', FormFieldCatalog::requiredStatuses($pendaftar));
    }

    public function cetak(Pendaftar $pendaftar)
    {
        $pendaftar->load(['biodata', 'alamat', 'dataAyah', 'dataIbu', 'dataWali', 'sekolahAsal', 'jurusan1', 'jurusan2', 'jalurPendaftaran', 'kontak']);
        return view('peserta.cetak.index', ['pendaftar' => $pendaftar, 'groups' => FormFieldCatalog::groups(), 'enabledFields' => FormFieldCatalog::enabled(), 'settings' => SystemSetting::publicValues(), 'backRoute' => route($this->routeName('pendaftar.show'), $pendaftar)]);
    }

    public function pdf(Pendaftar $pendaftar)
    {
        $view = $this->cetak($pendaftar);
        $data = $view->getData();
        $settings = $data['settings'] ?? [];
        $letterheadPath = $settings['letterhead_path'] ?? null;
        $letterheadFile = $letterheadPath ? public_path($letterheadPath) : null;
        $data['letterheadSrc'] = $letterheadFile && is_file($letterheadFile)
            ? 'data:image/' . pathinfo($letterheadFile, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($letterheadFile))
            : null;
        return Pdf::loadHTML(view('peserta.cetak.index', $data)->render())
            ->setPaper('a4', 'portrait')
            ->download('formulir-' . ($pendaftar->registration_number ?: $pendaftar->id) . '.pdf');
    }

    public function verify(Request $request, Pendaftar $pendaftar, WhatsappCloudApiService $whatsapp)
    {
        if ($pendaftar->registration_status === 'draft') {
            return back()->with('warning', 'Formulir masih Draft dan belum dikirim siswa. Formulir belum boleh disetujui atau dipendingkan.');
        }

        if (! RegistrationFee::isPaid(RegistrationFee::billFor($pendaftar))) {
            return back()->with('warning', 'Pembayaran formulir belum lunas. Approval formulir belum dapat dilakukan.');
        }

        $request->merge([
            'notes' => trim((string) $request->input('notes', '')),
        ]);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['verified', 'submitted'])],
            'notes' => [
                Rule::requiredIf(fn () => $request->input('status') === 'submitted'),
                'nullable',
                'string',
                'max:500',
            ],
        ], [
            'notes.required' => 'Isi catatan kalau formulir dibuat pending agar siswa tahu bagian yang perlu diperbaiki.',
        ]);

        $pendaftar->load([
            'biodata',
            'alamat',
            'dataAyah',
            'dataIbu',
            'sekolahAsal',
            'dokumenPendaftars',
        ]);

        if ($validated['status'] === 'verified') {
            $missing = collect(FormFieldCatalog::activeStatuses($pendaftar, ['Wali', 'Dokumen Pendukung']))
                ->filter(fn ($complete) => !$complete)
                ->keys()->all();
            if ((FormFieldCatalog::isEnabled('foto_3x4') || FormFieldCatalog::isEnabled('skl_skhu_ijazah')) && $pendaftar->dokumenPendaftars->isEmpty()) {
                $missing[] = 'Dokumen Terunggah';
            }

            if ($missing) {
                return back()
                    ->withInput()
                    ->with('warning', 'Formulir belum bisa diverifikasi. Lengkapi dulu: ' . implode(', ', $missing) . '.');
            }
        }

        $oldStatus = $pendaftar->registration_status;
        $oldNotes = trim((string) $pendaftar->verification_notes);
        $isSameDecision = $oldStatus === $validated['status']
            && ($validated['status'] === 'verified' || $oldNotes === trim((string) ($validated['notes'] ?? '')));

        if ($isSameDecision) {
            return redirect()->route($this->routeName('pendaftar.show'), $pendaftar)
                ->with('success', $validated['status'] === 'verified'
                    ? 'Formulir ini sudah disetujui. Notifikasi WhatsApp tidak dikirim ulang.'
                    : 'Status pending dan catatan belum berubah. Notifikasi WhatsApp tidak dikirim ulang.');
        }
        if ($validated['status'] === 'verified') {
            $pendaftar->dokumenPendaftars()->update([
                'status' => 'approved',
                'notes' => null,
                'verified_by' => Auth::id(),
                'verified_at' => now(),
            ]);
        }

        $pendaftar->update([
            'registration_status' => $validated['status'],
            'verification_notes' => $validated['status'] === 'submitted' ? $validated['notes'] : null,
            'correction_status' => $validated['status'] === 'submitted' ? 'requested' : null,
            'correction_submitted_at' => null,
        ]);

        RiwayatStatusPendaftar::create([
            'pendaftar_id' => $pendaftar->id,
            'status_lama' => $oldStatus,
            'status_baru' => $validated['status'],
            'catatan' => $validated['notes'] ?? null,
            'diubah_oleh' => Auth::id(),
        ]);

        $pendaftar->loadMissing(['user', 'biodata', 'kontak', 'preferredTestSchedule']);
        $studentPhone = $pendaftar->user?->phone;
        $studentName = $pendaftar->biodata?->full_name ?? $pendaftar->user?->name ?? 'Calon siswa';
        $approverName = Auth::user()->name;
        $dashboardUrl = route('peserta.dashboard');

        if ($validated['status'] === 'verified') {
            $testSchedule = $pendaftar->preferredTestSchedule;
            $scheduleInfo = $testSchedule
                ? "Jadwal Tes SPMB: " . \Illuminate\Support\Carbon::parse($testSchedule->tanggal_mulai)->translatedFormat('l, d F Y · H:i') . " WIB\n"
                    . "Lokasi: Kampus E SMK Muhammadiyah 4 Cileungsi\n"
                    . "Mohon hadir 30 menit sebelum tes dimulai.\n\n"
                : "Jadwal Tes SPMB akan muncul di dashboard setelah ditetapkan sekolah.\n\n";

            $whatsappMessage = \App\Support\WhatsappGreeting::opening()."\n\n"
                . "Formulir pendaftaran atas nama {$studentName} sudah diverifikasi oleh panitia.\n"
                . "No. pendaftaran: {$pendaftar->registration_number}\n\n"
                . $scheduleInfo
                . "Buka dashboard untuk melihat informasi proses pendaftaran: {$dashboardUrl}";
        } else {
            $whatsappMessage = \App\Support\WhatsappGreeting::opening()."\n\n"
                ."Formulir SPMB atas nama {$studentName} dengan nomor {$pendaftar->registration_number} perlu diperbaiki oleh {$approverName}.\n"
                ."Catatan: {$validated['notes']}\n\n"
                ."Silakan masuk, perbaiki bagian yang diminta, lalu kirim ulang formulir melalui: {$dashboardUrl}";
        }

        try {
            if (! $studentPhone) {
                throw new \RuntimeException('Nomor WhatsApp siswa tidak tersedia.');
            }
            $whatsapp->send((string) $studentPhone, $whatsappMessage);
        } catch (Throwable $exception) {
            Log::warning('Notifikasi hasil approval formulir ke siswa gagal dikirim.', [
                'applicant_id' => $pendaftar->id,
                'error' => $exception->getMessage(),
            ]);

            return redirect()->route($this->routeName('pendaftar.show'), $pendaftar)
                ->with('warning', 'Status formulir sudah tersimpan, tetapi notifikasi WhatsApp ke siswa belum terkirim.');
        }

        $message = $validated['status'] === 'verified'
            ? 'Formulir pendaftaran berhasil diverifikasi. Notifikasi tahap selanjutnya sudah dikirim ke siswa.'
            : 'Formulir dibuat pending. Catatan dan notifikasi perbaikan sudah dikirim ke siswa.';

        return redirect()->route($this->routeName('pendaftar.show'), $pendaftar)
            ->with('success', $message);
    }

    private function formFieldExportValue(Pendaftar $pendaftar, string $key): mixed
    {
        if ($key === 'jurusan') {
            return $pendaftar->jurusan1?->name;
        }

        if ($key === 'email') {
            return $pendaftar->kontak?->email ?? $pendaftar->user?->email;
        }

        if ($key === 'tanggal_lahir') {
            $date = $pendaftar->biodata?->birth_date;
            return $date ? \Illuminate\Support\Carbon::parse($date)->format('d-m-Y') : null;
        }

        if (in_array($key, [
            'foto_3x4', 'foto_seluruh_badan', 'skl_skhu_ijazah', 'nilai_rapor_dokumen', 'akta_kelahiran',
            'kartu_keluarga', 'ktp_orangtua', 'sptjm_orangtua', 'surat_penugasan_instansi', 'surat_domisili',
            'surat_tidak_mampu', 'kartu_pkh_kps_kip', 'prestasi', 'berkas_lainnya',
        ], true)) {
            return $this->documentExportValue($pendaftar, $key);
        }

        return FormFieldCatalog::valueFor($pendaftar, $key);
    }

    private function documentExportValue(Pendaftar $pendaftar, string $key): string
    {
        $matchers = [
            'foto_3x4' => fn (string $name) => str_contains($name, 'foto') && str_contains($name, '3x4'),
            'foto_seluruh_badan' => fn (string $name) => str_contains($name, 'foto') && (str_contains($name, 'badan') || str_contains($name, 'full')),
            'skl_skhu_ijazah' => fn (string $name) => str_contains($name, 'ijazah') || str_contains($name, 'skl') || str_contains($name, 'skhu'),
            'nilai_rapor_dokumen' => fn (string $name) => str_contains($name, 'rapor') || str_contains($name, 'nilai'),
            'akta_kelahiran' => fn (string $name) => str_contains($name, 'akta'),
            'kartu_keluarga' => fn (string $name) => str_contains($name, 'keluarga') || str_contains($name, 'kk'),
            'ktp_orangtua' => fn (string $name) => str_contains($name, 'ktp'),
            'sptjm_orangtua' => fn (string $name) => str_contains($name, 'sptjm'),
            'surat_penugasan_instansi' => fn (string $name) => str_contains($name, 'penugasan'),
            'surat_domisili' => fn (string $name) => str_contains($name, 'domisili'),
            'surat_tidak_mampu' => fn (string $name) => str_contains($name, 'tidak mampu') || str_contains($name, 'sktm'),
            'kartu_pkh_kps_kip' => fn (string $name) => str_contains($name, 'pkh') || str_contains($name, 'kps') || str_contains($name, 'kip'),
            'prestasi' => fn (string $name) => str_contains($name, 'prestasi'),
            'berkas_lainnya' => fn (string $name) => str_contains($name, 'lain'),
        ];
        $matcher = $matchers[$key] ?? null;
        if (! $matcher) {
            return '';
        }

        $document = $pendaftar->dokumenPendaftars->first(fn ($item) => $matcher(strtolower((string) $item->jenisDokumen?->name)));
        if (! $document) {
            return 'Belum diunggah';
        }

        return match ($document->status) {
            'approved' => 'Diunggah — Disetujui',
            'rejected' => 'Diunggah — Ditolak',
            default => 'Diunggah — Menunggu verifikasi',
        };
    }

    private function filteredPendaftarQuery(Request $request)
    {
        return Pendaftar::query()
            ->when($request->filled('academic_year_id'), fn ($query) => $query->where('academic_year_id', $request->integer('academic_year_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('registration_status', $request->status))
            ->when($request->filled('correction'), fn ($query) => $query->where('correction_status', $request->correction))
            ->when($request->filled('search'), fn ($query) => $query->whereHas('biodata', fn ($biodata) => $biodata->where('full_name', 'LIKE', '%' . $request->search . '%')));
    }

    private function writeExportRow($sheet, int $row, array $values): void
    {
        foreach (array_values($values) as $index => $value) {
            $sheet->setCellValueExplicit(Coordinate::stringFromColumnIndex($index + 1) . $row, (string) ($value ?? ''), DataType::TYPE_STRING);
        }
    }

    private function formatExportSheet($sheet, int $columnCount, int $lastRow, int $headerRows = 1): void
    {
        $lastColumn = Coordinate::stringFromColumnIndex($columnCount);
        $sheet->freezePane('A' . ($headerRows + 1));
        $sheet->setAutoFilter('A' . $headerRows . ':' . $lastColumn . $lastRow);
        $sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F3B82']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        if ($headerRows > 1) {
            $sheet->getStyle('A2:' . $lastColumn . '2')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '0F3B82']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F1FF']],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            ]);
            $sheet->getRowDimension(2)->setRowHeight(42);
        }
        $sheet->getStyle('A1:' . $lastColumn . $lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
        $sheet->getRowDimension(1)->setRowHeight(28);
        for ($column = 1; $column <= $columnCount; $column++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setWidth(20);
        }
    }

    private function registrationStatusLabel(?string $status): string
    {
        return [
            'draft' => 'Draft', 'submitted' => 'Pending', 'verified' => 'Terverifikasi',
            'accepted' => 'Diterima', 'rejected' => 'Ditolak', 're_registered' => 'Daftar Ulang',
        ][$status] ?? (string) $status;
    }

    private function correctionStatusLabel(?string $status): string
    {
        return ['requested' => 'Perlu Perbaikan', 'resubmitted' => 'Sudah Diperbaiki'][$status] ?? (string) $status;
    }

    private function selectionStatusLabel(?string $status): string
    {
        return ['accepted' => 'Diterima', 'rejected' => 'Tidak diterima', 'pending' => 'Menunggu keputusan'][$status] ?? (string) $status;
    }

    private function routeName(string $name): string
    {
        if (request()->routeIs('admin.*')) {
            return 'admin.' . $name;
        }

        if (request()->routeIs('bendahara.*')) {
            return 'bendahara.' . $name;
        }

        if (request()->routeIs('kepala-sekolah.*')) {
            return 'kepala-sekolah.' . $name;
        }

        return 'panitia.' . $name;
    }
}
