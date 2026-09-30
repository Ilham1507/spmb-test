@props(['transactions', 'routePrefix', 'accent' => 'amber', 'showPagination' => false])

@php
    $items = $transactions instanceof \Illuminate\Pagination\AbstractPaginator ? $transactions->getCollection() : $transactions;
    $waitingCount = $items->where('status', 'pending')->count();
    $total = $transactions instanceof \Illuminate\Pagination\AbstractPaginator ? $transactions->total() : $items->count();
    $canApprove = auth()->user()?->hasRole('panitia') || auth()->user()?->hasRole('bendahara') || auth()->user()?->hasRole('kepala_sekolah') || auth()->user()?->hasRole('admin');
@endphp

<section class="payment-review-section payment-review-{{ $accent }}" x-data="{ rejectOpen: false, rejectAction: '', rejectStudent: '', rejectAmount: '', approveOpen: false, approveAction: '', approveStudent: '', approveAmount: 0, approvalItems: [], approvedItems: [], get approvedTotal() { return this.approvalItems.filter(item => this.approvedItems.includes(item.name)).reduce((total, item) => total + Number(item.amount), 0) } }">
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
                                        <button type="button" class="rounded-lg bg-emerald-700 px-3 py-2 text-xs font-black text-white hover:bg-emerald-800" @click="approveAction = @js(route($routePrefix . 'pembayaran.verify', $trx)); approveStudent = @js($name); approveAmount = @js((int) $trx->amount); approvalItems = @js($feeItems->map(fn ($item) => ['name' => (string) $item['name'], 'amount' => (int) $item['amount']])->values()); approvedItems = []; approveOpen = true">Pilih rincian & setujui</button>
                                    @else
                                        <form method="POST" action="{{ route($routePrefix . 'pembayaran.verify', $trx) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="verified"><button type="submit">Setujui</button></form>
                                    @endif
                                    <button type="button" class="is-reject" @click="rejectAction = @js(route($routePrefix . 'pembayaran.verify', $trx)); rejectStudent = @js($name); rejectAmount = @js('Rp '.number_format($trx->amount, 0, ',', '.')); rejectOpen = true">Tolak</button>
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

    <template x-teleport="body">
        <div x-cloak x-show="approveOpen" x-transition.opacity class="fixed inset-0 z-[2147483647] flex items-center justify-center bg-slate-950/60 p-4" @keydown.escape.window="approveOpen = false">
            <section class="flex max-h-[calc(100vh-2rem)] w-full max-w-xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="approve-payment-title" @click.outside="approveOpen = false">
                <header class="flex items-start justify-between border-b border-slate-100 px-6 py-5">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[.16em] text-emerald-700">Persetujuan pembayaran</p>
                        <h2 id="approve-payment-title" class="mt-1 text-xl font-black text-slate-900">Pilih rincian yang dibayar</h2>
                        <p class="mt-1 text-sm text-slate-500"><strong x-text="approveStudent"></strong> · Target <strong x-text="'Rp ' + Number(approveAmount).toLocaleString('id-ID')"></strong></p>
                    </div>
                    <button type="button" @click="approveOpen = false" class="rounded-xl px-3 py-2 text-sm font-black text-slate-500 hover:bg-slate-100">Tutup</button>
                </header>
                <form method="POST" :action="approveAction" class="flex min-h-0 flex-1 flex-col">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="verified">
                    <div class="min-h-0 flex-1 overflow-y-auto px-6 py-4">
                        <p class="mb-3 text-sm font-semibold text-slate-600">Centang rincian yang sesuai dengan nominal bukti transfer.</p>
                        <div class="space-y-2">
                            <template x-for="item in approvalItems" :key="item.name">
                                <label class="flex cursor-pointer items-center justify-between gap-3 rounded-2xl border px-4 py-3 transition" :class="approvedItems.includes(item.name) ? 'border-emerald-300 bg-emerald-50' : 'border-slate-200 bg-white hover:border-emerald-200'">
                                    <span class="flex min-w-0 items-center gap-3"><input type="checkbox" name="selected_items[]" :value="item.name" x-model="approvedItems" class="h-5 w-5 rounded border-slate-300 text-emerald-700 focus:ring-emerald-600"><span class="truncate text-sm font-bold text-slate-800" x-text="item.name"></span></span>
                                    <strong class="shrink-0 text-sm text-slate-900" x-text="'Rp ' + Number(item.amount).toLocaleString('id-ID')"></strong>
                                </label>
                            </template>
                        </div>
                    </div>
                    <footer class="border-t border-slate-100 bg-white px-6 py-4">
                        <div class="mb-3 flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3 text-sm"><span class="font-semibold text-slate-600">Total rincian dipilih</span><strong :class="approvedTotal === Number(approveAmount) ? 'text-emerald-700' : 'text-rose-700'" x-text="'Rp ' + approvedTotal.toLocaleString('id-ID')"></strong></div>
                        <div class="grid grid-cols-2 gap-3">
                            <button type="button" @click="approveOpen = false" class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-black text-slate-700 hover:bg-slate-50">Batal</button>
                            <button type="submit" :disabled="approvedTotal !== Number(approveAmount)" class="rounded-xl bg-emerald-700 px-4 py-3 text-sm font-black text-white shadow-lg shadow-emerald-200 hover:bg-emerald-800 disabled:cursor-not-allowed disabled:opacity-45">Setujui pembayaran</button>
                        </div>
                    </footer>
                </form>
            </section>
        </div>
    </template>

    <template x-teleport="body">
        <div x-cloak x-show="rejectOpen" x-transition.opacity class="fixed inset-0 z-[2147483647] flex items-center justify-center bg-slate-950/60 p-4" @keydown.escape.window="rejectOpen = false">
            <section class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="reject-payment-title" @click.outside="rejectOpen = false">
                <div class="flex items-start gap-4">
                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-rose-100 text-2xl font-black text-rose-700">!</span>
                    <div>
                        <p class="text-xs font-black uppercase tracking-[.16em] text-rose-700">Konfirmasi penolakan</p>
                        <h2 id="reject-payment-title" class="mt-1 text-xl font-black text-slate-900">Tolak pembayaran?</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Bukti pembayaran <strong x-text="rejectStudent"></strong> sebesar <strong x-text="rejectAmount"></strong> akan ditolak. Siswa menerima notifikasi WhatsApp untuk mengirim ulang bukti.</p>
                    </div>
                </div>
                <form method="POST" :action="rejectAction" class="mt-5">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="rejected">
                    <label class="text-sm font-black text-slate-700">Catatan penolakan <span class="font-semibold text-slate-400">(opsional)</span></label>
                    <textarea name="notes" rows="3" maxlength="255" class="mt-2 w-full resize-none rounded-2xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 outline-none focus:border-rose-400 focus:ring-4 focus:ring-rose-100" placeholder="Contoh: nominal atau foto bukti belum terbaca."></textarea>
                    <div class="mt-5 grid grid-cols-2 gap-3">
                        <button type="button" @click="rejectOpen = false" class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-black text-slate-700 hover:bg-slate-50">Batal</button>
                        <button type="submit" class="rounded-xl bg-rose-600 px-4 py-3 text-sm font-black text-white shadow-lg shadow-rose-200 hover:bg-rose-700">Ya, tolak pembayaran</button>
                    </div>
                </form>
            </section>
        </div>
    </template>
</section>
