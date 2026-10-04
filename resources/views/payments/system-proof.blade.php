@php
    $bill = $transaction->tagihan;
    $student = $bill?->pendaftar;
    $name = $student?->biodata?->full_name ?? $student?->user?->name ?? 'Calon siswa';
    $checkout = $transaction->checkout;
    $channel = $checkout
        ? \App\Support\PaymentChannel::label($checkout->provider_payment_type, $checkout->provider_bank)
        : (['cash' => 'Tunai di sekolah', 'transfer' => 'Transfer bank'][$transaction->payment_method] ?? 'Pembayaran sekolah');
    $settings = \App\Models\SystemSetting::publicValues();
    $letterhead = $settings['letterhead_path'] ?? 'images/kop-surat-resmi.png';
    // Dompdf cannot reliably fetch an HTTP asset on Railway. Embed the
    // configured letterhead so the same official image appears in browser,
    // downloaded PDF, email attachment, and WhatsApp document.
    $letterheadFile = public_path($letterhead);
    if (! is_file($letterheadFile)) {
        $letterheadFile = public_path('images/kop-surat-resmi.png');
    }
    $letterheadSrc = is_file($letterheadFile)
        ? 'data:image/'.pathinfo($letterheadFile, PATHINFO_EXTENSION).';base64,'.base64_encode((string) file_get_contents($letterheadFile))
        : null;
    $reference = $checkout?->provider_transaction_id ?: $checkout?->order_id ?: $transaction->reference_number ?: $transaction->transaction_number;
    $invoiceNumber = 'INV-SPMB-'.str_pad((string) $transaction->id, 6, '0', STR_PAD_LEFT);
    $paidAt = \Illuminate\Support\Carbon::parse($transaction->payment_date ?? $transaction->created_at);
    $receivedAmount = (float) ($transaction->received_amount ?? $transaction->amount);
    $treasurerStatus = $transaction->treasurer_received_at
        ? 'Diterima oleh '.($transaction->treasurerReceiver?->name ?? 'Bendahara')
        : 'Menunggu penerimaan bendahara';
    $isReRegistration = \App\Support\PaymentProof::isReRegistration($transaction);
    $isBmtProof = $isReRegistration && ($bmtProof ?? false);
    $proofTitle = $isReRegistration ? 'BUKTI PEMBAYARAN DAFTAR ULANG' : 'BUKTI PEMBAYARAN FORMULIR';
    if ($isBmtProof) { $proofTitle = 'BUKTI PEMBAYARAN BTM ANNISA'; }
    $summary = $isReRegistration && $bill ? \App\Support\PaymentSummary::forBill($bill) : null;
    $studentGroups = $summary ? $summary['items']->groupBy('category') : collect();
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ pathinfo(\App\Support\PaymentProof::filename($transaction), PATHINFO_FILENAME) }}</title>
    <style>
        /* This document is sent as a compact receipt in WhatsApp.  Do not give
           the invoice its own A4-sized minimum height: combined with the PDF
           page margin it made an otherwise short receipt spill into extra pages. */
        @page { size: A4 portrait; margin: 8mm 8mm 18mm; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #fff; color: #172033; font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; line-height: 1.4; }
        .invoice { width: 100%; margin: 0; background: #fff; }
        .letterhead { display: block; width: 100%; height: auto; max-height: 38mm; object-fit: fill; }
        .content { padding: 3mm 5mm; }
        .head { display: table; width: 100%; padding-bottom: 8px; border-bottom: 2px solid #163f7d; }
        .head > div { display: table-cell; vertical-align: top; }
        .head > div:last-child { text-align: right; }
        .kicker { margin: 0 0 5px; color: #8a6500; font-size: 10px; font-weight: 700; letter-spacing: 1px; }
        h1 { margin: 0; color: #102d61; font-size: 18px; line-height: 1.25; }
        .sub { margin: 4px 0 0; color: #64748b; font-size: 11px; }
        .number { display: inline-block; padding: 8px 10px; border: 1px solid #b8c8df; color: #102d61; font-size: 11px; font-weight: 700; }
        .status { display: inline-block; margin-top: 7px; padding: 6px 10px; background: #e9f7ee; color: #147044; font-size: 10px; font-weight: 700; }
        .section { margin: 14px 0 7px; color: #64748b; font-size: 10px; font-weight: 700; letter-spacing: .6px; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; }
        .identity td { width: 50%; padding: 8px 10px; border: 1px solid #d8e1ee; background: #f8fafd; }
        .label { display: block; margin-bottom: 3px; color: #748299; font-size: 9px; font-weight: 700; text-transform: uppercase; }
        .value { font-size: 13px; font-weight: 700; }
        .payment th { padding: 7px 5px; background: #102d61; color: #fff; font-size: 9px; text-align: left; }
        .payment td { padding: 4px 5px; border: 1px solid #d8e1ee; vertical-align: top; }
        .payment thead { display: table-header-group; }
        .payment tr, .total, .verification, .next { page-break-inside: avoid; }
        .payment .group td { background: #edf3fa; font-weight: bold; }
        .payment .group { page-break-after: avoid; }
        .fee-columns { table-layout: fixed; }
        .fee-columns > tbody > tr > td { width: 49%; vertical-align: top; padding: 0; }
        .fee-columns > tbody > tr > td.gutter { width: 2%; }
        .fee-columns .payment { table-layout: fixed; font-size: 9px; }
        .fee-columns .payment th { padding: 5px 3px; font-size: 8px; overflow-wrap: break-word; }
        .fee-columns .payment td { padding: 4px 3px; overflow-wrap: break-word; }
        .fee-columns .payment .fee-name { width: 32%; }
        .fee-columns .payment .fee-amount { width: 16%; }
        .fee-columns .payment .fee-status { width: 20%; }
        .bmt-proof { font-size: 9px; line-height: 1.3; }
        .bmt-proof .content { padding: 2mm 4mm; }
        .bmt-proof h1 { font-size: 16px; }
        .bmt-proof .value { font-size: 11px; }
        .bmt-proof .footer { padding: 7px 17mm; font-size: 8px; }
        .bmt-proof .section { margin: 8px 0 5px; }
        .bmt-proof .identity td { padding: 5px 8px; }
        .bmt-proof .fee-columns .payment td { padding: 1px 3px; }
        .bmt-proof .balances td { padding: 4px 8px; }
        .bmt-proof .notice, .bmt-proof .muted { margin: 6px 0 0; font-size: 9px; }
        .bmt-proof .total, .bmt-proof .verification, .bmt-proof .next { margin-top: 6px; padding: 6px 10px; }
        .bmt-proof .total > strong { font-size: 19px; }
        .balances td { padding: 8px; border-bottom: 1px solid #d8e1ee; }
        .amount { text-align: right; white-space: nowrap; }
        .muted { color: #718096; font-size: 10px; }
        .total { display: table; width: 100%; margin-top: 10px; padding: 10px 12px; border: 1px solid #bddfd8; background: #eef7f5; }
        .total > span { display: table-cell; vertical-align: middle; color: #37786d; font-size: 10px; font-weight: 700; letter-spacing: .5px; }
        .total > strong { display: table-cell; color: #08796d; font-size: 22px; text-align: right; }
        .verification, .next { margin-top: 10px; padding: 10px 12px; }
        .verification { border-left: 4px solid #163f7d; background: #f5f8fc; }
        .next { margin-top: 10px; border-left: 4px solid #d8a900; background: #fff9e7; }
        .verification p, .next p { margin: 2px 0; }
        .notice { margin: 10px 0 0; color: #66758b; font-size: 10px; }
        .footer { position: fixed; bottom: -10mm; left: 0; right: 0; margin: 0; padding: 10px 17mm; background: #102d61; color: #fff; font-size: 9px; }
    </style>
</head>
<body>
    <main class="invoice{{ $isBmtProof ? ' bmt-proof' : '' }}">
        <footer class="footer">{{ $settings['school_name'] ?? 'SMK Muhammadiyah 4 Cileungsi' }} · {{ $settings['school_address'] ?? 'Cileungsi, Bogor' }}</footer>
        @if($letterheadSrc)
            <img class="letterhead" src="{{ $letterheadSrc }}" alt="Kop surat resmi sekolah">
        @endif
        <div class="content">
            <section class="head">
                <div><p class="kicker">SISTEM PENERIMAAN MURID BARU</p><h1>{{ $proofTitle }}</h1><p class="sub">{{ $transaction->treasurer_received_at ? 'Pembayaran diterima BMT sekolah' : 'Dokumen pembayaran resmi SPMB' }}</p></div>
                <div><span class="number">{{ $invoiceNumber }}</span><br><span class="status">{{ $transaction->treasurer_received_at ? 'DITERIMA BMT' : 'DISETUJUI PANITIA' }}</span></div>
            </section>
            <p class="section">Data calon siswa</p>
            <table class="identity"><tr><td><span class="label">Nama calon siswa</span><span class="value">{{ $name }}</span></td><td><span class="label">Nomor pendaftaran</span><span class="value">{{ $student?->registration_number ?? '-' }}</span></td></tr></table>
            <p class="section">Rincian transaksi</p>
            @if($isReRegistration)
                    @if($isBmtProof)
                        @php $feeColumns = $summary['items']->values()->split(2); @endphp
                        <table class="fee-columns"><tbody><tr>
                        @foreach($feeColumns as $column)
                            @if(!$loop->first)<td class="gutter"></td>@endif
                            <td><table class="payment"><thead><tr><th class="fee-name">Rincian biaya</th><th class="fee-amount">Tagihan<br>(Rp)</th><th class="fee-amount">Selesai<br>(Rp)</th><th class="fee-amount">Sisa<br>(Rp)</th><th class="fee-status">Status</th></tr></thead><tbody>
                        @foreach($column as $item)
                            @php
                                $itemStatus = $item['remaining'] <= 0 ? 'Lunas' : ($item['settled'] > 0 ? 'Belum lunas (sebagian)' : 'Belum lunas');
                                if ($summary['unallocated']) { $itemStatus = 'Perlu dicocokkan'; }
                            @endphp
                            <tr><td>{{ $item['name'] }}</td><td class="amount">{{ number_format($item['amount'], 0, ',', '.') }}</td><td class="amount">{{ $summary['unallocated'] ? '-' : number_format($item['settled'], 0, ',', '.') }}</td><td class="amount">{{ $summary['unallocated'] ? '-' : number_format($item['remaining'], 0, ',', '.') }}</td><td>{{ $itemStatus }}</td></tr>
                        @endforeach
                            </tbody></table></td>
                        @endforeach
                        </tr></tbody></table>
                    @else
                <table class="payment"><thead><tr><th>Rincian biaya</th><th class="amount">Tagihan (Rp)</th><th class="amount">Diselesaikan (Rp)</th><th class="amount">Sisa (Rp)</th><th>Status</th></tr></thead><tbody>
                    @forelse($studentGroups as $category => $items)
                        @php
                            $groupStatus = $items->every(fn ($item) => $item['status'] === 'Lunas') ? 'Lunas' : ($items->sum('settled') > 0 ? 'Belum lunas (sebagian)' : 'Belum lunas');
                            if ($summary['unallocated']) { $groupStatus = 'Perlu dicocokkan'; }
                        @endphp
                        <tr class="group"><td>{{ $category }}</td><td class="amount">{{ number_format($items->sum('amount'), 0, ',', '.') }}</td><td class="amount">{{ $summary['unallocated'] ? '-' : number_format($items->sum('settled'), 0, ',', '.') }}</td><td class="amount">{{ $summary['unallocated'] ? '-' : number_format($items->sum('remaining'), 0, ',', '.') }}</td><td>{{ $groupStatus }}</td></tr>
                    @empty
                        <tr><td colspan="5">Rincian biaya belum tersedia. Hubungi bendahara untuk pencocokan.</td></tr>
                    @endforelse
                </tbody></table>
                    @endif
                <p class="notice">Metode: {{ $channel }} · Referensi: {{ $reference ?: '-' }} · Tercatat {{ $paidAt->translatedFormat('d F Y, H:i') }} WIB</p>
                @if($isBmtProof)
                    <p class="muted">Selesai = pembayaran disetujui + potongan/kredit yang berlaku.</p>
                @else
                    <p class="muted">Diselesaikan = pembayaran disetujui + potongan/kredit yang berlaku.</p>
                @endif
                @if($summary['unallocated'])
                    <p class="notice">Alokasi pembayaran lama perlu dicocokkan bendahara.</p>
                @endif
                <table class="balances"><tr><td>Total tagihan daftar ulang (setelah potongan)</td><td class="amount">Rp {{ number_format($bill->total_amount, 0, ',', '.') }}</td></tr><tr><td>Akumulasi pembayaran daftar ulang</td><td class="amount">Rp {{ number_format($bill->paid_amount, 0, ',', '.') }}</td></tr><tr><td><strong>Sisa tagihan daftar ulang</strong></td><td class="amount"><strong>Rp {{ number_format(max(0, $bill->remaining_amount ?? ($bill->total_amount - $bill->paid_amount)), 0, ',', '.') }}</strong></td></tr></table>
            @else
                <table class="payment"><thead><tr><th>TAGIHAN</th><th>METODE</th><th>REFERENSI</th><th class="amount">NOMINAL</th></tr></thead><tbody><tr><td>{{ $bill?->jenisTagihan?->name ?? 'Pembayaran formulir SPMB' }}</td><td>{{ $channel }}</td><td>{{ $reference ?: '-' }}<br><span class="muted">{{ $paidAt->translatedFormat('d F Y, H:i') }} WIB</span></td><td class="amount"><strong>Rp {{ number_format($transaction->amount, 0, ',', '.') }}</strong></td></tr></tbody></table>
            @endif
            <div class="total"><span>PEMBAYARAN TRANSAKSI INI</span><strong>Rp {{ number_format($receivedAmount, 0, ',', '.') }}</strong></div>
            <div class="verification"><p><strong>{{ $transaction->treasurer_received_at ? 'Petugas persetujuan:' : 'Persetujuan panitia:' }}</strong> {{ $transaction->verifier?->name ?? '-' }}</p><p><strong>Status bendahara:</strong> {{ $treasurerStatus }}</p></div>
            @if($transaction->treasurer_received_at)
                <p class="notice">Tanggal penerimaan BMT: {{ \Illuminate\Support\Carbon::parse($transaction->treasurer_received_at)->locale('id')->translatedFormat('d F Y, H:i') }} WIB</p>
            @endif
            @unless($isBmtProof)
            <div class="next"><p>{{ $isReRegistration ? 'Pembayaran berikutnya di BMT sekolah: Selasa dan Jumat, 07.30-14.30 WIB.' : 'Pembayaran tercatat. Silakan lanjutkan pengisian formulir SPMB.' }}</p></div>
            @endunless
        </div>
    </main>
</body>
</html>
