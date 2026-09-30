<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GelombangPendaftaran;
use App\Models\GelombangJurusan;
use App\Models\Jurusan;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class GelombangController extends Controller
{
    public function index()
    {
        return view('admin.gelombang.index', [
            'gelombangs' => GelombangPendaftaran::with('tahunAjaran')->latest('start_date')->get(),
            'tahunAjarans' => TahunAjaran::latest('start_date')->get(),
            'jurusans' => Jurusan::where('status', 'aktif')->orderBy('name')->get(),
        ]);
    }

    public function biayaJurusan()
    {
        // Do not let one legacy row in gelombang_jurusan make the whole
        // management page unavailable. Older Railway databases can have the
        // base registration tables while this optional fee-breakdown table is
        // still being repaired during startup.
        $gelombangs = GelombangPendaftaran::with('tahunAjaran')->latest('start_date')->get();
        $jurusans = Jurusan::where('status', 'aktif')->orderBy('name')->get();

        try {
            $feesByGelombang = GelombangJurusan::query()
                ->whereIn('gelombang_id', $gelombangs->pluck('id'))
                ->get()
                ->groupBy('gelombang_id');
        } catch (\Throwable $exception) {
            Log::error('Unable to load major fee breakdowns.', [
                'exception' => $exception,
            ]);

            $feesByGelombang = collect();
        }

        $gelombangs->each(function (GelombangPendaftaran $gelombang) use ($feesByGelombang): void {
            $gelombang->setRelation('jurusanBiaya', $feesByGelombang->get($gelombang->id, collect()));
        });

        return view('admin.biaya-jurusan.index', [
            'gelombangs' => $gelombangs,
            'jurusans' => $jurusans,
        ]);
    }

    public function saveBiayaJurusan(Request $request)
    {
        $data = $request->validate([
            'details' => 'nullable|array',
            'details.*' => 'nullable|array',
            'details.*.*' => 'nullable|array',
            'details.*.*.*.name' => 'nullable|string|max:100',
            'details.*.*.*.category' => 'nullable|string|max:50',
            'details.*.*.*.amount' => 'nullable|numeric|min:0',
        ]);

        $details = $data['details'] ?? [];

        DB::transaction(function () use ($details) {
            // The page submits only the major currently edited in the popup.
            // Never iterate every gelombang/jurusan here: missing request data means
            // "not edited", not "delete its fees".
            foreach ($details as $gelombangId => $majorDetails) {
                $gelombang = GelombangPendaftaran::query()->find($gelombangId);
                if (! $gelombang || ! is_array($majorDetails)) continue;

                foreach ($majorDetails as $jurusanId => $rawComponents) {
                    $jurusan = Jurusan::query()->where('status', 'aktif')->find($jurusanId);
                    if (! $jurusan || ! is_array($rawComponents)) continue;

                    $components = collect($rawComponents)
                        ->map(fn ($item) => ['name' => trim((string) ($item['name'] ?? '')), 'category' => trim((string) ($item['category'] ?? 'Lainnya')) ?: 'Lainnya', 'amount' => (float) ($item['amount'] ?? 0)])
                        ->filter(fn ($item) => $item['name'] !== '' && $item['amount'] > 0)
                        ->values()
                        ->all();

                    $relation = GelombangJurusan::firstOrNew([
                        'gelombang_id' => $gelombang->id,
                        'jurusan_id' => $jurusan->id,
                    ]);

                    // An empty list intentionally deletes only this selected major's fee.
                    if ($components === []) {
                        $relation->delete();
                        continue;
                    }

                    $relation->biaya_masuk = collect($components)->sum('amount');
                    $relation->rincian_biaya = $components;
                    $relation->save();
                }
            }
        });

        return back()->with('success', 'Biaya jurusan berhasil disimpan.');
    }

    public function exportBiayaJurusan()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rincian Biaya');
        $headings = ['Gelombang', 'Jurusan', 'Kelompok Komponen', 'Nama Komponen', 'Nominal'];
        $sheet->fromArray($headings, null, 'A1');

        $row = 2;
        $gelombangs = GelombangPendaftaran::with(['jurusanBiaya.jurusan'])->latest('start_date')->get();
        foreach ($gelombangs as $gelombang) {
            foreach ($gelombang->jurusanBiaya as $fee) {
                foreach ((array) $fee->rincian_biaya as $item) {
                    $sheet->fromArray([
                        $gelombang->name,
                        $fee->jurusan?->name,
                        filled($item['category'] ?? null) ? $item['category'] : $this->feeCategory((string) ($item['name'] ?? '')),
                        $item['name'] ?? '',
                        (float) ($item['amount'] ?? 0),
                    ], null, "A{$row}");
                    $row++;
                }
            }
        }

        $this->styleFeeSpreadsheet($sheet, $row - 1);

        return $this->downloadSpreadsheet($spreadsheet, 'biaya-jurusan-spmb.xlsx');
    }

    public function templateBiayaJurusan()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rincian Biaya');
        $sheet->fromArray(['Gelombang', 'Jurusan', 'Kelompok Komponen', 'Nama Komponen', 'Nominal'], null, 'A1');

        $examples = [
            ['CONTOH - GANTI GELOMBANG', 'CONTOH - GANTI Jurusan', 'Biaya Pendidikan', 'SPP Awal', 250000],
            ['CONTOH - GANTI GELOMBANG', 'CONTOH - GANTI Jurusan', 'Administrasi & Dokumen', 'Administrasi Daftar Ulang', 150000],
            ['CONTOH - GANTI GELOMBANG', 'CONTOH - GANTI Jurusan', 'Seragam & Perlengkapan', 'Seragam Sekolah', 750000],
            ['CONTOH - GANTI GELOMBANG', 'CONTOH - GANTI Jurusan', 'Praktik & Kejuruan', 'Bahan Praktik Jurusan', 500000],
        ];
        $sheet->fromArray($examples, null, 'A2');
        $sheet->getStyle('A2:E5')->applyFromArray([
            'font' => ['italic' => true, 'color' => ['rgb' => '64748B']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF7ED']],
        ]);
        $sheet->getStyle('A2:B5')->getFont()->getColor()->setRGB('C2410C');
        $this->styleFeeSpreadsheet($sheet, 5);
        foreach (range('A', 'E') as $column) $sheet->getColumnDimension($column)->setWidth(28);
        $sheet->freezePane('A2');

        $guide = $spreadsheet->createSheet();
        $guide->setTitle('Petunjuk');
        $guide->fromArray([
            ['PETUNJUK IMPOR BIAYA JURUSAN'],
            ['1. Contoh pengisian sudah tersedia di sheet Rincian Biaya. Ganti semua teks CONTOH dengan nama Gelombang dan Jurusan yang sama persis dengan sistem.'],
            ['2. Satu baris adalah satu komponen biaya. Tambahkan baris baru jika komponennya lebih banyak.'],
            ['3. Nominal boleh diisi 250000, 250.000, 250,000, atau Rp 250.000.'],
            ['4. Hanya kombinasi Gelombang dan Jurusan yang tercantum pada file yang akan diperbarui saat impor.'],
        ], null, 'A1');
        $guide->mergeCells('A1:E1');
        $guide->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('0D3476');
        $guide->getColumnDimension('A')->setWidth(112);
        $guide->getStyle('A1:A5')->getAlignment()->setWrapText(true);
        foreach (range(1, 5) as $guideRow) $guide->getRowDimension($guideRow)->setRowHeight(32);

        return $this->downloadSpreadsheet($spreadsheet, 'template-biaya-jurusan.xlsx');
    }

    public function importBiayaJurusan(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls,csv|max:5120']);

        $rows = IOFactory::load($request->file('file')->getRealPath())
            ->getSheetByName('Rincian Biaya')?->toArray(null, true, true, false);
        if (!$rows || count($rows) < 2) {
            throw ValidationException::withMessages(['file' => 'Sheet Rincian Biaya tidak ditemukan atau belum berisi data.']);
        }

        $headers = array_map(fn ($value) => strtolower(trim((string) $value)), array_shift($rows));
        $required = ['gelombang', 'jurusan', 'kelompok komponen', 'nama komponen', 'nominal'];
        if (array_diff($required, $headers)) {
            throw ValidationException::withMessages(['file' => 'Header tidak sesuai. Gunakan file hasil ekspor atau unduh template.']);
        }
        $columns = array_flip($headers);
        $gelombangs = GelombangPendaftaran::pluck('id', 'name');
        $jurusans = Jurusan::pluck('id', 'name');
        $groups = [];
        $errors = [];
        $exampleRows = 0;

        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $gelombangName = trim((string) ($row[$columns['gelombang']] ?? ''));
            $jurusanName = trim((string) ($row[$columns['jurusan']] ?? ''));
            $category = trim((string) ($row[$columns['kelompok komponen']] ?? '')) ?: 'Lainnya';
            $name = trim((string) ($row[$columns['nama komponen']] ?? ''));
            $amount = $this->spreadsheetAmount($row[$columns['nominal']] ?? null);

            if (str_contains(strtolower($gelombangName), 'contoh') || str_contains(strtolower($jurusanName), 'contoh')) {
                $exampleRows++;
                continue;
            }

            if ($gelombangName === '' && $jurusanName === '' && $name === '' && ($amount === null || $amount === '')) continue;
            if (!isset($gelombangs[$gelombangName])) $errors[] = "Baris {$line}: gelombang tidak ditemukan.";
            if (!isset($jurusans[$jurusanName])) $errors[] = "Baris {$line}: jurusan tidak ditemukan.";
            if ($name === '') $errors[] = "Baris {$line}: nama komponen wajib diisi.";
            if ($amount === null || $amount <= 0) $errors[] = "Baris {$line}: nominal harus lebih dari 0.";
            if (count($errors) >= 8) break;
            $key = $gelombangs[$gelombangName].'|'.$jurusans[$jurusanName];
            $groups[$key]['gelombang_id'] = $gelombangs[$gelombangName];
            $groups[$key]['jurusan_id'] = $jurusans[$jurusanName];
            $groups[$key]['components'][] = ['name' => $name, 'category' => $category, 'amount' => $amount];
        }

        if ($errors || $groups === []) {
            $message = $errors ? implode(' ', $errors) : ($exampleRows > 0
                ? 'File masih berisi baris contoh. Ganti Contoh Gelombang dan Contoh Jurusan dengan nama dari sheet Referensi Sistem.'
                : 'Tidak ada komponen biaya yang dapat diimpor. Isi minimal satu baris pada sheet Rincian Biaya.');
            throw ValidationException::withMessages(['file' => $message]);
        }

        DB::transaction(function () use ($groups) {
            foreach ($groups as $group) {
                GelombangJurusan::updateOrCreate(
                    ['gelombang_id' => $group['gelombang_id'], 'jurusan_id' => $group['jurusan_id']],
                    ['biaya_masuk' => collect($group['components'])->sum('amount'), 'rincian_biaya' => $group['components']]
                );
            }
        });

        return back()->with('success', count($groups).' rincian biaya berhasil diimpor dari Excel.');
    }

    private function feeCategory(string $name): string
    {
        $value = strtolower($name);
        return match (true) {
            str_contains($value, 'seragam'), str_contains($value, 'baju'), str_contains($value, 'atribut'), str_contains($value, 'sepatu'), str_contains($value, 'tas') => 'Seragam & Perlengkapan',
            str_contains($value, 'buku'), str_contains($value, 'modul'), str_contains($value, 'lks') => 'Buku & Pembelajaran',
            str_contains($value, 'formulir'), str_contains($value, 'administrasi'), str_contains($value, 'kartu pelajar'), str_contains($value, 'dokumen') => 'Administrasi & Dokumen',
            str_contains($value, 'praktik'), str_contains($value, 'kejuruan'), str_contains($value, 'pkl') => 'Praktik & Kejuruan',
            str_contains($value, 'ujian'), str_contains($value, 'tes'), str_contains($value, 'asesmen'), str_contains($value, 'sertifikasi') => 'Asesmen & Kompetensi',
            str_contains($value, 'fortasi'), str_contains($value, 'kegiatan'), str_contains($value, 'osis'), str_contains($value, 'ipm'), str_contains($value, 'ekskul') => 'Kegiatan & Kesiswaan',
            str_contains($value, 'uks'), str_contains($value, 'kesehatan'), str_contains($value, 'asuransi') => 'Kesehatan & Perlindungan',
            str_contains($value, 'infak'), str_contains($value, 'zakat'), str_contains($value, 'zis'), str_contains($value, 'ta’awin'), str_contains($value, "ta'awin") => 'Kerohanian & Sosial',
            str_contains($value, 'spp'), str_contains($value, 'gedung'), str_contains($value, 'pangkal'), str_contains($value, 'pendidikan') => 'Biaya Pendidikan',
            default => 'Lainnya',
        };
    }

    private function spreadsheetAmount(mixed $value): ?float
    {
        $raw = trim((string) $value);
        if ($raw === '') return null;
        // Spreadsheet cells can arrive as strings. Treat 250.000 as an Indonesian
        // thousands format before PHP interprets it as the decimal number 250.
        if (preg_match('/^\d{1,3}(\.\d{3})+$/', $raw)) return (float) str_replace('.', '', $raw);
        if (is_numeric($raw)) return (float) $raw;

        $normalized = preg_replace('/[^0-9,.-]/', '', $raw);
        if ($normalized === '') return null;
        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            $normalized = strrpos($normalized, ',') > strrpos($normalized, '.')
                ? str_replace(['.', ','], ['', '.'], $normalized)
                : str_replace(',', '', $normalized);
        } elseif (str_contains($normalized, ',')) {
            $normalized = str_replace(',', '', $normalized);
        } elseif (substr_count($normalized, '.') > 1 || preg_match('/^\d{1,3}(\.\d{3})+$/', $normalized)) {
            $normalized = str_replace('.', '', $normalized);
        }

        return is_numeric($normalized) ? (float) $normalized : null;
    }

    private function styleFeeSpreadsheet($sheet, int $lastRow): void
    {
        $sheet->freezePane('A2');
        $sheet->getStyle('A1:E1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A1:E1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0D3476');
        $sheet->getStyle("A1:E{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("E2:E{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
        foreach (['A' => 24, 'B' => 34, 'C' => 30, 'D' => 32, 'E' => 18] as $column => $width) $sheet->getColumnDimension($column)->setWidth($width);
        $sheet->setAutoFilter("A1:E{$lastRow}");
    }

    private function downloadSpreadsheet(Spreadsheet $spreadsheet, string $filename)
    {
        return response()->streamDownload(function () use ($spreadsheet) {
            IOFactory::createWriter($spreadsheet, 'Xlsx')->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($data) {
            if ($data['status'] === 'aktif') {
                GelombangPendaftaran::query()->lockForUpdate()->update(['status' => 'nonaktif']);
            }

            GelombangPendaftaran::create($data);
        });

        return back()->with('success', 'Gelombang berhasil ditambahkan.');
    }

    public function update(Request $request, GelombangPendaftaran $gelombang)
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($data, $gelombang) {
            if ($data['status'] === 'aktif') {
                GelombangPendaftaran::query()
                    ->whereKeyNot($gelombang->id)
                    ->lockForUpdate()
                    ->update(['status' => 'nonaktif']);
            }

            $gelombang->update($data);
        });

        return back()->with('success', 'Gelombang berhasil diperbarui.');
    }

    public function destroy(GelombangPendaftaran $gelombang)
    {
        $gelombang->delete();

        return back()->with('success', 'Gelombang berhasil dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'academic_year_id' => 'required|exists:tahun_ajaran,id',
            'name' => 'required|string|max:150',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'quota' => 'required|integer|min:0',
            'status' => 'required|in:aktif,nonaktif',
        ], [
            'academic_year_id.required' => 'Tahun ajaran wajib dipilih.',
            'name.required' => 'Nama gelombang wajib diisi.',
        ]);
    }

    private function saveFees(GelombangPendaftaran $gelombang, array $fees): void
    {
        foreach (Jurusan::where('status', 'aktif')->get() as $jurusan) {
            $amount = $fees[$jurusan->id] ?? null;
            $query = GelombangJurusan::query()
                ->where('gelombang_id', $gelombang->id)
                ->where('jurusan_id', $jurusan->id);

            if ($amount === null || $amount === '') {
                $query->delete();
                continue;
            }

            GelombangJurusan::updateOrCreate(
                ['gelombang_id' => $gelombang->id, 'jurusan_id' => $jurusan->id],
                ['biaya_masuk' => $amount]
            );
        }
    }
}

