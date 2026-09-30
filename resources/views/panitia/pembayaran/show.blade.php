@extends(request()->routeIs('admin.*') ? 'layouts.admin' : 'layouts.panitia')

@section('title', 'Detail Tagihan & Pembayaran')
@section('page_title', 'Verifikasi Pembayaran')

@section('content')
<div class="max-w-4xl space-y-6" x-data="{ rejectOpen: false, rejectAction: '', rejectStudent: '', rejectAmount: '' }">

    <a href="{{ route((request()->routeIs('admin.*') ? 'admin.' : 'panitia.') . 'pembayaran.index') }}" class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-slate-700 font-medium">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7"/></svg>
        Kembali ke Data Keuangan
    </a>

    {{-- Header --}}
    <div class="bg-gradient-to-br from-indigo-600 to-sky-700 rounded-2xl p-6 text-white shadow-xl flex justify-between items-center">
        <div>
            <h2 class="text-xl font-bold">{{ $tagihan->jenisTagihan?->name ?? 'Tagihan' }}</h2>
            <p class="text-indigo-100 text-sm mt-1">Pendaftar: <strong>{{ $tagihan->pendaftar->biodata?->full_name ?? '-' }}</strong> ({{ $tagihan->pendaftar->registration_number }})</p>
        </div>
        @php
            $statusColors = [
                'paid' => 'bg-emerald-400 text-emerald-900',
                'partial' => 'bg-amber-400 text-amber-900',
                'unpaid' => 'bg-rose-400 text-white',
            ];
        @endphp
        <span class="px-3 py-1.5 rounded-full text-xs font-bold {{ $statusColors[$tagihan->status] ?? 'bg-white/20 text-white' }}">
            {{ strtoupper($tagihan->status) }}
        </span>
    </div>

    {{-- Ringkasan Nominal --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-white border border-slate-200 rounded-2xl p-5 text-center shadow-sm">
            <p class="text-xs font-semibold text-slate-500 uppercase">Total Tagihan</p>
            <p class="text-xl font-bold text-slate-900 mt-2">Rp {{ number_format($tagihan->total_amount, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl p-5 text-center shadow-sm">
            <p class="text-xs font-semibold text-emerald-500 uppercase">Sudah Dibayar</p>
            <p class="text-xl font-bold text-emerald-600 mt-2">Rp {{ number_format($tagihan->paid_amount, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl p-5 text-center shadow-sm">
            <p class="text-xs font-semibold text-rose-500 uppercase">Sisa Tagihan</p>
            <p class="text-xl font-bold text-rose-600 mt-2">Rp {{ number_format($tagihan->remaining_amount, 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- Riwayat Transaksi --}}
    <h3 class="font-bold text-slate-800 mt-8 mb-4">Riwayat Transaksi Pembayaran</h3>
    
    <div class="space-y-4">
        @forelse($tagihan->transaksi as $trx)
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden p-6">
            <div class="flex items-start justify-between border-b border-slate-100 pb-4 mb-4">
                <div>
                    <h4 class="font-bold text-slate-800 flex items-center gap-2">
                        {{ $trx->transaction_number ?? 'No. Transaksi Baru' }}
                        @php
                            $trxStatus = [
                                'pending' => 'bg-amber-100 text-amber-700',
                                'verified' => 'bg-emerald-100 text-emerald-700',
                                'rejected' => 'bg-rose-100 text-rose-700',
                            ];
                        @endphp
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $trxStatus[$trx->status] ?? '' }}">{{ strtoupper($trx->status) }}</span>
                    </h4>
                    <p class="text-xs text-slate-400 mt-1">Metode: {{ ucfirst($trx->payment_method) }} | Ref: {{ $trx->reference_number ?? '-' }}</p>
                </div>
                <div class="text-right">
                    <p class="text-lg font-bold text-slate-900">Rp {{ number_format($trx->amount, 0, ',', '.') }}</p>
                    <p class="text-xs text-slate-400 mt-1">{{ date('d M Y H:i', strtotime($trx->payment_date ?? $trx->created_at)) }}</p>
                </div>
            </div>

            @if($trx->status === 'pending')
                <div class="flex flex-wrap items-center gap-6">
                    @if($trx->proof_file)
                    <div class="flex-1 min-w-[200px]">
                        <p class="text-xs font-semibold text-slate-500 mb-2">Bukti Pembayaran:</p>
                        <a href="{{ route((request()->routeIs('admin.*') ? 'admin.' : 'panitia.') . 'pembayaran.proof', $trx) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-sm text-sky-600 font-medium hover:underline bg-sky-50 px-3 py-2 rounded-lg border border-sky-100">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Lihat Bukti Struk/Transfer
                        </a>
                    </div>
                    @endif
                    <div class="w-full md:w-auto">
                        <form method="POST" action="{{ route((request()->routeIs('admin.*') ? 'admin.' : 'panitia.') . 'pembayaran.verify', $trx->id) }}" class="flex items-center gap-3">
                            @csrf
                            @method('PATCH')
                            <input type="text" name="notes" placeholder="Catatan jika ditolak..." class="px-3 py-2 text-sm rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
                            <button type="submit" name="status" value="verified" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-lg shadow-sm">Verifikasi (Sah)</button>
                            <button type="button" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-lg shadow-sm" @click="rejectAction = @js(route((request()->routeIs('admin.*') ? 'admin.' : 'panitia.') . 'pembayaran.verify', $trx->id)); rejectStudent = @js($tagihan->pendaftar->biodata?->full_name ?? 'Peserta'); rejectAmount = @js('Rp '.number_format($trx->amount, 0, ',', '.')); rejectOpen = true">Tolak</button>
                        </form>
                    </div>
                </div>
            @else
                <div class="bg-slate-50 rounded-xl p-4 flex gap-3 text-sm">
                    <svg class="w-5 h-5 text-slate-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div>
                        <p class="font-medium text-slate-700">Telah diverifikasi oleh Panitia pada {{ $trx->verified_at ? date('d M Y H:i', strtotime($trx->verified_at)) : '-' }}</p>
                        @if($trx->notes)
                            <p class="text-slate-500 mt-1 italic">Catatan: {{ $trx->notes }}</p>
                        @endif
                    </div>
                </div>
            @endif
        </div>
        @empty
        <div class="bg-white border border-slate-200 rounded-2xl p-8 text-center shadow-sm">
            <p class="text-slate-500 font-medium">Belum ada transaksi untuk tagihan ini.</p>
        </div>
        @endforelse
    </div>

    <template x-teleport="body">
        <div x-cloak x-show="rejectOpen" x-transition.opacity class="fixed inset-0 z-[2147483647] flex items-center justify-center bg-slate-950/60 p-4" @keydown.escape.window="rejectOpen = false">
            <section class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="detail-reject-payment-title" @click.outside="rejectOpen = false">
                <div class="flex items-start gap-4">
                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-rose-100 text-2xl font-black text-rose-700">!</span>
                    <div>
                        <p class="text-xs font-black uppercase tracking-[.16em] text-rose-700">Konfirmasi penolakan</p>
                        <h2 id="detail-reject-payment-title" class="mt-1 text-xl font-black text-slate-900">Tolak pembayaran?</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Bukti pembayaran <strong x-text="rejectStudent"></strong> sebesar <strong x-text="rejectAmount"></strong> akan ditolak. Siswa menerima WA untuk mengirim ulang bukti.</p>
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
</div>
@endsection
