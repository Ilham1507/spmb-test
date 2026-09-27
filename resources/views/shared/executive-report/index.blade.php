@extends(auth()->user()?->hasRole('kepala_sekolah') ? 'layouts.kepala-sekolah' : 'layouts.admin')

@section('title', 'Laporan Eksekutif')
@section('page_title', 'Laporan Eksekutif')
@section('page_description', 'Ringkasan penerimaan untuk pengambilan keputusan.')

@section('content')
@php
    $isHeadmaster = auth()->user()?->hasRole('kepala_sekolah');
    $routePrefix = $isHeadmaster ? 'kepala-sekolah.' : 'admin.';
    $accent = $isHeadmaster ? 'teal' : 'blue';
    $rupiah = fn ($value) => 'Rp '.number_format((float) $value, 0, ',', '.');
    $statusLabels = ['draft' => 'Draft', 'submitted' => 'Menunggu verifikasi', 'verified' => 'Terverifikasi', 'accepted' => 'Diterima', 'rejected' => 'Ditolak', 're_registered' => 'Daftar ulang'];
@endphp

<div class="mx-auto max-w-[1320px] space-y-5">
    <section class="overflow-hidden rounded-3xl {{ $isHeadmaster ? 'bg-gradient-to-br from-teal-800 via-teal-700 to-cyan-700' : 'bg-gradient-to-br from-blue-900 via-blue-800 to-sky-700' }} p-6 text-white shadow-xl sm:p-8">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl">
                <p class="text-xs font-black uppercase tracking-[.18em] text-white/80">Laporan SPMB</p>
                <h2 class="mt-2 text-2xl font-black tracking-tight sm:text-3xl">Ringkasan penerimaan yang siap dibahas.</h2>
                <p class="mt-2 text-sm font-semibold leading-6 text-white/85">Rekap jurusan, jalur, status penerimaan, hasil tes, dan realisasi keuangan dalam satu halaman.</p>
            </div>
            <div class="flex flex-wrap gap-2 print:hidden">
                <a href="{{ route($routePrefix.'laporan-eksekutif.pdf') }}" class="rounded-xl bg-white px-4 py-3 text-sm font-black {{ $isHeadmaster ? 'text-teal-800' : 'text-blue-800' }}">Unduh PDF</a>
                <a href="{{ route($routePrefix.'laporan-eksekutif.excel') }}" class="rounded-xl border border-white/35 px-4 py-3 text-sm font-black text-white hover:bg-white/10">Export Excel</a>
            </div>
        </div>
    </section>

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['Total pendaftar', $totalApplicants, 'Seluruh calon siswa'],
            ['Menunggu verifikasi', $pendingVerification, 'Perlu dipantau tim'],
            ['Peserta diterima', $accepted, 'Keputusan seleksi akhir'],
            ['Daftar ulang', $reRegistered, 'Peserta mengonfirmasi'],
        ] as [$label, $value, $caption])
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-[11px] font-black uppercase tracking-[.12em] text-slate-500">{{ $label }}</p><p class="mt-3 text-3xl font-black {{ $isHeadmaster ? 'text-teal-800' : 'text-blue-900' }}">{{ $value }}</p><p class="mt-1 text-xs font-semibold text-slate-500">{{ $caption }}</p></article>
        @endforeach
    </section>

    <section class="grid gap-5 xl:grid-cols-[1.4fr_.9fr]">
        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <header><p class="text-xs font-black uppercase tracking-[.16em] {{ $isHeadmaster ? 'text-teal-700' : 'text-blue-700' }}">Peminat per jurusan</p><h3 class="mt-1 text-xl font-black text-slate-900">Sebaran pilihan utama siswa</h3></header>
            <div class="mt-6 space-y-4">
                @forelse($majorSummary as $major)
                    @php($width = $major->applicants > 0 ? max(12, round(($major->applicants / $chartMaxApplicants) * 100)) : 0)
                    <div><div class="mb-2 flex justify-between gap-3 text-sm"><span class="font-bold text-slate-700">{{ $major->name }}</span><span class="font-black text-slate-900">{{ $major->applicants }} pendaftar</span></div><div class="h-3 overflow-hidden rounded-full border border-slate-200 bg-slate-100"><div class="h-full rounded-full transition-all duration-700" style="width:{{ $width }}%;background-color:{{ $isHeadmaster ? '#0f766e' : '#1d4ed8' }}" title="{{ $major->applicants }} pendaftar"></div></div><p class="mt-1 text-xs font-semibold text-slate-500">{{ $major->accepted }} diterima · {{ $major->re_registered }} daftar ulang</p></div>
                @empty
                    <p class="py-8 text-center text-sm font-semibold text-slate-500">Belum ada data jurusan.</p>
                @endforelse
            </div>
        </article>
        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <p class="text-xs font-black uppercase tracking-[.16em] {{ $isHeadmaster ? 'text-teal-700' : 'text-blue-700' }}">Realisasi pembayaran</p><h3 class="mt-1 text-xl font-black text-slate-900">Penerimaan terverifikasi</h3>
            <div class="mt-6 h-3 overflow-hidden rounded-full border border-slate-200 bg-slate-100"><div class="h-full rounded-full transition-all duration-700" style="width:{{ $financeProgress }}%;background-color:{{ $isHeadmaster ? '#0f766e' : '#1d4ed8' }}" title="{{ $financeProgress }}% dari target"></div></div>
            <div class="mt-4 flex items-end justify-between gap-3"><p class="text-2xl font-black text-slate-950">{{ $rupiah($verifiedRevenue) }}</p><p class="text-right text-xs font-bold text-slate-500">{{ $financeProgress }}% dari target<br>{{ $rupiah($targetRevenue) }}</p></div>
            <div class="mt-6 border-t border-slate-100 pt-4"><p class="text-sm font-black text-slate-800">Pendaftar per jalur</p><div class="mt-3 space-y-3">@forelse($pathSummary as $path)<div class="flex items-center justify-between text-sm"><span class="font-semibold text-slate-600">{{ $path->name }}</span><span class="rounded-full {{ $isHeadmaster ? 'bg-teal-50 text-teal-800' : 'bg-blue-50 text-blue-800' }} px-3 py-1 text-xs font-black">{{ $path->applicants }}</span></div>@empty<p class="text-sm text-slate-500">Belum ada data jalur.</p>@endforelse</div></div>
        </article>
    </section>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <header class="flex flex-col gap-2 border-b border-slate-100 px-5 py-5 sm:flex-row sm:items-end sm:justify-between"><div><p class="text-xs font-black uppercase tracking-[.16em] {{ $isHeadmaster ? 'text-teal-700' : 'text-blue-700' }}">Kapasitas & realisasi</p><h3 class="mt-1 text-xl font-black text-slate-900">Rekap per gelombang dan jurusan</h3></div><p class="text-xs font-semibold text-slate-500">Pembayaran hanya menghitung transaksi terverifikasi.</p></header>
        <div class="overflow-x-auto"><table class="min-w-[940px] w-full text-left text-sm"><thead class="bg-slate-50 text-[11px] font-black uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Gelombang</th><th class="px-5 py-3">Jurusan</th><th class="px-5 py-3 text-right">Kuota gelombang</th><th class="px-5 py-3 text-right">Kuota jurusan</th><th class="px-5 py-3 text-right">Pendaftar</th><th class="px-5 py-3 text-right">Diterima</th><th class="px-5 py-3 text-right">Daftar ulang</th><th class="px-5 py-3 text-right">Pembayaran</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($waveMajorSummary as $row)<tr><td class="px-5 py-3 font-bold text-slate-800">{{ $row->wave_name }}</td><td class="px-5 py-3 text-slate-700">{{ $row->major_name }}</td><td class="px-5 py-3 text-right font-semibold">{{ $row->wave_quota }}</td><td class="px-5 py-3 text-right font-semibold">{{ $row->major_quota }}</td><td class="px-5 py-3 text-right font-black text-slate-900">{{ $row->applicants }}</td><td class="px-5 py-3 text-right font-bold text-emerald-700">{{ $row->accepted }}</td><td class="px-5 py-3 text-right font-bold text-teal-700">{{ $row->re_registered }}</td><td class="px-5 py-3 text-right font-black {{ $isHeadmaster ? 'text-teal-800' : 'text-blue-900' }}">{{ $rupiah($row->verified_payment) }}</td></tr>@empty<tr><td colspan="8" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada data pendaftar.</td></tr>@endforelse</tbody></table></div>
    </section>

    <section class="grid gap-5 xl:grid-cols-2">
        <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm"><header class="border-b border-slate-100 px-5 py-5"><p class="text-xs font-black uppercase tracking-[.16em] {{ $isHeadmaster ? 'text-teal-700' : 'text-blue-700' }}">Status penerimaan</p><h3 class="mt-1 text-xl font-black text-slate-900">Perjalanan pendaftar</h3></header><div class="divide-y divide-slate-100">@foreach($statusLabels as $key => $label)<div class="flex items-center justify-between px-5 py-3"><span class="text-sm font-semibold text-slate-700">{{ $label }}</span><span class="text-lg font-black text-slate-950">{{ $statusCounts[$key] ?? 0 }}</span></div>@endforeach</div></article>
        <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm"><header class="border-b border-slate-100 px-5 py-5"><p class="text-xs font-black uppercase tracking-[.16em] {{ $isHeadmaster ? 'text-teal-700' : 'text-blue-700' }}">Hasil tes</p><h3 class="mt-1 text-xl font-black text-slate-900">Rata-rata nilai per tes</h3></header><div class="divide-y divide-slate-100">@forelse($testSummary as $test)<div class="flex items-center justify-between gap-4 px-5 py-3"><div><p class="text-sm font-bold text-slate-800">{{ $test->test_name }}</p><p class="mt-1 text-xs font-semibold text-slate-500">{{ $test->participants }} peserta hadir</p></div><p class="text-lg font-black {{ $isHeadmaster ? 'text-teal-800' : 'text-blue-900' }}">{{ $test->average_score !== null ? number_format((float) $test->average_score, 1, ',', '.') : '-' }}</p></div>@empty<div class="px-5 py-8 text-center text-sm text-slate-500">Belum ada hasil tes.</div>@endforelse</div></article>
    </section>
    <p class="text-center text-xs font-semibold text-slate-400">Data diperbarui {{ $generatedAt->translatedFormat('d F Y, H:i') }} WIB.</p>
</div>
@endsection
