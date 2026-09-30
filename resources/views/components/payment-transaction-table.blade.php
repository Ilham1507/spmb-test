@props(['transactions', 'routePrefix', 'accent' => 'amber', 'showPagination' => false])

@php
    $items = $transactions instanceof \Illuminate\Pagination\AbstractPaginator ? $transactions->getCollection() : $transactions;
    $waitingCount = $items->where('status', 'pending')->count();
    $total = $transactions instanceof \Illuminate\Pagination\AbstractPaginator ? $transactions->total() : $items->count();
    $canApprove = auth()->user()?->hasRole('panitia') || auth()->user()?->hasRole('bendahara') || auth()->user()?->hasRole('kepala_sekolah') || auth()->user()?->hasRole('admin');
@endphp

<section class="payment-review-section payment-review-{{ $accent }}">
    <header>
        <div>
            <p class="payment-review-kicker">PEMBAYARAN MASUK</p>
            <h3>Daftar transaksi</h3>
            <p>Aksi hanya muncul pada pembayaran yang masih menunggu pemeriksaan.</p>
        </div>
        <div class="payment-review-summary"><span>{{ $total }} transaksi</span><b>{{ $waitingCount }} menunggu</b></div>
    </header>

    <div class="payment-review-table-wrap">
        <table class="payment-review-table">
            <thead><tr><th>Peserta</th><th>Pembayaran</th><th>Bukti</th><th>Persetujuan</th><th>Penerimaan</th><th class="text-right">Aksi</th></tr></thead>
            <tbody>
                @forelse($items as $trx)
                    @php
                        $applicant = $trx->tagihan?->pendaftar;
                        $name = $applicant?->biodata?->full_name ?? $applicant?->user?->name ?? 'Peserta';
                        $isPending = $trx->status === 'pending';
                        $checkout = $trx->checkout;
                        $isOnline = $checkout && ($checkout->provider_transaction_id || $checkout->order_id);
                        $approval = match ($trx->status) {
                            'verified' => ['Disetujui', 'is-approved'],
                            'rejected' => ['Ditolak', 'is-rejected'],
                            default => ['Menunggu', 'is-pending'],
                        };
                        $reference = $checkout?->provider_transaction_id ?: $checkout?->order_id ?: $trx->transaction_number;
                        $billName = strtolower((string) $trx->tagihan?->jenisTagihan?->name);
                        $isReRegistration = str_contains($billName, 'daftar ulang') || str_contains($billName, 'du');
                        $feeItems = collect($trx->tagihan?->rincian_biaya ?? [])->filter(fn ($item) => filled($item['name'] ?? null) && (float) ($item['amount'] ?? 0) > 0);
                    @endphp
                    <tr>
                        <td><div class="payment-review-person"><span>{{ strtoupper(mb_substr($name, 0, 1)) }}</span><div><h4>{{ $name }}</h4><p>{{ $applicant?->registration_number ?? 'Belum memiliki nomor pendaftaran' }}</p></div></div></td>
                        <td><strong class="payment-amount">Rp {{ number_format($trx->amount, 0, ',', '.') }}</strong><small class="payment-date">{{ $trx->payment_date ? \Illuminate\Support\Carbon::parse($trx->payment_date)->format('d M Y, H:i') : 'Waktu belum tercatat' }}</small></td>
                        <td>
                            @if($trx->proof_file)
                                <a class="payment-proof-link" target="_blank" rel="noopener" href="{{ route($routePrefix . 'pembayaran.proof', $trx) }}">Lihat bukti <b>↗</b></a><small class="payment-proof-meta">{{ ucfirst($trx->payment_method ?: 'Transfer') }}</small>
                            @elseif($isOnline)
                                <a class="payment-proof-link" target="_blank" rel="noopener" href="{{ route($routePrefix . 'pembayaran.proof', $trx) }}">Bukti sistem <b>↗</b></a><small class="payment-proof-meta" title="{{ $reference }}">{{ $reference }}</small>
                            @else
                                <span class="payment-manual-label">Input manual</span><small class="payment-proof-meta">Tanpa berkas bukti</small>
                            @endif
                        </td>
                        <td class="payment-status-cell"><div class="payment-final-status"><b class="{{ $approval[1] }}">{{ $approval[0] }}</b><small>{{ $trx->verifier?->name ? 'oleh '.$trx->verifier->name : 'Belum diproses' }}</small></div></td>
                        <td class="payment-status-cell">
                            @if($trx->treasurer_received_at)
                                <div class="payment-final-status"><b class="is-approved">Diterima</b><small>oleh {{ $trx->treasurerReceiver?->name ?? 'Keuangan' }}</small></div>
                            @elseif($trx->status === 'verified')
                                <div class="payment-final-status"><b class="is-pending">Menunggu</b><small>penerimaan admin/bendahara</small></div>
                            @else
                                <div class="payment-final-status"><b class="is-pending">—</b><small>Belum dapat diterima</small></div>
                            @endif
                        </td>
                        <td class="payment-status-cell">
                            @if($isPending && $canApprove)
                                <div class="payment-decision-actions">
                                    @if($isReRegistration && $feeItems->isNotEmpty())
                                        <details>
                                            <summary class="cursor-pointer list-none rounded-lg bg-emerald-700 px-3 py-2 text-xs font-black text-white">Pilih rincian & setujui</summary>
                                            <form method="POST" action="{{ route($routePrefix . 'pembayaran.verify', $trx) }}" class="mt-2 w-80 rounded-2xl border border-emerald-200 bg-white p-3 text-left shadow-xl">@csrf @method('PATCH')
                                                <input type="hidden" name="status" value="verified">
                                                <p class="mb-2 text-xs font-bold text-slate-700">Pilih rincian yang dibayar untuk Rp {{ number_format($trx->amount, 0, ',', '.') }}.</p>
                                                <div class="max-h-48 space-y-2 overflow-y-auto">@foreach($feeItems as $item)<label class="flex items-center justify-between gap-2 rounded-lg border border-slate-100 px-2 py-1.5 text-xs"><span class="flex items-center gap-2"><input type="checkbox" name="selected_items[]" value="{{ $item['name'] }}"><span>{{ $item['name'] }}</span></span><b>Rp {{ number_format($item['amount'], 0, ',', '.') }}</b></label>@endforeach</div>
                                                <button type="submit" class="mt-3 w-full rounded-lg bg-emerald-700 px-3 py-2 text-xs font-black text-white">Setujui pembayaran</button>
                                            </form>
                                        </details>
                                    @else
                                        <form method="POST" action="{{ route($routePrefix . 'pembayaran.verify', $trx) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="verified"><button type="submit">Setujui</button></form>
                                    @endif
                                    <form method="POST" action="{{ route($routePrefix . 'pembayaran.verify', $trx) }}" onsubmit="return confirm('Tolak pembayaran ini?')">@csrf @method('PATCH')<input type="hidden" name="status" value="rejected"><button type="submit" class="is-reject">Tolak</button></form>
                                </div>
                            @elseif($isPending)
                                <span class="payment-manual-label">Menunggu approval panitia/kepsek</span>
                            @else
                                <span class="payment-manual-label">Tidak ada aksi</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="payment-review-empty"><span>✓</span><h4>Belum ada transaksi</h4><p>Transaksi pembayaran akan muncul di sini.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($showPagination)<x-per-page-pagination :paginator="$transactions" />@endif
</section>
