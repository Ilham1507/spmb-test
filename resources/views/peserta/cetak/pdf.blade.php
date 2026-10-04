@php
    $academicYear = $pendaftar->tahunAjaran?->name ?? $pendaftar->gelombangPendaftaran?->tahunAjaran?->name ?? '-';
    $status = match ($pendaftar->registration_status) {
        'draft' => 'Belum dikirim', 'submitted' => 'Menunggu pemeriksaan',
        'verified', 'approved' => 'Disetujui', 'rejected' => 'Ditolak',
        default => ucfirst($pendaftar->registration_status ?? '-'),
    };
    $letterheadFile = public_path($settings['letterhead_path'] ?? 'images/kop-surat-resmi.png');
    if (!is_file($letterheadFile)) { $letterheadFile = public_path('images/kop-surat-resmi.png'); }
    $letterheadSrc = is_file($letterheadFile) ? 'data:image/'.pathinfo($letterheadFile, PATHINFO_EXTENSION).';base64,'.base64_encode(file_get_contents($letterheadFile)) : null;
    $value = function ($key) use ($pendaftar) {
        if ($key === 'jenis_kelamin') {
            return match ($pendaftar->biodata?->gender) { 'L' => 'Laki-laki', 'P' => 'Perempuan', default => '-' };
        }
        $result = \App\Support\FormFieldCatalog::valueFor($pendaftar, $key);
        if ($key === 'tanggal_lahir' && $result) {
            try { return \Illuminate\Support\Carbon::parse($result)->locale('id')->translatedFormat('d F Y'); } catch (\Throwable) {}
        }
        return $result ?: '-';
    };
@endphp
<!doctype html>
<html lang="id"><head><meta charset="utf-8"><title>Formulir Pendaftaran - {{ $pendaftar->registration_number }}</title>
<style>
    @page { size: A4 portrait; margin: 8mm 8mm 18mm; }
    * { box-sizing: border-box; }
    body { margin: 0; color: #172033; font-family: DejaVu Sans, sans-serif; font-size: 9px; line-height: 1.25; }
    .letterhead { display: block; width: 100%; height: auto; }
    .school { text-align: center; color: #102d61; border-bottom: 3px solid #d8a900; padding: 8px; }
    h1 { margin: 12px 0 3px; text-align: center; color: #102d61; font-size: 15px; }
    .year { margin: 0 0 10px; text-align: center; color: #475569; }
    .meta { width: 100%; border-collapse: collapse; background: #f0f5fb; margin-bottom: 10px; }
    .meta td { padding: 7px 10px; width: 50%; }
    .meta .right { text-align: right; }
    .section { margin: 0 4mm 5px; }
    h2 { margin: 0 0 4px; padding: 4px 6px; background: #edf4f7; border-left: 3px solid #0f766e; color: #102d61; font-size: 10px; }
    .fields { width: 100%; table-layout: fixed; border-collapse: collapse; }
    .fields > tbody > tr > td { width: 50%; vertical-align: top; padding: 1px 5px; }
    tr { page-break-inside: avoid; }
    .field { width: 100%; table-layout: fixed; border-collapse: collapse; }
    .field td { vertical-align: top; padding: 0; overflow-wrap: break-word; }
    .label { width: 40%; color: #52637d; }
    .colon { width: 4%; }
    .value { width: 56%; }
    .signatures { width: 100%; margin-top: 16px; page-break-inside: avoid; text-align: center; table-layout: fixed; }
    .signatures td { width: 50%; padding: 0 12mm; vertical-align: top; }
    .signature-space { height: 17mm; }
    .signature-line { border-top: 1px solid #64748b; padding-top: 4px; color: #52637d; }
    .footer { position: fixed; bottom: -10mm; left: 0; right: 0; margin: 0; padding: 10px 17mm; background: #102d61; color: #fff; font-size: 9px; }
</style></head><body>
<footer class="footer">{{ $settings['school_name'] ?? 'SMK Muhammadiyah 4 Cileungsi' }} · {{ $settings['school_address'] ?? 'Cileungsi, Bogor' }}</footer>
@if($letterheadSrc)<img class="letterhead" src="{{ $letterheadSrc }}" alt="Kop surat resmi sekolah">@else<div class="school">{{ $settings['school_name'] ?? 'SMK Muhammadiyah 4 Cileungsi' }}</div>@endif
<h1>FORMULIR PENDAFTARAN MURID BARU</h1><p class="year">Tahun Pelajaran {{ $academicYear }}</p>
<table class="meta"><tr><td>No. Pendaftaran: <strong>{{ $pendaftar->registration_number ?: '-' }}</strong></td><td class="right">Status: <strong>{{ $status }}</strong></td></tr></table>
@php
    $sections = ['Pilihan Pendaftaran' => ['pilihan_1' => 'Jurusan Pilihan 1', 'pilihan_2' => 'Jurusan Pilihan 2', 'jalur' => 'Jalur Pendaftaran']];
    foreach ($groups as $group => $fields) {
        if (in_array($group, ['Wali', 'Dokumen Pendukung'], true)) { continue; }
        $visible = collect($fields)->except(['jurusan'])->filter(fn ($label, $key) => in_array($key, $enabledFields, true));
        if ($visible->isNotEmpty()) { $sections['Data '.$group] = $visible->all(); }
    }
@endphp
@foreach($sections as $heading => $fields)
<div class="section"><h2>{{ $heading }}</h2><table class="fields"><tbody>
@foreach(collect($fields)->chunk(2) as $row)
<tr>@foreach($row as $key => $label)
@php $display = match ($key) { 'pilihan_1' => $pendaftar->jurusan1?->name ?: '-', 'pilihan_2' => $pendaftar->jurusan2?->name ?: '-', 'jalur' => $pendaftar->jalurPendaftaran?->name ?: '-', default => $value($key) }; @endphp
<td><table class="field"><tr><td class="label">{{ $label }}</td><td class="colon">:</td><td class="value">{{ $display }}</td></tr></table></td>
@endforeach @if($row->count() === 1)<td></td>@endif</tr>
@endforeach
</tbody></table></div>
@endforeach
<table class="signatures"><tr><td>Mengetahui,<br>Orang Tua/Wali</td><td>Cileungsi, ........................<br>Calon Siswa</td></tr><tr><td class="signature-space"></td><td></td></tr><tr><td><div class="signature-line">Nama terang dan tanda tangan</div></td><td><div class="signature-line">Nama terang dan tanda tangan</div></td></tr></table>
</body></html>
