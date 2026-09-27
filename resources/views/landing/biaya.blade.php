@extends('layouts.school')

@section('title', 'Informasi Biaya')

@section('content')
<div class="bg-[#effcf8]">
    <section class="relative overflow-hidden bg-[#073f39] px-5 py-12 text-white md:py-14">
        <div class="absolute -left-24 -top-24 h-72 w-72 rounded-full bg-yellow-300/10"></div>
        <div class="relative mx-auto max-w-6xl">
            <span class="inline-flex rounded-full bg-cyan-100 px-4 py-2 text-xs font-black uppercase tracking-widest text-emerald-950">Informasi Biaya</span>
            <h1 class="mt-4 max-w-3xl text-3xl font-black leading-tight md:text-4xl">Biaya SPMB yang transparan</h1>
            <p class="mt-4 max-w-2xl text-base leading-relaxed text-emerald-50/80 md:text-lg">Lihat biaya pendaftaran dan perkiraan biaya awal sesuai konsentrasi pilihanmu.</p>
        </div>
    </section>

    <section class="mx-auto grid max-w-6xl gap-5 px-5 py-9 lg:grid-cols-[.9fr_1.1fr]">
        <article class="rounded-[2rem] border border-emerald-100 bg-white p-6 shadow-sm">
            <span class="text-xs font-black uppercase tracking-widest text-emerald-700">Tagihan Awal</span>
            <h2 class="mt-2 text-xl font-black text-slate-950">Biaya formulir dan administrasi</h2>
            <div class="mt-4 divide-y divide-slate-100">
                @forelse($tagihans as $tagihan)
                    @if(str_contains(strtolower($tagihan->name), 'daftar ulang'))
                        @continue
                    @endif
                    <div class="flex flex-col gap-2 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="font-black text-slate-950">{{ $tagihan->name }}</h3>
                            @if($tagihan->description)
                                <p class="mt-1 text-sm text-slate-500">{{ $tagihan->description }}</p>
                            @endif
                        </div>
                            <strong class="shrink-0 text-lg font-black text-emerald-700">Rp {{ number_format($tagihan->default_amount, 0, ',', '.') }}</strong>
                    </div>
                @empty
                    <div class="rounded-2xl bg-amber-50 p-4 text-sm font-semibold text-amber-800">Informasi biaya pendaftaran belum tersedia.</div>
                @endforelse
            </div>
            <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-5">
                <h3 class="font-black text-amber-900">Catatan pembayaran</h3>
                <p class="mt-1 text-sm leading-relaxed text-amber-800">Pembayaran formulir wajib lunas. Pembayaran daftar ulang mengikuti status kelulusan dan arahan bendahara sekolah.</p>
            </div>
        </article>

        <article class="rounded-[2rem] border border-emerald-100 bg-white p-6 shadow-sm">
            <span class="text-xs font-black uppercase tracking-widest text-emerald-700">Estimasi Daftar Ulang</span>
            <h2 class="mt-2 text-xl font-black text-slate-950">Berdasarkan konsentrasi aktif</h2>
            <div class="mt-4 grid gap-2">
                @forelse($jurusans as $jurusan)
                    @php($rincian = $gelombangAktif?->jurusanBiaya?->firstWhere('jurusan_id', $jurusan->id))
                    <details class="group rounded-2xl bg-[#f7fffc] p-3.5 ring-1 ring-emerald-100">
                        <summary class="grid cursor-pointer list-none grid-cols-[2.75rem_minmax(0,1fr)_auto_1rem] items-center gap-3">
                        <div class="contents">
                            @if($jurusan->logo_path)
                                <img src="{{ asset($jurusan->logo_path) }}" class="h-11 w-11 rounded-xl bg-white object-contain p-1 ring-1 ring-emerald-100" alt="Logo {{ $jurusan->name }}">
                            @else
                                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-100 text-xs font-black text-emerald-700">{{ strtoupper(substr($jurusan->name, 0, 2)) }}</span>
                            @endif
                            <h3 class="min-w-0 text-base font-black leading-snug text-slate-950">{{ $jurusan->name }}</h3>
                        </div>
                        <strong class="whitespace-nowrap text-sm font-black text-emerald-700">{{ $rincian?->biaya_masuk ? 'Rp ' . number_format($rincian->biaya_masuk, 0, ',', '.') : 'Hubungi sekolah' }}</strong>
                        <span class="text-emerald-600 transition group-open:rotate-180" aria-hidden="true">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                            </svg>
                        </span>
                        </summary>
                        @if($rincian?->rincian_biaya)
                            <div class="mt-4 space-y-2 border-t border-emerald-100 pt-3">@foreach($rincian->rincian_biaya as $item)<div class="flex justify-between gap-4 text-sm"><span class="text-slate-600">{{ $item['name'] ?? '' }}</span><strong class="text-slate-900">Rp {{ number_format((float)($item['amount'] ?? 0), 0, ',', '.') }}</strong></div>@endforeach</div>
                            <p class="mt-3 text-xs font-bold text-emerald-700">Total rincian dihitung otomatis dari seluruh komponen.</p>
                        @endif
                    </details>
                @empty
                    <div class="rounded-2xl bg-amber-50 p-4 text-sm font-semibold text-amber-800">Biaya konsentrasi belum tersedia.</div>
                @endforelse
            </div>
        </article>
    </section>
</div>
@endsection
