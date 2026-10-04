@extends('layouts.peserta')

@section('title', 'Cetak Formulir Pendaftaran')
@section('page_title', 'Cetak Formulir Pendaftaran')

@section('content')
@php
    $isPdf = request()->routeIs('admin.pendaftar.pdf', 'panitia.pendaftar.pdf', 'peserta.pdf');
    $returnUrl = $backRoute ?? (request()->routeIs('peserta.*') ? route('peserta.dashboard') : url()->previous());
    $value = function (string $key) use ($pendaftar) {
        if ($key === 'jurusan') {
            return $pendaftar->jurusan1?->name;
        }

        if ($key === 'jenis_kelamin') {
            return match ($pendaftar->biodata?->gender) {
                'L' => 'Laki-laki',
                'P' => 'Perempuan',
                default => null,
            };
        }

        return \App\Support\FormFieldCatalog::valueFor($pendaftar, $key);
    };
@endphp
<style>
    @page{size:A4 portrait;margin:6mm 10mm 10mm}
    .print-sheet{width:100%;max-width:210mm!important;margin:0 auto}
    .letterhead{display:flex;align-items:center;gap:18px;border-bottom:4px solid #fbbf24;padding-bottom:10px;position:relative}.letterhead:after{content:'';position:absolute;bottom:-8px;left:0;right:0;border-bottom:1px solid #0f766e}.letterhead img{width:76px;height:76px;object-fit:contain}.letterhead-text{flex:1;text-align:center}.letterhead-text .brand{font-size:13px;font-weight:800;letter-spacing:.08em;color:#b45309;text-transform:uppercase}.letterhead-text .school{font-size:21px;font-weight:900;color:#0f172a;text-transform:uppercase;line-height:1.15}.letterhead-text .address{font-size:11px;color:#475569;margin-top:4px}.official-letterhead{width:100%;height:auto;display:block;max-height:145px;object-fit:contain;object-position:center}.form-title{text-align:center;margin:24px 0 18px}.form-title h1{font-size:20px;font-weight:900;text-transform:uppercase;color:#0f172a}.form-title p{font-size:13px;color:#475569;margin-top:3px}.print-meta{display:flex;justify-content:space-between;gap:12px;font-size:12px;margin-bottom:16px;color:#334155}.print-meta strong{color:#0f172a}
    .signature-area{display:grid;grid-template-columns:1fr 1fr;gap:70px;margin-top:32px;page-break-inside:avoid}.signature-box{text-align:center;font-size:12px;color:#1f2937}.signature-box .city{margin-bottom:58px}.signature-box .line{border-bottom:1px solid #1f2937;padding-bottom:4px}.signature-box .role{margin-top:6px}
    @media print{nav,header,.print-actions{display:none!important}@page{size:A4 portrait;margin:5mm 10mm 10mm}html,body{margin:0!important;padding:0!important;background:#fff!important;font-family:Arial,Helvetica,sans-serif!important}.portal-page-content,.portal-page-content *:not(.print-sheet):not(.print-sheet *){margin-top:0!important}.portal-page-content{padding:0!important;overflow:visible!important}.print-sheet,.print-sheet *{font-family:Arial,Helvetica,sans-serif!important}.print-sheet{width:100%!important;max-width:none!important;margin:0!important;padding:0!important;box-shadow:none!important;border:0!important;color:#1f2937!important;-webkit-print-color-adjust:exact;print-color-adjust:exact}.letterhead{padding-top:0}.official-letterhead{max-height:none!important}.form-title{margin:12px 0 10px}.form-title h1{font-size:18px}.form-title p{font-size:11px}.print-meta{font-size:10px;margin-bottom:10px}.print-sheet section{margin-bottom:12px!important}.print-sheet section h4{font-size:11px!important;padding-bottom:4px!important}.print-sheet section div{font-size:10px!important;line-height:1.25!important}.signature-area{margin-top:22px;gap:45px}}
    @media(max-width:560px){.letterhead{gap:10px}.letterhead img{width:52px;height:52px}.letterhead-text .school{font-size:14px}.letterhead-text .brand{font-size:9px}.letterhead-text .address{font-size:9px}.print-meta{display:block;line-height:1.8}}
</style>
@if($isPdf)
<style>
    @page{size:A4 portrait!important;margin:5mm 10mm 10mm}
    nav,header,.print-actions{display:none!important}
    .portal-page-content{padding:0!important;margin:0!important;overflow:visible!important}
    .portal-page-content > *{margin:0!important;max-width:none!important}
    .print-sheet{display:block!important;width:100%!important;max-width:none!important;margin:0!important;padding:0!important;border:0!important;box-shadow:none!important;font-family:Arial,Helvetica,sans-serif!important;color:#111827!important}
    .official-letterhead{display:block!important;width:100%!important;height:auto!important}
    .pdf-output .form-title{margin:12px 0 10px!important;text-align:center!important}
    .pdf-output .form-title h1{font-size:18px!important;font-weight:700!important}
    .pdf-output .form-title p{font-size:11px!important}
    .pdf-output .print-meta{display:flex!important;justify-content:space-between!important;font-size:10px!important;margin-bottom:10px!important}
    .pdf-output section{margin-bottom:12px!important;page-break-inside:avoid}
    .pdf-output section h4{font-size:11px!important;font-weight:700!important;border-bottom:1px solid #cbd5e1!important;padding-bottom:4px!important;margin:0!important}
    .pdf-output section > div{display:block!important;width:100%!important;margin-top:8px!important;overflow:hidden!important}
    .pdf-output section > div:after{content:'';display:block;clear:both}
    .pdf-output section > div > div{display:block!important;float:left!important;width:50%!important;font-size:10px!important;line-height:1.25!important;min-height:14px!important}
    .pdf-output section > div > div:nth-child(odd){clear:both!important}
    .pdf-output section > div > div span{display:inline-block!important;vertical-align:top!important;font-size:10px!important;line-height:1.25!important}
    .pdf-output section > div > div span:first-child{width:115px!important;color:#64748b!important}
    .pdf-output section > div > div span:nth-child(2){width:8px!important}
    .pdf-output section > div > div span:last-child{width:calc(100% - 123px)!important;font-weight:400!important;overflow-wrap:anywhere!important}
    .pdf-output .signature-area{display:table!important;width:100%!important;table-layout:fixed!important;margin-top:22px!important;page-break-inside:avoid!important}
    .pdf-output .signature-box{display:table-cell!important;width:50%!important;vertical-align:top!important;text-align:center!important;font-size:10px!important}
    .pdf-output .signature-box:first-child{padding-right:22px!important}
    .pdf-output .signature-box:last-child{padding-left:22px!important}
    .pdf-output .signature-box .city{margin-bottom:44px!important}
    .pdf-output .signature-box .line{border-bottom:1px solid #1f2937!important;padding-bottom:3px!important}
    .pdf-output .pdf-fields{width:100%!important;border-collapse:collapse!important;margin-top:8px!important;table-layout:fixed!important}
    .pdf-output .pdf-fields td{width:50%!important;vertical-align:top!important;padding:2px 14px 2px 0!important;font-size:10px!important;line-height:1.25!important}
    .pdf-output .pdf-fields td:nth-child(2){padding-left:14px!important}
    .pdf-output .pdf-field-label{display:inline-block!important;width:115px!important;color:#64748b!important;vertical-align:top!important}
    .pdf-output .pdf-field-separator{display:inline-block!important;width:8px!important;vertical-align:top!important}
    .pdf-output .pdf-field-value{display:inline!important;font-weight:400!important;overflow-wrap:anywhere!important}
</style>
@endif
@php
    $isPesertaPage = request()->routeIs('peserta.*');
@endphp
@unless($isPdf)<div class="print-actions mx-auto mb-4 flex max-w-4xl flex-wrap items-center justify-between gap-3">
        <div><p class="text-xs font-black uppercase tracking-widest text-emerald-700">Formulir Pendaftaran</p><h3 class="mt-1 text-xl font-black text-slate-900">Data Calon Peserta Didik</h3><p class="mt-1 text-sm text-slate-500">No. Pendaftaran: {{ $pendaftar->registration_number ?? 'Belum dibuat' }}</p></div>
        <div class="flex flex-wrap justify-end gap-2">
            <a data-no-loading target="_blank" rel="noopener" href="{{ ($pdfUrl ?? route('peserta.pdf')).'?inline=1' }}" class="rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-black text-white">Cetak Formulir</a>
            <a id="simpan-pdf" data-no-loading download href="{{ $pdfUrl ?? route('peserta.pdf') }}" class="rounded-2xl border border-emerald-200 bg-white px-5 py-3 text-sm font-black text-emerald-700">Simpan sebagai PDF</a>
            <a href="{{ $returnUrl }}" class="rounded-2xl bg-slate-100 px-5 py-3 text-sm font-black text-slate-700">Kembali</a>
        </div>
</div>@endunless
@if(request()->boolean('autoprint'))
<script>
    window.addEventListener('load', () => window.location.replace(@json(($pdfUrl ?? route('peserta.pdf')).'?inline=1')));
</script>
@endif
<div class="print-sheet {{ $isPdf ? 'pdf-output' : '' }} max-w-4xl rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
    @if(!empty($settings['letterhead_path']))
        <img class="official-letterhead" src="{{ $letterheadSrc ?? asset($settings['letterhead_path']) }}" alt="Kop surat sekolah">
    @else
    <div class="letterhead">
        <img src="{{ asset($settings['school_logo'] ?? 'images/logo-sekolah.png') }}" alt="Logo sekolah">
        <div class="letterhead-text"><div class="brand">{{ $settings['brand_name'] ?? 'SPMB ONLINE' }}</div><div class="school">{{ $settings['school_name'] ?? 'Nama Sekolah' }}</div><div class="address">{{ $settings['school_address'] ?? '' }} · Telp/WA {{ $settings['contact_phone'] ?? '' }}</div></div>
    </div>
    @endif
    <div class="form-title"><h1>Formulir Pendaftaran Murid Baru</h1><p>Tahun Pelajaran {{ $pendaftar->gelombangPendaftaran?->tahunAjaran?->name ?? '2026/2027' }}</p></div>
    <div class="print-meta"><span>No. Pendaftaran: <strong>{{ $pendaftar->registration_number ?? 'Belum dibuat' }}</strong></span><span>Status: <strong>{{ ucfirst($pendaftar->registration_status ?? 'draft') }}</strong></span></div>
    <section class="mb-6 break-inside-avoid"><h4 class="border-b border-slate-200 pb-2 text-sm font-black uppercase tracking-wide text-slate-700">Pilihan Pendaftaran</h4><table class="pdf-fields"><tr><td><span class="pdf-field-label">Jurusan Pilihan 1</span><span class="pdf-field-separator">:</span><span class="pdf-field-value">{{ $pendaftar->jurusan1?->name ?: '-' }}</span></td><td><span class="pdf-field-label">Jurusan Pilihan 2</span><span class="pdf-field-separator">:</span><span class="pdf-field-value">{{ $pendaftar->jurusan2?->name ?: '-' }}</span></td></tr><tr><td><span class="pdf-field-label">Jalur Pendaftaran</span><span class="pdf-field-separator">:</span><span class="pdf-field-value">{{ $pendaftar->jalurPendaftaran?->name ?: '-' }}</span></td><td></td></tr></table></section>
    @foreach($groups as $group => $fields)
        @continue(in_array($group, ['Wali', 'Dokumen Pendukung'], true))
        @php $visible = collect($fields)->except(['jurusan'])->filter(fn($label, $key) => in_array($key, $enabledFields, true)); @endphp
        @if($visible->isNotEmpty())
            <section class="mb-6 break-inside-avoid">
                <h4 class="border-b border-slate-200 pb-2 text-sm font-black uppercase tracking-wide text-slate-700">Data {{ $group }}</h4>
                @if($isPdf)
                    <table class="pdf-fields"><tbody>
                        @foreach($visible->chunk(2) as $row)
                            <tr>
                                @foreach($row as $key => $label)
                                    <td><span class="pdf-field-label">{{ $label }}</span><span class="pdf-field-separator">:</span><span class="pdf-field-value">{{ $value($key) ?: '-' }}</span></td>
                                @endforeach
                                @if($row->count() === 1)
                                    <td></td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody></table>
                @else
                    <div class="mt-3 grid gap-x-6 gap-y-2 sm:grid-cols-2">
                        @foreach($visible as $key => $label)
                            <div class="flex gap-2 text-sm"><span class="w-40 shrink-0 text-slate-500">{{ $label }}</span><span>:</span><span class="font-normal text-slate-900">{{ $value($key) ?: '-' }}</span></div>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif
    @endforeach
    <div class="signature-area"><div class="signature-box"><div>Mengetahui,</div><div class="city">Orang Tua/Wali</div><div class="line">&nbsp;</div><div class="role">Nama terang dan tanda tangan</div></div><div class="signature-box"><div>Cileungsi, ........................</div><div class="city">Calon Siswa</div><div class="line">&nbsp;</div><div class="role">Nama terang dan tanda tangan</div></div></div>
</div>
@endsection
