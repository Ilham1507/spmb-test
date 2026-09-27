@props(['bill'])
@php($summary = \App\Support\PaymentSummary::forBill($bill))
<div class="my-3 space-y-3 text-sm">
    <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
        <div class="rounded-xl bg-slate-50 p-3"><p class="text-xs text-slate-600">Total tagihan</p><p class="font-bold text-slate-900">Rp {{ number_format($bill->total_amount, 0, ',', '.') }}</p></div>
        <div class="rounded-xl bg-emerald-50 p-3"><p class="text-xs text-emerald-800">Sudah dibayar</p><p class="font-bold text-emerald-800">Rp {{ number_format($bill->paid_amount, 0, ',', '.') }}</p></div>
        <div class="rounded-xl bg-amber-50 p-3"><p class="text-xs text-amber-800">Sisa tagihan</p><p class="font-bold text-amber-800">Rp {{ number_format($bill->remaining_amount, 0, ',', '.') }}</p></div>
    </div>
    @if($summary['pending'] > 0)
        <p class="rounded-xl bg-amber-50 p-3 text-amber-900">Rp {{ number_format($summary['pending'], 0, ',', '.') }} menunggu dicek. Nominal ini belum mengurangi sisa tagihan.</p>
    @endif
    <div class="rounded-xl border border-slate-200 p-3">
        <p class="font-bold text-slate-900">Pembayaran terakhir yang diterima</p>
        @if($last = $summary['last'])
            <p class="mt-1 text-slate-700">{{ \Illuminate\Support\Carbon::parse($last->payment_date ?? $last->created_at)->format('d/m/Y H:i') }} · Rp {{ number_format($last->amount, 0, ',', '.') }} · {{ $last->payment_method === 'cash' ? 'Tunai' : 'Transfer' }}</p>
            <p class="mt-1 text-slate-600">{{ collect($last->selected_items ?? [])->pluck('name')->join(', ') ?: ($bill->jenisTagihan?->name ?? 'Tagihan') }}</p>
        @else
            <p class="mt-1 text-slate-500">Belum ada pembayaran yang diterima.</p>
        @endif
    </div>
    @if($summary['items']->isNotEmpty())
        <div class="rounded-xl border border-slate-200 p-3">
            <p class="mb-2 font-bold text-slate-900">Rincian biaya & status</p>
            <div class="divide-y divide-slate-100">
                @foreach($summary['items'] as $item)
                    <div class="flex flex-wrap items-center justify-between gap-2 py-2">
                        <div><p class="font-semibold text-slate-800">{{ $item['name'] }}</p><p class="text-xs text-slate-500">Rp {{ number_format($item['amount'], 0, ',', '.') }}</p></div>
                        <span class="rounded-full px-3 py-1 text-xs font-bold {{ $item['status'] === 'Lunas' ? 'bg-emerald-100 text-emerald-800' : ($item['status'] === 'Belum dibayar' ? 'bg-slate-100 text-slate-700' : 'bg-amber-100 text-amber-900') }}">{{ $item['status'] }}</span>
                    </div>
                @endforeach
            </div>
            @if($summary['unallocated'])
                <p class="mt-2 text-xs text-slate-600">Ada pembayaran lama tanpa rincian biaya. Nominal sudah dihitung, tetapi biaya yang dibayar perlu dicocokkan oleh petugas.</p>
            @endif
        </div>
    @endif
</div>
