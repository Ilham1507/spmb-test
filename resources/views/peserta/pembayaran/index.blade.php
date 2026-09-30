@extends('layouts.peserta')
@section('title', 'Pembayaran')
@section('page_title', 'Pembayaran')
@section('content')
@php
    $gatewayReady = $gatewayReady ?? false;
    $activeCheckouts = $activeCheckouts ?? collect();
    $rekeningAktif = $rekeningAktif ?? collect();
@endphp
<div class="mx-auto max-w-5xl space-y-4">
    @if(isset($errors) && $errors->any())
        <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">{{ $errors->first() }}</div>
    @endif
    @forelse($tagihans as $tagihan)
        @php
            $summary = \App\Support\PaymentSummary::forBill($tagihan);
            $quote = \App\Support\PaymentQuote::forBill($tagihan);
            $pending = $tagihan->transaksi->contains('status', 'pending');
            $paid = $tagihan->status === 'paid';
            $checkout = $activeCheckouts->get($tagihan->id);
            $initialSelection = old('selected_items', []);
            $isRegistrationFee = str_contains(strtolower((string) $tagihan->jenisTagihan?->name), 'formulir') || str_contains(strtolower((string) $tagihan->jenisTagihan?->name), 'pendaftaran');
            $isReRegistrationFee = str_contains(strtolower((string) $tagihan->jenisTagihan?->name), 'daftar ulang') || str_contains(strtolower((string) $tagihan->jenisTagihan?->name), 'du');
            $activePromotion = !$paid && !$pending && !$checkout
                ? (\App\Support\PromotionEvent::activeFor((string) $tagihan->jenisTagihan?->name, $pendaftar->id)
                    ?? collect($quote['items'])->map(fn ($item) => \App\Support\PromotionEvent::activeFor((string) $tagihan->jenisTagihan?->name, $pendaftar->id, (string) $item['name']))->filter()->first())
                : null;
            $itemPaymentAmounts = collect($quote['items'])->mapWithKeys(function ($item) use ($tagihan, $pendaftar) {
                $promo = \App\Support\PromotionEvent::apply(
                    (float) $item['amount'],
                    (string) $tagihan->jenisTagihan?->name,
                    $pendaftar->id,
                    (string) $item['name'],
                );

                return [(string) $item['name'] => (int) round($promo['amount'])];
            });
        @endphp
        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6"
            x-data="{ paying: false, opening: false, selectionOpen: false, historyOpen: false, cancelOpen: false, selected: @js($initialSelection), items: @js($quote['items']), paymentAmounts: @js($itemPaymentAmounts), get total() { return this.items.filter(item => this.selected.includes(item.name)).reduce((sum, item) => sum + Number(this.paymentAmounts[item.name] ?? item.amount), 0) } }">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 class="text-lg font-bold text-slate-900">{{ $isRegistrationFee ? 'Uang Formulir Pendaftaran' : ($tagihan->jenisTagihan?->name ?? 'Tagihan') }}</h3>
                    @if($activePromotion)
                        <p class="mt-1 text-sm font-bold text-emerald-700">{{ $activePromotion['name'] ?? 'Promo aktif' }} · potongan otomatis sudah diterapkan</p>
                    @endif
                    @if($pending)<p class="mt-1 text-sm font-semibold text-amber-800">{{ $isReRegistrationFee ? 'Pembayaran awal sedang diperiksa' : 'Sedang diperiksa panitia' }}</p>@endif
                </div>
                @if(!$paid && !$pending && $checkout)
                    <span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-bold text-sky-800">Menunggu pembayaran</span>
                @endif
            </div>
            @if($paid)
                <div class="mt-4 grid gap-3 sm:grid-cols-2"><div class="rounded-xl bg-blue-50 p-3"><p class="text-sm text-blue-800">Status</p><p class="mt-1 font-bold text-blue-900">Lunas</p></div><div class="rounded-xl bg-slate-50 p-3"><p class="text-sm text-slate-500">Dibayar</p><p class="mt-1 text-xl font-bold text-slate-900">Rp {{ number_format($tagihan->paid_amount, 0, ',', '.') }}</p></div></div>
            @elseif($pending)
                <p class="mt-4 text-sm text-slate-600">{{ $isReRegistrationFee ? 'Pembayaran daftar ulang sedang diperiksa. Setelah disetujui, pembayaran berikutnya dilakukan langsung di sekolah setiap hari Jumat.' : 'Pembayaran sudah tercatat. Kamu tidak perlu melakukan pembayaran lagi. Formulir akan terbuka setelah pemeriksaan panitia selesai.' }}</p>
            @elseif($checkout)
                <p class="mt-4 text-2xl font-bold text-slate-900">Rp {{ number_format($checkout->amount, 0, ',', '.') }}</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    @if($checkout->status === 'pending' && $checkout->redirect_url && $gatewayReady)
                        <form method="POST" action="{{ route('peserta.pembayaran.checkout', $tagihan) }}" @submit="opening = true">@csrf<input type="hidden" name="expected_amount" value="{{ $checkout->amount }}"><button class="btn-primary" :disabled="opening" x-text="opening ? 'Membuka…' : 'Lanjutkan bayar'">Lanjutkan bayar</button></form>
                    @endif
                    <form method="POST" action="{{ route('peserta.pembayaran.refresh', $checkout) }}">@csrf<button class="rounded-xl border border-sky-200 px-4 py-3 text-sm font-bold text-sky-800">Cek status</button></form>
                    @if($checkout->status === 'pending')
                        <button type="button" @click="cancelOpen = true" class="rounded-xl border border-rose-200 px-4 py-3 text-sm font-bold text-rose-700">Batalkan</button>
                    @endif
                </div>
            @elseif($quote['requires_selection'])
                @php($payableTotal = collect($quote['items'])->sum('amount'))
                @php($settledItems = $summary['items']->where('status', 'Lunas'))
                <div class="mt-4 grid gap-3 sm:grid-cols-3"><div class="rounded-xl bg-slate-50 p-3"><p class="text-sm text-slate-500">Total</p><p class="mt-1 text-xl font-bold text-slate-900">Rp {{ number_format($tagihan->total_amount, 0, ',', '.') }}</p></div><div class="rounded-xl bg-blue-50 p-3"><p class="text-sm text-blue-800">Terbayar</p><p class="mt-1 font-bold text-blue-900">{{ $settledItems->pluck('name')->join(', ') ?: '—' }}</p></div><div class="rounded-xl bg-sky-50 p-3"><p class="text-sm text-sky-800">Sisa</p><p class="mt-1 text-xl font-bold text-sky-900">Rp {{ number_format($payableTotal, 0, ',', '.') }}</p></div></div>
                <button type="button" @click="selectionOpen = true" class="btn-primary mt-4">Bayar</button>
                <div x-cloak x-show="selectionOpen" x-transition.opacity @keydown.escape.window="selectionOpen = false" class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/50 p-4" @click.self="selectionOpen = false">
                <section role="dialog" aria-modal="true" aria-label="Pilih biaya pembayaran" class="flex min-h-0 w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-2xl" style="height: calc(100% - 2rem); max-height: 42rem;">
                        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4"><h4 class="font-bold text-slate-900">Bayar daftar ulang</h4><button type="button" @click="selectionOpen = false" class="rounded-lg px-3 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100">Tutup</button></div>
                        <div class="overflow-y-auto p-5">
                            @php($settledItems = $summary['items']->where('status', 'Lunas'))
                            @if($settledItems->isNotEmpty())
                                <div class="mb-3 flex items-center justify-between rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                                    <span>✓ @foreach($settledItems as $item){{ $item['name'] }}{{ ! $loop->last ? ', ' : '' }}@endforeach</span><span class="font-bold">Rp {{ number_format($settledItems->sum('amount'), 0, ',', '.') }}</span>
                                </div>
                            @endif
                            <div class="divide-y divide-slate-100 rounded-xl border border-slate-200">
                            @foreach($quote['items'] as $item)
                                @php($itemPayable = $itemPaymentAmounts->get($item['name'], $item['amount']))
                                <label class="flex cursor-pointer items-center justify-between gap-3 p-4"><span class="flex items-center gap-3"><input type="checkbox" value="{{ $item['name'] }}" x-model="selected" class="h-5 w-5 rounded border-slate-300 text-sky-700 focus:ring-sky-600"><span class="text-sm font-semibold text-slate-800">{{ $item['name'] }}</span></span><span class="text-right text-sm font-bold text-slate-900">@if($itemPayable < $item['amount'])<span class="mr-1 text-xs font-semibold text-slate-400 line-through">Rp {{ number_format($item['amount'], 0, ',', '.') }}</span>@endif Rp {{ number_format($itemPayable, 0, ',', '.') }}</span></label>
                            @endforeach
                            </div>
                            <p class="mt-4 rounded-xl bg-sky-50 px-3 py-2 text-sm font-semibold text-sky-900">Unggah bukti di sini khusus untuk transfer. Pembayaran tunai dilakukan di sekolah dan dicatat oleh panitia.</p>
                            @if($isReRegistrationFee)<p class="mt-3 rounded-xl bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-900">Pembayaran DU melalui SPMB hanya satu kali. Setelah disetujui, pembayaran berikutnya dilakukan di sekolah setiap hari Jumat.</p>@endif
                        </div>
                        <div class="border-t border-slate-100 p-5"><div class="mb-3 flex items-center justify-between gap-3"><span class="text-sm font-semibold text-slate-600">Dipilih</span><strong class="text-xl text-slate-900" x-text="new Intl.NumberFormat('id-ID').format(total).replace(/^/, 'Rp ')">Rp 0</strong></div>
                            @if($activePromotion)<p class="mb-4 rounded-xl bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-800">Promo aktif sudah dihitung pada nominal tiap biaya yang dipilih.</p>@endif
                            @if($gatewayReady)
                                <form method="POST" action="{{ route('peserta.pembayaran.checkout', $tagihan) }}" @submit="opening = true">@csrf @foreach($quote['items'] as $item)<input type="checkbox" class="hidden" name="selected_items[]" value="{{ $item['name'] }}" x-model="selected">@endforeach <input type="hidden" name="expected_amount" x-bind:value="total"><button class="btn-primary w-full" :disabled="selected.length === 0 || total < 1 || opening" x-text="opening ? 'Membuka…' : 'Bayar Rp ' + new Intl.NumberFormat('id-ID').format(total)">Bayar</button></form>
                            @elseif($rekeningAktif->isNotEmpty())
                                <button type="button" @click="paying = !paying" class="btn-primary w-full" :disabled="selected.length === 0">Bayar pilihan</button>
                                <div x-show="paying" x-cloak class="mt-4 rounded-xl bg-sky-50 p-4">
                                    @php($rekening = $rekeningAktif->first())
                                    <p class="text-sm font-semibold text-sky-900">{{ $rekening->nama_bank }} · {{ $rekening->nomor_rekening }} a.n. {{ $rekening->atas_nama }}</p>
                                    <form method="POST" action="{{ route('peserta.pembayaran.store', $tagihan) }}" enctype="multipart/form-data" class="mt-3 space-y-3">@csrf @foreach($quote['items'] as $item)<input type="checkbox" class="hidden" name="selected_items[]" value="{{ $item['name'] }}" x-model="selected">@endforeach <input type="hidden" name="amount" x-bind:value="total"><input type="hidden" name="payment_method" value="transfer"><input type="file" name="proof_file" accept=".jpg,.jpeg,.png,.pdf" required class="w-full text-sm"><button class="btn-primary">Kirim bukti transfer</button></form>
                                </div>
                            @endif
                        </div>
                    </section>
                </div>
            @elseif($gatewayReady && $quote['amount'] > 0)
                @if($activePromotion && $quote['discount'] > 0)<p class="mt-4 text-sm font-semibold text-slate-500"><span class="line-through">Rp {{ number_format($tagihan->remaining_amount, 0, ',', '.') }}</span> <span class="ml-2 text-emerald-700">Hemat Rp {{ number_format($quote['discount'], 0, ',', '.') }}</span></p>@endif
                <p class="mt-4 text-2xl font-bold text-slate-900">Rp {{ number_format($quote['amount'], 0, ',', '.') }}</p>
                <p class="mt-1 text-sm font-medium text-slate-500">Pilih cara pembayaran yang paling nyaman.</p>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <form method="POST" action="{{ route('peserta.pembayaran.checkout', $tagihan) }}" @submit="opening = true">
                        @csrf<input type="hidden" name="expected_amount" value="{{ $quote['amount'] }}">
                        <button id="payment-digital-option" class="payment-method-choice payment-method-choice--digital w-full text-left" :disabled="opening">
                            <span class="payment-method-choice__icon">⇄</span><span><b x-text="opening ? 'Membuka…' : 'Transfer digital'"></b><small>Virtual account bank, QRIS, GoPay, ShopeePay, DANA, dan OVO yang aktif.</small></span><i>→</i>
                        </button>
                    </form>
                    <div id="payment-cash-option" class="payment-method-choice w-full text-left">
                        <span class="payment-method-choice__icon">⌂</span><span><b>Bayar tunai di sekolah</b><small>Datang ke sekolah. Petugas akan mencatat pembayaran dan notanya masuk ke riwayatmu.</small></span>
                    </div>
                </div>
            @elseif($rekeningAktif->isNotEmpty() && $quote['amount'] > 0)
                @if($activePromotion && $quote['discount'] > 0)<p class="mt-4 text-sm font-semibold text-slate-500"><span class="line-through">Rp {{ number_format($tagihan->remaining_amount, 0, ',', '.') }}</span> <span class="ml-2 text-emerald-700">Hemat Rp {{ number_format($quote['discount'], 0, ',', '.') }}</span></p>@endif
                <p class="mt-4 text-2xl font-bold text-slate-900">Rp {{ number_format($quote['amount'], 0, ',', '.') }}</p>
                @if($isRegistrationFee)
                    <div class="mt-4 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-950"><span class="font-bold">Pembayaran mandiri:</span> hanya melalui transfer. Pembayaran tunai dicatat panitia di sekolah.</div>
                @endif
                @if($isReRegistrationFee)
                    <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950"><span class="font-bold">Pembayaran DU satu kali di SPMB:</span> setelah pembayaran ini disetujui, pembayaran berikutnya dilakukan langsung di sekolah setiap hari Jumat dan dicatat panitia.</div>
                @endif
                <button type="button" @click="paying = !paying" class="btn-primary mt-4">Bayar sekarang</button>
                <div x-show="paying" x-cloak class="mt-4 rounded-xl bg-sky-50 p-4">
                    @php($rekening = $rekeningAktif->first())
                    <p class="text-sm font-semibold text-sky-900">{{ $rekening->nama_bank }} · {{ $rekening->nomor_rekening }} a.n. {{ $rekening->atas_nama }}</p>
                    <p class="mt-2 text-xs font-semibold text-sky-800">Transfer ke rekening ini, lalu unggah bukti pembayaran.</p>
                    <form method="POST" action="{{ route('peserta.pembayaran.store', $tagihan) }}" enctype="multipart/form-data" class="mt-3 space-y-3">@csrf<input type="hidden" name="amount" value="{{ $quote['amount'] }}"><input type="hidden" name="payment_method" value="transfer"><input type="file" name="proof_file" accept=".jpg,.jpeg,.png,.pdf" required class="w-full text-sm"><button class="btn-primary">Kirim bukti transfer</button></form>
                </div>
            @else
                <p class="mt-4 text-sm text-slate-600">Hubungi bendahara untuk pembayaran ini.</p>
            @endif
            <button type="button" @click="historyOpen = true" class="ml-5 mt-5 border-t border-slate-100 pt-3 text-sm font-semibold text-sky-800">{{ $quote['requires_selection'] ? 'Rincian' : 'Rincian & riwayat' }}</button>

            <div x-cloak x-show="historyOpen" x-transition.opacity @keydown.escape.window="historyOpen = false" class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/50 p-4" @click.self="historyOpen = false">
                <section role="dialog" aria-modal="true" aria-label="Rincian dan riwayat pembayaran" class="flex min-h-0 w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-2xl" style="height: calc(100% - 2rem); max-height: 42rem;">
                    <div class="flex shrink-0 items-center justify-between border-b border-slate-100 px-5 py-4"><h4 class="font-bold text-slate-900">{{ $tagihan->jenisTagihan?->name ?? 'Tagihan sekolah' }}</h4><button type="button" @click="historyOpen = false" class="rounded-lg px-3 py-1 text-sm font-bold text-slate-600 hover:bg-slate-100">Tutup</button></div>
                    <div class="overflow-y-auto p-5">
                        <div class="grid grid-cols-3 gap-2 text-sm">
                            <div class="rounded-xl bg-slate-50 p-3"><p class="text-slate-500">Total</p><p class="mt-1 font-bold text-slate-900">Rp {{ number_format($tagihan->total_amount, 0, ',', '.') }}</p></div>
                            <div class="rounded-xl bg-blue-50 p-3"><p class="text-blue-800">Dibayar</p><p class="mt-1 font-bold text-blue-900">Rp {{ number_format($tagihan->paid_amount, 0, ',', '.') }}</p></div>
                            <div class="rounded-xl bg-amber-50 p-3"><p class="text-amber-800">Sisa</p><p class="mt-1 font-bold text-amber-900">Rp {{ number_format($tagihan->remaining_amount, 0, ',', '.') }}</p></div>
                        </div>

                        @if($summary['items']->isNotEmpty())
                            <div class="mt-5 border-t border-slate-100 pt-4">
                                <div class="mb-3 flex items-center justify-between"><h5 class="font-bold text-slate-900">Rincian biaya</h5><span class="text-xs text-slate-500">Yang dibayar pada tagihan ini</span></div>
                                <div class="divide-y divide-slate-100 rounded-xl border border-slate-200 px-4">
                                    @foreach($summary['items'] as $item)
                                        <div class="flex items-center justify-between gap-3 py-3"><div class="min-w-0"><p class="truncate text-sm font-semibold text-slate-800">{{ $item['name'] }}</p><p class="mt-0.5 text-xs text-slate-500">Rp {{ number_format($item['amount'], 0, ',', '.') }}</p></div><span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-bold {{ $item['status'] === 'Lunas' ? 'bg-emerald-50 text-emerald-700' : ($item['status'] === 'Belum dibayar' ? 'bg-slate-100 text-slate-700' : 'bg-amber-50 text-amber-700') }}">{{ $item['status'] }}</span></div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="mt-5 border-t border-slate-100 pt-4">
                            <div class="mb-3 flex items-center justify-between"><h5 class="font-bold text-slate-900">Riwayat pembayaran</h5><span class="text-xs text-slate-500">{{ $tagihan->transaksi->count() }} transaksi</span></div>
                            <div class="space-y-2">
                                @forelse($tagihan->transaksi->sortByDesc(fn ($trx) => $trx->payment_date ?? $trx->created_at) as $trx)
                                    @php($status = ['verified' => 'Diterima', 'pending' => 'Menunggu dicek', 'rejected' => 'Ditolak'][$trx->status] ?? ucfirst($trx->status))
                                    <article class="rounded-xl border border-slate-200 px-4 py-3">
                                        <div class="flex items-start justify-between gap-3"><div><p class="font-bold text-slate-900">Rp {{ number_format($trx->amount, 0, ',', '.') }}</p><p class="mt-1 text-xs text-slate-500">{{ \Illuminate\Support\Carbon::parse($trx->payment_date ?? $trx->created_at)->format('d/m/Y H:i') }} · {{ ['cash' => 'Tunai', 'digital' => 'QRIS / e-wallet', 'transfer' => 'Transfer / VA'][$trx->payment_method] ?? 'Pembayaran digital' }}</p></div><span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-bold {{ $trx->status === 'verified' ? 'bg-emerald-50 text-emerald-700' : ($trx->status === 'rejected' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700') }}">{{ $status }}</span></div>
                                        @if(!empty($trx->selected_items))<p class="mt-2 border-t border-slate-100 pt-2 text-xs text-slate-600">{{ collect($trx->selected_items)->pluck('name')->filter()->implode(', ') }}</p>@endif
                                        <a href="{{ route('peserta.pembayaran.receipt', $trx) }}" target="_blank" rel="noopener" class="mt-2 inline-flex text-xs font-black text-teal-700 underline underline-offset-4">Lihat nota pembayaran</a>
                                    </article>
                                @empty
                                    <p class="rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-500">Belum ada pembayaran yang diterima.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            @if($checkout && $checkout->status === 'pending')
                <div x-cloak x-show="cancelOpen" x-transition.opacity @keydown.escape.window="cancelOpen = false" class="fixed inset-0 z-[110] flex items-center justify-center bg-slate-950/50 p-4" @click.self="cancelOpen = false">
                    <section role="dialog" aria-modal="true" aria-label="Batalkan pembayaran" class="w-full max-w-sm rounded-2xl bg-white p-5 shadow-2xl">
                        <h4 class="text-lg font-bold text-slate-900">Batalkan pembayaran?</h4>
                        <p class="mt-2 text-sm text-slate-600">VA ini tidak bisa digunakan lagi setelah dibatalkan. Kamu dapat membuat pembayaran baru nanti.</p>
                        <div class="mt-5 flex justify-end gap-2"><button type="button" @click="cancelOpen = false" class="rounded-xl px-4 py-3 text-sm font-bold text-slate-600 hover:bg-slate-100">Kembali</button><form method="POST" action="{{ route('peserta.pembayaran.cancel', $checkout) }}">@csrf<button class="rounded-xl bg-rose-600 px-4 py-3 text-sm font-bold text-white hover:bg-rose-700">Ya, batalkan</button></form></div>
                    </section>
                </div>
            @endif
        </section>
    @empty
        <p class="rounded-2xl bg-white p-6 text-sm text-slate-600">Belum ada tagihan.</p>
    @endforelse
</div>

@if(request()->boolean('tour'))
    <div id="payment-stage-tour" hidden class="participant-payment-tour fixed inset-0 z-[125]" aria-live="polite">
        <div class="fixed inset-0 bg-slate-950/65 backdrop-blur-[1px]"></div>
        <aside class="participant-page-tour-tip fixed z-[132] w-[calc(100%-2rem)] max-w-sm rounded-2xl bg-white p-4 shadow-2xl">
            <p id="payment-tour-title" class="text-sm font-black text-slate-950"></p>
            <p id="payment-tour-text" class="mt-1 text-xs font-semibold leading-relaxed text-slate-600"></p>
            <div class="mt-4 flex items-center justify-between gap-3"><button type="button" id="payment-tour-skip" class="text-xs font-black text-slate-500 underline underline-offset-4">Lewati</button><button type="button" id="payment-tour-next" class="rounded-xl bg-teal-700 px-4 py-2.5 text-xs font-black text-white">Lanjut</button></div>
        </aside>
    </div>
@endif
@endsection

@if(request()->boolean('tour'))
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tour = document.getElementById('payment-stage-tour');
        if (!tour) return;
        const steps = [
            { target: '#payment-digital-option', title: 'Transfer digital', text: 'Pilih ini untuk virtual account bank, QRIS, GoPay, ShopeePay, DANA, atau OVO.' },
            { target: '#payment-cash-option', title: 'Bayar tunai di sekolah', text: 'Pilih ini bila pembayaran dilakukan langsung di sekolah. Siapkan bukti penerimaan untuk dikirim.' },
        ].filter(step => document.querySelector(step.target));
        if (!steps.length) return;
        let index = 0, active;
        const title = document.getElementById('payment-tour-title');
        const text = document.getElementById('payment-tour-text');
        const next = document.getElementById('payment-tour-next');
        const show = function () {
            if (active) active.classList.remove('participant-page-tour-target');
            const step = steps[index]; active = document.querySelector(step.target);
            active.classList.add('participant-page-tour-target');
            const activeRect = active.getBoundingClientRect();
            if (activeRect.top < 16 || activeRect.bottom > window.innerHeight - 16) {
                active.scrollIntoView({ behavior: 'auto', block: 'center', inline: 'nearest' });
            }
            title.textContent = step.title; text.textContent = step.text;
            next.textContent = index === steps.length - 1 ? 'Selesai' : 'Berikutnya';
            requestAnimationFrame(() => {
                const tip = tour.querySelector('.participant-page-tour-tip');
                tour.hidden = false;
                window.positionParticipantTourTip?.(tip, active);
                requestAnimationFrame(() => window.positionParticipantTourTip?.(tip, active));
            });
        };
        window.addEventListener('resize', () => window.positionParticipantTourTip?.(tour.querySelector('.participant-page-tour-tip'), active));
        const close = function () { if (active) active.classList.remove('participant-page-tour-target'); tour.remove(); };
        next.addEventListener('click', function () { index === steps.length - 1 ? close() : (index++, show()); });
        document.getElementById('payment-tour-skip').addEventListener('click', close);
        show();
    });
</script>
@endpush
@endif
