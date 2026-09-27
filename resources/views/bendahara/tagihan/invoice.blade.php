<!doctype html>
<html lang="id"><head><meta charset="utf-8"><style>
@page{margin:0 0 7mm}*{box-sizing:border-box;font-family:DejaVu Sans,sans-serif}body{margin:0;padding:0 11mm;color:#1e293b;font-size:7pt;line-height:1.2}.letterhead{display:block;width:calc(100% + 22mm);height:auto;max-height:none;margin:0 0 1.5mm -11mm}.school-head{min-height:17mm;padding:0 0 1.5mm;border-bottom:1.5px solid #0f4c81}.logo{float:left;width:13mm;height:13mm;margin-right:3mm;object-fit:contain}.school-head h1{margin:0;color:#0f315e;font-size:11pt;font-weight:bold;text-transform:uppercase}.school-head p{margin:.5mm 0 0;color:#64748b;font-size:6pt}.clear{clear:both}.document-title{margin:1.5mm 0 1mm;text-align:center}.document-title h2{margin:0;color:#0f315e;font-size:10pt;font-weight:bold;line-height:1.1;text-transform:uppercase;letter-spacing:.2px}.document-title p{margin:.5mm 0 0;color:#52637d;font-size:6.5pt;font-weight:normal;line-height:1.2}.reference{margin:0 0 1mm;padding:1mm 2mm;border:1px solid #cbd9e8;background:#f5f9ff;color:#173f70;font-size:6.5pt;font-weight:bold}.main-table,.financial-table{width:100%;border-collapse:collapse;table-layout:fixed}.main-table td{padding:.65mm 1.2mm;vertical-align:top}.main-table .number{width:5%;color:#64748b;text-align:center;font-weight:bold}.main-table .label{width:31%;color:#475569}.main-table .colon{width:3%;color:#64748b;text-align:center}.main-table .value{width:61%;color:#172033;font-weight:bold}.parent-item{display:table;width:100%;line-height:1.25}.parent-marker{display:table-cell;width:4mm;font-weight:bold}.parent-label{display:table-cell;width:13mm}.parent-colon{display:table-cell;width:3mm;text-align:center}.parent-name{display:table-cell}.section-title{margin:1.5mm 0 .7mm;color:#0f315e;font-size:7.5pt;font-weight:bold;text-transform:uppercase;letter-spacing:.2px}.financial-columns{display:table;width:100%;table-layout:fixed;border-spacing:0}.financial-column{display:table-cell;width:50%;vertical-align:top}.financial-column:first-child{padding-right:.75mm}.financial-column:last-child{padding-left:.75mm}.financial-table{border:1px solid #cbd9e8}.financial-table th{padding:1mm 1.5mm;background:#0f315e;color:#fff;font-size:6pt;text-align:left;text-transform:uppercase}.financial-table td{padding:.85mm 1.5mm;border-top:1px solid #dce6f0;vertical-align:top}.financial-table .right{text-align:right}.financial-table .total td{background:#f6f9fc;font-weight:bold}.financial-table .remaining td{background:#edf8f6;color:#075d55;font-size:8pt;font-weight:bold}.summary-table{margin-top:1.2mm}.note{margin-top:1.5mm;padding:1.3mm 2mm;border:1px solid #dce6f0;background:#f8fafc;color:#52637d;font-size:6.2pt}.signature{width:100%;margin-top:2mm;border-collapse:collapse}.signature td{width:50%;vertical-align:top;text-align:center;color:#475569;font-size:6.5pt}.signature .space{height:7mm}.signature strong{color:#172033;font-weight:bold}.signature .line{width:42mm;margin:0 auto .7mm;border-top:1px solid #64748b}.footer{margin-top:1.5mm;padding-top:1mm;border-top:1px solid #dce6f0;color:#7c8aa0;text-align:center;font-size:5.5pt}
body{font-size:7pt;line-height:1.15}.school-head p{color:#334155;font-size:6.2pt}.document-title p{color:#334155;font-size:6.5pt}.reference{border-color:#b8cbe0;font-size:6.5pt;padding:.7mm 1.5mm;text-align:center}.main-table td{padding:.48mm 1mm}.main-table .number,.main-table .label,.main-table .colon{color:#334155}.main-table .value{color:#0f172a}.parent-item{line-height:1.15}.financial-table{border-color:#b8cbe0}.financial-table th{font-size:6pt;padding:.75mm 1.2mm}.financial-table td{color:#1e293b;border-top-color:#cbd5e1;padding:.6mm 1.2mm}.section-title{margin:1.1mm 0 .5mm}.summary-table{margin-top:2.2mm}.note{color:#334155;border-color:#cbd5e1;font-size:6.2pt;padding:1mm 1.5mm;margin-top:1mm}.signature{margin-top:2mm}.signature td{color:#334155;font-size:6.5pt}.signature .space{height:12mm}.footer{color:#475569;border-top-color:#cbd5e1;font-size:5.7pt;margin-top:1mm;padding-top:.7mm}</style></head><body>
@php
    $student = $bill->pendaftar;
    $biodata = $student?->biodata;
    $address = $student?->alamat;
    $parents = collect([
        filled($student?->dataAyah?->name) ? ['label' => 'Ayah', 'name' => $student->dataAyah->name] : null,
        filled($student?->dataIbu?->name) ? ['label' => 'Ibu', 'name' => $student->dataIbu->name] : null,
        filled($student?->dataWali?->name) ? ['label' => 'Wali', 'name' => $student->dataWali->name] : null,
    ])->filter()->values();
    $phone = $student?->user?->phone ?? '-';
    $studentName = $biodata?->full_name ?? $student?->user?->name ?? 'Peserta';
    $status = $bill->remaining_amount <= 0 ? 'Lunas' : ((float) $bill->paid_amount > 0 ? 'Cicilan' : 'Belum dibayar');
    $promotionTransactions = $bill->transaksi->filter(fn ($transaction) => (float) ($transaction->discount_amount ?? 0) > 0);
    $promotionDiscount = (float) $promotionTransactions->sum('discount_amount');
    $promotionNames = $promotionTransactions->pluck('promotion_name')->filter()->unique()->values();
    $promotionLabel = $promotionNames->isNotEmpty() ? $promotionNames->implode(', ') : 'Tidak ada potongan';
    $paymentSequence = max(1, $bill->transaksi->where('amount', '>', 0)->count());
    $paymentItems = collect($summary['items']);
    $leftPaymentItems = $paymentItems->slice(0, (int) ceil($paymentItems->count() / 2));
    $rightPaymentItems = $paymentItems->slice((int) ceil($paymentItems->count() / 2));
@endphp
@if($letterheadSrc)
<img class="letterhead" src="{{ $letterheadSrc }}" alt="Kop surat sekolah">
@else
<header class="school-head">@if($logo)<img class="logo" src="{{ $logo }}" alt="Logo sekolah">@endif<h1>{{ strtoupper($settings['school_name'] ?? 'SMK Muhammadiyah 4 Cileungsi') }}</h1><p>{{ $settings['school_address'] ?? 'Dokumen pembayaran SPMB' }}<br>Telp/WA {{ $settings['contact_phone'] ?? '-' }}</p><div class="clear"></div></header>
@endif
<div class="document-title"><h2>Formulir Pembayaran BTM</h2><p>Tagihan {{ $bill->jenisTagihan?->name ?? 'Pendaftaran / Daftar Ulang' }} - SPMB</p></div>
<div class="reference">NO. BTM-{{ str_pad((string) $bill->id, 6, '0', STR_PAD_LEFT) }} &nbsp; | &nbsp; Status: {{ $status }} &nbsp; | &nbsp; Dicetak {{ now()->translatedFormat('d F Y, H:i') }} WIB</div>
<table class="main-table">
<tr><td class="number">1</td><td class="label">Nomor pendaftaran</td><td class="colon">:</td><td class="value">{{ $student?->registration_number ?? '-' }}</td></tr>
<tr><td class="number">2</td><td class="label">Nama calon siswa</td><td class="colon">:</td><td class="value">{{ $studentName }}</td></tr>
<tr><td class="number">3</td><td class="label">Jenis kelamin</td><td class="colon">:</td><td class="value">{{ $biodata?->gender === 'P' ? 'Perempuan' : ($biodata?->gender === 'L' ? 'Laki-laki' : '-') }}</td></tr>
<tr><td class="number">4</td><td class="label">Tempat, tanggal lahir</td><td class="colon">:</td><td class="value">{{ $biodata?->birth_place ?? '-' }}{{ $biodata?->birth_date ? ', '.\Illuminate\Support\Carbon::parse($biodata->birth_date)->translatedFormat('d F Y') : '' }}</td></tr>
<tr><td class="number">5</td><td class="label">Asal sekolah</td><td class="colon">:</td><td class="value">{{ $student?->sekolahAsal?->school_name ?? '-' }}</td></tr>
<tr><td class="number">6</td><td class="label">Alamat peserta didik</td><td class="colon">:</td><td class="value">{{ $address?->address ?? '-' }}{{ $address?->rt || $address?->rw ? ' RT '.$address?->rt.' / RW '.$address?->rw : '' }}{{ $address?->village ? ', '.$address->village : '' }}{{ $address?->district ? ', '.$address->district : '' }}</td></tr>
<tr><td class="number">7</td><td class="label">Nomor telepon / WhatsApp</td><td class="colon">:</td><td class="value">{{ $phone }}</td></tr>
<tr><td class="number">8</td><td class="label">Orang tua / wali</td><td class="colon">:</td><td class="value">@forelse($parents as $parent)<span class="parent-item"><span class="parent-marker">{{ chr(97 + $loop->index) }}.</span><span class="parent-label">{{ $parent['label'] }}</span><span class="parent-colon">:</span><span class="parent-name">{{ $parent['name'] }}</span></span>@empty-@endforelse</td></tr>
<tr><td class="number">9</td><td class="label">Jalur pendaftaran</td><td class="colon">:</td><td class="value">{{ $student?->jalurPendaftaran?->name ?? '-' }}</td></tr>
<tr><td class="number">10</td><td class="label">Pilihan jurusan</td><td class="colon">:</td><td class="value">{{ $student?->jurusan1?->name ?? '-' }}</td></tr>
</table>
<p class="section-title">Rincian pembayaran</p>
@if($paymentItems->isEmpty())
<table class="financial-table"><thead><tr><th>Rincian biaya</th><th class="right">Nominal</th><th class="right">Keterangan</th></tr></thead><tbody><tr><td>{{ $bill->jenisTagihan?->name ?? 'Tagihan SPMB' }}</td><td class="right">Rp {{ number_format($bill->total_amount,0,',','.') }}</td><td class="right">{{ $bill->remaining_amount <= 0 ? 'Lunas' : 'Belum lunas' }}</td></tr></tbody></table>
@else
<div class="financial-columns">
    @foreach([$leftPaymentItems, $rightPaymentItems] as $columnItems)
    <div class="financial-column"><table class="financial-table"><thead><tr><th>Rincian biaya</th><th class="right">Nominal</th><th class="right">Ket.</th></tr></thead><tbody>
        @foreach($columnItems as $item)
            @php($displayStatus = $item['status'] === 'Lunas' ? 'Lunas' : 'Belum lunas')
            <tr><td>{{ $item['name'] }}</td><td class="right">Rp {{ number_format($item['amount'],0,',','.') }}</td><td class="right">{{ $displayStatus }}</td></tr>
        @endforeach
    </tbody></table></div>
    @endforeach
</div>
@endif
<table class="financial-table summary-table"><tbody><tr class="total"><td>Total tagihan</td><td class="right">Rp {{ number_format($bill->total_amount,0,',','.') }}</td><td class="right">-</td></tr>
<tr><td>Jenis dan besar potongan</td><td class="right">{{ $promotionDiscount > 0 ? '- Rp '.number_format($promotionDiscount,0,',','.') : 'Rp 0' }}</td><td class="right">{{ $promotionLabel }}</td></tr>
<tr><td>Pembayaran ke</td><td class="right">{{ $paymentSequence }}</td><td class="right">{{ $paymentSequence > 1 ? 'Cicilan' : 'Pembayaran pertama' }}</td></tr>
<tr><td>Jumlah sudah dibayarkan</td><td class="right">Rp {{ number_format($bill->paid_amount,0,',','.') }}</td><td class="right">{{ $status }}</td></tr>
<tr class="remaining"><td>Jumlah yang harus dibayar</td><td class="right">Rp {{ number_format($bill->remaining_amount,0,',','.') }}</td><td class="right">{{ $bill->remaining_amount <= 0 ? 'LUNAS' : 'SISA TAGIHAN' }}</td></tr>
</tbody></table>
<div class="note">Serahkan lembar ini kepada petugas BTM. Nominal, status, serta riwayat pembayaran diambil otomatis dari Sistem SPMB. Setelah pembayaran diterima, petugas mencatat transaksi dan siswa menyimpan bukti pembayaran.</div>
<table class="signature"><tr><td colspan="2">Cileungsi, {{ now()->translatedFormat('d F Y') }}</td></tr><tr><td>Bendahara I</td><td>Bendahara II</td></tr><tr><td class="space"></td><td class="space"></td></tr><tr><td><div class="line"></div><strong>apt. Krismiyati, S.Farm.</strong></td><td><div class="line"></div><strong>Ratri Kuswarini</strong></td></tr></table>
<p class="footer">Dokumen pembayaran ini dibuat otomatis oleh Sistem SPMB {{ $settings['school_name'] ?? 'sekolah' }} untuk proses BTM.</p>
</body></html>
