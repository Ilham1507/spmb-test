<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\ReferensiSekolah;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('sekolah:import {file}', function (string $file) {
    $path = base_path($file);

    if (! is_file($path)) {
        $path = storage_path($file);
    }

    if (! is_file($path)) {
        $this->error("File tidak ditemukan: {$file}");
        return Command::FAILURE;
    }

    $handle = fopen($path, 'r');
    $header = fgetcsv($handle);

    if (! $header) {
        $this->error('CSV kosong atau header tidak terbaca.');
        return Command::FAILURE;
    }

    $header = array_map(fn ($value) => strtolower(trim($value)), $header);
    $imported = 0;

    while (($row = fgetcsv($handle)) !== false) {
        $data = array_combine($header, array_pad($row, count($header), null));
        $npsn = preg_replace('/\D/', '', (string) ($data['npsn'] ?? ''));
        $nama = trim((string) ($data['nama'] ?? $data['nama_sekolah'] ?? $data['sekolah'] ?? ''));

        if (strlen($npsn) !== 8 || $nama === '') {
            continue;
        }

        ReferensiSekolah::updateOrCreate(
            ['npsn' => $npsn],
            [
                'nama' => $nama,
                'bentuk_pendidikan' => $data['bentuk_pendidikan'] ?? $data['bentuk'] ?? null,
                'status' => $data['status'] ?? null,
                'alamat' => $data['alamat'] ?? $data['alamat_sekolah'] ?? null,
                'desa_kelurahan' => $data['desa_kelurahan'] ?? $data['kelurahan'] ?? null,
                'kecamatan' => $data['kecamatan'] ?? null,
                'kabupaten_kota' => $data['kabupaten_kota'] ?? $data['kabupaten'] ?? $data['kota'] ?? null,
                'provinsi' => $data['provinsi'] ?? null,
            ]
        );

        $imported++;
    }

    fclose($handle);
    $this->info("Import selesai. {$imported} sekolah diproses.");

    return Command::SUCCESS;
})->purpose('Import CSV referensi sekolah asal berdasarkan NPSN.');

Schedule::command('spmb:send-test-result-notifications')
    ->everyMinute()
    ->withoutOverlapping()
    ->timezone('Asia/Jakarta');

Schedule::command('spmb:send-selection-reminders')
    ->everyMinute()
    ->withoutOverlapping()
    ->timezone('Asia/Jakarta');
