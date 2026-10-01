<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Fill};

class ExecutiveReportWorkbook
{
    public function build(array $data): Spreadsheet
    {
        $book = new Spreadsheet();
        $book->removeSheetByIndex(0);
        $book->getProperties()->setCreator('SPMB SMKM 4 Cileungsi')->setTitle('Laporan Eksekutif SPMB');
        $context = $data['yearLabel'].'; bulan: '.($data['filters']['month'] ?? 'Semua bulan').'; dibuat '.$data['generatedAt']->format('d/m/Y H:i').' WIB';
        $filterLabels = [
            'Tahun ajaran' => $data['yearLabel'], 'Bulan daftar' => $data['filters']['month'] ?? 'Semua',
            'Jurusan pilihan 1' => $data['majors']->firstWhere('id', $data['filters']['major_id'] ?? 0)?->name ?? 'Semua',
            'Gelombang' => $data['waves']->firstWhere('id', $data['filters']['wave_id'] ?? 0)?->name ?? 'Semua',
            'Jalur' => $data['paths']->firstWhere('id', $data['filters']['path_id'] ?? 0)?->name ?? 'Semua',
            'Status' => ExecutiveReportService::STATUSES[$data['filters']['status'] ?? ''] ?? 'Semua',
            'Sekolah asal' => $data['filters']['school'] ?? 'Semua', 'Pencarian nama / nomor' => $data['filters']['search'] ?? 'Semua',
        ];
        $this->sheet($book, 'Ringkasan', ['Indikator', 'Jumlah / nilai'], [
            ['Total pendaftar', $data['totalApplicants']], ['Diterima (termasuk daftar ulang)', $data['accepted']],
            ['Daftar ulang', $data['reRegistered']], ['Menunggu verifikasi', $data['pendingVerification']],
            ['Pendaftar jalur beasiswa', $data['scholarshipRows']->count()], ['Minat promosi', $data['interests']->count()],
            ['Pembayaran terverifikasi (Rp)', $data['verifiedRevenue']], ['Total tagihan (Rp)', $data['targetRevenue']],
            ['Bulan pendaftaran', 'Tanggal akun pendaftar dibuat, termasuk draft.'],
            ['Jurusan diterima', 'Jurusan keputusan seleksi, bukan selalu pilihan pertama.'],
            ['Beasiswa', 'Berdasarkan nama jalur yang mengandung Beasiswa; bukan bukti pencairan.'],
            ['Promosi', 'Data minat terpisah dari pendaftar. Hanya filter bulan dan jurusan berlaku. Tidak memiliki tahun ajaran.'],
            ['Sumber', 'Data sistem SPMB pada waktu ekspor. Unduh ulang untuk data terbaru.'],
            ...collect($filterLabels)->map(fn ($v, $k) => ['Filter '.$k, (string) $v])->values()->all(),
        ], $context);
        $headers = ['No. pendaftaran', 'Nama siswa', 'WhatsApp', 'Tahun ajaran', 'Tanggal daftar', 'Bulan daftar', 'Jalur', 'Gelombang', 'Jurusan pilihan 1', 'Jurusan pilihan 2', 'Jurusan diterima', 'Sekolah asal', 'Status', 'Tagihan (Rp)', 'Terverifikasi (Rp)', 'Sisa (Rp)'];
        $details = fn ($rows) => $rows->map(fn ($r) => [$r['number'], $r['name'], $r['phone'], $r['year'], $r['date'], $r['month'], $r['path'], $r['wave'], $r['major'], $r['major2'], $r['acceptedMajor'], $r['school'], $r['statusLabel'], $r['target'], $r['paid'], $r['remaining']])->values()->all();
        $this->sheet($book, 'Pendaftar', $headers, $details($data['rows']), $context);
        $this->sheet($book, 'Beasiswa', $headers, $details($data['scholarshipRows']), $context.'; jalur bernama Beasiswa');
        $this->sheet($book, 'Diterima', $headers, $details($data['rows']->whereIn('status', ['accepted', 're_registered'])), $context);
        $this->sheet($book, 'Daftar Ulang', $headers, $details($data['rows']->where('status', 're_registered')), $context);
        foreach (['Jalur' => 'pathSummary', 'Gelombang' => 'waveSummary', 'Bulanan' => 'monthSummary', 'Sekolah Asal' => 'schoolSummary'] as $title => $key) {
            $sheet = $this->sheet($book, $title, [$title, 'Pendaftar', 'Diterima', 'Daftar ulang'], $data[$key]->map(fn ($g) => [$g->name, $g->applicants, $g->accepted, $g->re_registered])->all(), $context);
            $row = $sheet->getHighestRow() + 3;
            $sheet->setCellValue('A'.$row, 'Daftar nama');
            $this->table($sheet, $row + 1, $headers, $details($data[$key]->flatMap(fn ($g) => $g->members)), false);
        }
        $sheet = $this->sheet($book, 'Jurusan', ['Jurusan', 'Pilihan utama', 'Diterima', 'Daftar ulang'], $data['majorSummary']->map(fn ($m) => [$m->name, $m->applicants, $m->accepted, $m->re_registered])->all(), $context);
        $this->table($sheet, $sheet->getHighestRow() + 4, $headers, $details($data['rows']->sortBy('major')), false);
        $this->sheet($book, 'Gelombang Jurusan', ['Gelombang', 'Jurusan pilihan 1', 'Kuota gelombang', 'Kuota jurusan', 'Pendaftar', 'Diterima', 'Daftar ulang', 'Terverifikasi (Rp)'], $data['waveMajorSummary']->map(fn ($r) => [$r->wave_name, $r->major_name, $r->wave_quota, $r->major_quota, $r->applicants, $r->accepted, $r->re_registered, $r->verified_payment])->all(), $context);
        $this->sheet($book, 'Rekap Promosi', ['Jurusan diminati', 'Jumlah peminat'], $data['promotionSummary']->map(fn ($r) => [$r->name, $r->total])->all(), 'Promosi: semua tahun; bulan '.($data['filters']['month'] ?? 'Semua bulan').'; filter jurusan mengikuti laporan.');
        $this->sheet($book, 'Minat Promosi', ['Nama siswa', 'WhatsApp', 'Sekolah asal', 'Jurusan diminati', 'Tanggal mengisi'], $data['interests']->map(fn ($i) => [$i->full_name, $i->student_phone, $i->school_name, $i->jurusanDiminati?->name ?? $i->major_interest ?? 'Belum memilih', $i->submitted_at])->all(), 'Minat belum berarti mendaftar; tanpa filter tahun ajaran, gelombang, jalur dan status.');
        $byId = $data['rows']->keyBy('id');
        $this->sheet($book, 'Pembayaran', ['ID transaksi', 'No. pendaftaran', 'Nama siswa', 'Tagihan', 'Nominal (Rp)', 'Status pembayaran', 'Tanggal transaksi'], $data['payments']->map(function ($p) use ($byId) {
            $r = $byId[$p->tagihan?->applicant_id] ?? [];
            return [$p->id, $r['number'] ?? '', $r['name'] ?? '', $p->tagihan?->jenisTagihan?->name ?? 'Tagihan', (float) $p->amount, $p->status, $p->created_at];
        })->all(), $context.'; ringkasan dana hanya transaksi verified');
        $this->sheet($book, 'Hasil Tes', ['Nama tes', 'Peserta hadir', 'Rata-rata nilai'], $data['testSummary']->map(fn ($t) => [$t->test_name, $t->participants, $t->average_score])->all(), $context);
        $book->setActiveSheetIndex(0);
        return $book;
    }

    private function sheet(Spreadsheet $book, string $name, array $headers, array $rows, string $context): Worksheet
    {
        $sheet = new Worksheet($book, $name);
        $book->addSheet($sheet);
        $sheet->setShowGridlines(false);
        $sheet->setCellValue('A2', 'Laporan SPMB — '.$name);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(16)->getColor()->setRGB('16324F');
        $sheet->setCellValue('A3', $context);
        $sheet->getStyle('A3')->getFont()->setSize(10)->getColor()->setRGB('64748B');
        $this->table($sheet, 5, $headers, $rows);
        $sheet->getSheetView()->setZoomScale(90);
        $sheet->getPageSetup()->setOrientation('landscape')->setFitToWidth(1)->setFitToHeight(0);
        return $sheet;
    }

    private function table(Worksheet $sheet, int $headerRow, array $headers, array $rows, bool $filter = true): void
    {
        $lastColumn = Coordinate::stringFromColumnIndex(count($headers));
        foreach ($headers as $index => $title) $sheet->setCellValueExplicit(Coordinate::stringFromColumnIndex($index + 1).$headerRow, $title, DataType::TYPE_STRING);
        $sheet->getStyle('A'.$headerRow.':'.$lastColumn.$headerRow)->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '16324F']], 'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true]]);
        $sheet->getRowDimension($headerRow)->setRowHeight(32);
        foreach ($rows as $index => $values) {
            $row = $headerRow + 1 + $index;
            $height = 24;
            foreach ($values as $col => $value) {
                $cell = Coordinate::stringFromColumnIndex($col + 1).$row;
                if ($value instanceof \DateTimeInterface) {
                    $sheet->setCellValue($cell, Date::PHPToExcel($value));
                    $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('dd/mm/yyyy hh:mm');
                } elseif (is_int($value) || is_float($value)) {
                    $sheet->setCellValue($cell, $value);
                    $sheet->getStyle($cell)->getNumberFormat()->setFormatCode(str_contains($headers[$col], 'nilai') ? '0.0' : '#,##0');
                } else {
                    // All user content is literal text, including leading =, +, - or @.
                    $sheet->setCellValueExplicit($cell, (string) ($value ?? ''), DataType::TYPE_STRING);
                    if (mb_strlen((string) $value) > 46) {
                        $sheet->getStyle($cell)->getAlignment()->setWrapText(true);
                        $height = max($height, ceil(mb_strlen((string) $value) / 44) * 16);
                    }
                }
            }
            $sheet->getRowDimension($row)->setRowHeight($height);
            if ($index % 2 === 1) $sheet->getStyle('A'.$row.':'.$lastColumn.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F1F5F9');
        }
        if (!$rows) $sheet->setCellValue('A'.($headerRow + 1), 'Tidak ada data sesuai filter.');
        $lastRow = max($headerRow + 1, $headerRow + count($rows));
        $sheet->getStyle('A'.$headerRow.':'.$lastColumn.$lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        for ($col = 1; $col <= count($headers); $col++) {
            $column = Coordinate::stringFromColumnIndex($col);
            $width = 20;
            foreach ([$headers[$col - 1], ...array_column($rows, $col - 1)] as $v) if (is_string($v)) $width = max($width, min(48, mb_strlen($v) + 2));
            $sheet->getColumnDimension($column)->setWidth(max($width, $sheet->getColumnDimension($column)->getWidth()));
        }
        if ($filter) {
            $sheet->setAutoFilter('A'.$headerRow.':'.$lastColumn.$lastRow);
            $sheet->freezePane('C'.($headerRow + 1));
        }
    }
}
