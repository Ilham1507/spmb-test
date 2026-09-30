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
    $billName = strtolower((string) $bill?->jenisTagihan?->name);
    $isReRegistration = str_contains($billName, 'daftar ulang') || preg_match('/(^|\\s)du(\\s|$)/', $billName);
    $selectedItems = collect($transaction->selected_items ?? [])
        ->map(fn ($item) => is_array($item) ? $item : ['name' => (string) $item, 'amount' => 0])
        ->filter(fn ($item) => filled($item['name'] ?? null));
    $billItems = collect($bill?->rincian_biaya ?? [])->keyBy(fn ($item) => (string) ($item['name'] ?? ''));
    $studentGroups = $selectedItems
        ->map(function ($item) use ($billItems) {
            $source = $billItems->get((string) $item['name'], []);
            return [
                'name' => trim((string) ($source['category'] ?? '')) ?: 'Biaya daftar ulang',
                'amount' => (float) ($item['amount'] ?? 0),
            ];
        })
        ->groupBy('name')
        ->map(fn ($items, $category) => ['name' => $category, 'amount' => (float) $items->sum('amount')])
        ->values();
    $proofTitle = $isReRegistration ? 'BUKTI PEMBAYARAN DAFTAR ULANG' : 'BUKTI PEMBAYARAN FORMULIR';
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $invoiceNumber }} · {{ $student?->registration_number }}</title>
    <style>
        /* This document is sent as a compact receipt in WhatsApp.  Do not give
           the invoice its own A4-sized minimum height: combined with the PDF
           page margin it made an otherwise short receipt spill into extra pages. */
        @page { size: A4 portrait; margin: 8mm; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #edf1f6; color: #172033; font-family: Arial, sans-serif; font-size: 12px; line-height: 1.45; }
        .invoice { width: 100%; margin: 0; background: #fff; }
        .letterhead { display: block; width: 100%; height: auto; max-height: 38mm; object-fit: fill; }
        .content { padding: 17mm 17mm 14mm; }
        .head { display: table; width: 100%; padding-bottom: 13px; border-bottom: 2px solid #163f7d; }
        .head > div { display: table-cell; vertical-align: top; }
        .head > div:last-child { text-align: right; }
        .kicker { margin: 0 0 5px; color: #8a6500; font-size: 10px; font-weight: 700; letter-spacing: 1px; }
        h1 { margin: 0; color: #102d61; font-size: 24px; line-height: 1.18; }
        .sub { margin: 4px 0 0; color: #64748b; font-size: 11px; }
        .number { display: inline-block; padding: 8px 10px; border: 1px solid #b8c8df; color: #102d61; font-size: 11px; font-weight: 700; }
        .status { display: inline-block; margin-top: 7px; padding: 6px 10px; background: #e9f7ee; color: #147044; font-size: 10px; font-weight: 700; }
        .section { margin: 25px 0 8px; color: #64748b; font-size: 11px; font-weight: 700; letter-spacing: .6px; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; }
        .identity td { width: 50%; padding: 12px 13px; border: 1px solid #d8e1ee; background: #f8fafd; }
        .label { display: block; margin-bottom: 3px; color: #748299; font-size: 9px; font-weight: 700; text-transform: uppercase; }
        .value { font-size: 13px; font-weight: 700; }
        .payment th { padding: 11px; background: #102d61; color: #fff; font-size: 10px; text-align: left; letter-spacing: .35px; }
        .payment td { padding: 13px 11px; border: 1px solid #d8e1ee; vertical-align: top; }
        .amount { text-align: right; white-space: nowrap; }
        .muted { color: #718096; font-size: 10px; }
        .total { display: table; width: 100%; margin-top: 18px; padding: 17px 18px; border: 1px solid #bddfd8; background: #eef7f5; }
        .total > span { display: table-cell; vertical-align: middle; color: #37786d; font-size: 10px; font-weight: 700; letter-spacing: .5px; }
        .total > strong { display: table-cell; color: #08796d; font-size: 27px; text-align: right; }
        .verification, .next { margin-top: 22px; padding: 14px 16px; }
        .verification { border-left: 4px solid #163f7d; background: #f5f8fc; }
        .next { margin-top: 14px; border-left: 4px solid #d8a900; background: #fff9e7; }
        .verification p, .next p { margin: 2px 0; }
        .notice { margin: 20px 0 0; color: #66758b; font-size: 11px; }
        .footer { margin-top: 0; padding: 10px 17mm; background: #102d61; color: #fff; font-size: 9px; }
    </style>
</head>
<body>
    <main class="invoice">
        @if($letterheadSrc)
            <img class="letterhead" src="{{ $letterheadSrc }}" alt="Kop surat resmi sekolah">
        @endif
        <div class="content">
            <section class="head">
                <div><p class="kicker">SISTEM PENERIMAAN MURID BARU</p><h1>{{ $proofTitle }}</h1><p class="sub">Dokumen pembayaran resmi SPMB Tahun Ajaran 2027/2028</p></div>
                <div><span class="number">{{ $invoiceNumber }}</span><br><span class="status">DISETUJUI PANITIA</span></div>
            </section>
            <p class="section">Data calon siswa</p>
            <table class="identity"><tr><td><span class="label">Nama calon siswa</span><span class="value">{{ $name }}</span></td><td><span class="label">Nomor pendaftaran</span><span class="value">{{ $student?->registration_number ?? '-' }}</span></td></tr></table>
            <p class="section">Rincian transaksi</p>
            @if($isReRegistration)
                <table class="payment"><thead><tr><th>KELOMPOK BIAYA</th><th class="amount">NOMINAL</th></tr></thead><tbody>
                    @forelse($studentGroups as $group)
                        <tr><td>{{ $group['name'] }}</td><td class="amount"><strong>Rp {{ number_format($group['amount'], 0, ',', '.') }}</strong></td></tr>
                    @empty
                        <tr><td colspan="2">Kelompok biaya akan ditetapkan bendahara setelah pembayaran diterima.</td></tr>
                    @endforelse
                </tbody></table>
                <p class="notice">Metode: {{ $channel }} · Referensi: {{ $reference ?: '-' }} · Tercatat {{ $paidAt->translatedFormat('d F Y, H:i') }} WIB</p>
            @else
                <table class="payment"><thead><tr><th>TAGIHAN</th><th>METODE</th><th>REFERENSI</th><th class="amount">NOMINAL</th></tr></thead><tbody><tr><td>{{ $bill?->jenisTagihan?->name ?? 'Pembayaran formulir SPMB' }}</td><td>{{ $channel }}</td><td>{{ $reference ?: '-' }}<br><span class="muted">{{ $paidAt->translatedFormat('d F Y, H:i') }} WIB</span></td><td class="amount"><strong>Rp {{ number_format($transaction->amount, 0, ',', '.') }}</strong></td></tr></tbody></table>
            @endif
            <div class="total"><span>TOTAL PEMBAYARAN DITERIMA</span><strong>Rp {{ number_format($receivedAmount, 0, ',', '.') }}</strong></div>
            <div class="verification"><p><strong>Persetujuan panitia:</strong> {{ $transaction->verifier?->name ?? '-' }}</p><p><strong>Status bendahara:</strong> {{ $treasurerStatus }}</p></div>
            <div class="next"><p><strong>INFORMASI LANJUTAN</strong></p><p>{{ $isReRegistration ? 'Pembayaran daftar ulang telah dicatat. Dokumen ini hanya menampilkan total kelompok biaya yang dialokasikan bendahara; rincian komponen lengkap tersedia pada dokumen BMT.' : 'Pembayaran formulir telah dicatat. Silakan lanjutkan pengisian formulir SPMB melalui tautan yang dikirimkan ke WhatsApp.' }}</p></div>
            <p class="notice">Dokumen ini diterbitkan otomatis berdasarkan transaksi yang tercatat pada Sistem SPMB dan sah sebagai bukti pembayaran elektronik.</p>
        </div>
        <footer class="footer">{{ $settings['school_name'] ?? 'SMK Muhammadiyah 4 Cileungsi' }} · {{ $settings['school_address'] ?? 'Cileungsi, Bogor' }}</footer>
    </main>
</body>
</html>
