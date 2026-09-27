@props(['transactions', 'routePrefix', 'accent' => 'amber', 'showPagination' => false])

@php
    $items = $transactions instanceof \Illuminate\Pagination\AbstractPaginator ? $transactions->getCollection() : $transactions;
    $waitingCount = $items->where('status', 'pending')->count();
    $total = $transactions instanceof \Illuminate\Pagination\AbstractPaginator ? $transactions->total() : $items->count();
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
            <thead><tr><th>Peserta</th><th>Pembayaran</th><th>Bukti</th><th class="text-right">Status / aksi</th></tr></thead>
            <tbody>
                @forelse($items as $trx)
                    @php
                        $applicant = $trx->tagihan?->pendaftar;
                        $name = $applicant?->biodata?->full_name ?? $applicant?->user?->name ?? 'Peserta';
                        $isPending = $trx->status === 'pending';
                        $checkout = $trx->checkout;
                        $isOnline = $checkout && ($checkout->provider_transaction_id || $checkout->order_id);
                        $status = match ($trx->status) {
                            'verified' => ['Disetujui', 'is-approved'],
                            'rejected' => ['Ditolak', 'is-rejected'],
                            default => ['Menunggu', 'is-pending'],
                        };
                        $reference = $checkout?->provider_transaction_id ?: $checkout?->order_id ?: $trx->transaction_number;
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
                        <td class="payment-status-cell">
                            @if($isPending)
                                <div class="payment-decision-actions">
                                    <form method="POST" action="{{ route($routePrefix . 'pembayaran.verify', $trx) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="verified"><button type="submit">Setujui</button></form>
                                    <form method="POST" action="{{ route($routePrefix . 'pembayaran.verify', $trx) }}" onsubmit="return confirm('Tolak pembayaran ini?')">@csrf @method('PATCH')<input type="hidden" name="status" value="rejected"><button type="submit" class="is-reject">Tolak</button></form>
                                </div>
                            @else
                                <div class="payment-final-status"><b class="{{ $status[1] }}">{{ $status[0] }}</b><small>{{ $trx->verifier?->name ? 'oleh '.$trx->verifier->name : ($trx->verified_at ? \Illuminate\Support\Carbon::parse($trx->verified_at)->format('d M Y, H:i') : 'Status final') }}</small></div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4"><div class="payment-review-empty"><span>✓</span><h4>Belum ada transaksi</h4><p>Transaksi pembayaran akan muncul di sini.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($showPagination)<x-per-page-pagination :paginator="$transactions" />@endif
</section>
