@extends('layouts.peserta')

@section('title', 'Rincian Biaya Jurusan')
@section('page_title', 'Rincian Biaya Jurusan')

@section('content')
<div class="mx-auto max-w-6xl space-y-5">
    <section class="rounded-3xl border border-teal-100 bg-white p-5 shadow-sm md:p-7">
        <p class="text-xs font-black uppercase tracking-[0.14em] text-teal-700">Informasi biaya</p>
        <h2 class="mt-1 text-2xl font-black text-slate-950">Bandingkan biaya setiap jurusan</h2>
        <p class="mt-2 max-w-2xl text-sm font-medium leading-relaxed text-slate-600">Kamu dapat melihat rincian seluruh jurusan sebelum menentukan pilihan. Orang tua juga dapat menyiapkan pembayaran daftar ulang lebih awal tanpa menunggu formulir selesai diisi.</p>
    </section>

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
        @forelse($feeOptions as $option)
            @php
                $isSelected = (int) $pendaftar->major_choice_1 === (int) $option->jurusan->id;
                $groupedItems = $option->items->groupBy(fn ($item) => trim((string) ($item['category'] ?? '')) ?: 'Lainnya');
            @endphp
            <section class="overflow-hidden rounded-3xl border {{ $isSelected ? 'border-teal-300 bg-teal-50/40' : 'border-slate-200 bg-white' }} shadow-sm">
                <div class="border-b border-slate-100 p-5">
                    <div class="flex items-start justify-between gap-3"><div><p class="text-xs font-black uppercase tracking-wide text-teal-700">Jurusan</p><h3 class="mt-1 text-xl font-black text-slate-950">{{ $option->jurusan->name }}</h3></div>@if($isSelected)<span class="rounded-full bg-teal-100 px-3 py-1 text-xs font-black text-teal-800">Pilihanmu</span>@endif</div>
                    <p class="mt-3 text-2xl font-black text-teal-800">Rp {{ number_format($option->amount, 0, ',', '.') }}</p>
                </div>
                <div class="p-5">
                    <p class="text-sm font-black text-slate-900">Rincian biaya</p>
                    @if($option->items->isNotEmpty())
                        <div class="mt-3 space-y-3">
                            @foreach($groupedItems as $category => $items)
                                <section class="overflow-hidden rounded-2xl border border-slate-200">
                                    <div class="flex items-center justify-between gap-3 bg-teal-50 px-4 py-3"><p class="text-sm font-black text-teal-900">{{ $category }}</p><b class="shrink-0 text-base text-teal-900">Rp {{ number_format($items->sum(fn ($item) => (float) ($item['amount'] ?? 0)), 0, ',', '.') }}</b></div>
                                </section>
                            @endforeach
                        </div>
                    @else
                        <p class="mt-3 rounded-2xl bg-slate-50 p-3 text-sm text-slate-500">Rincian komponen belum diatur. Nominal total tetap dapat digunakan sebagai informasi biaya.</p>
                    @endif
                    <form method="POST" action="{{ route('peserta.biaya-jurusan.daftar-ulang', $option->jurusan) }}" class="mt-5">@csrf<button class="w-full rounded-2xl bg-teal-700 px-4 py-3 text-sm font-black text-white shadow-lg shadow-teal-700/15 hover:bg-teal-800">{{ $isSelected ? 'Lihat pembayaran daftar ulang' : 'Pilih untuk pembayaran daftar ulang' }}</button></form>
                </div>
            </section>
        @empty
            <p class="rounded-3xl bg-white p-6 text-sm text-slate-600">Belum ada biaya jurusan aktif yang dapat ditampilkan.</p>
        @endforelse
    </div>
</div>
@endsection
