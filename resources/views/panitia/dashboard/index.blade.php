@extends(request()->routeIs('admin.*') ? 'layouts.admin' : 'layouts.panitia')

@section('title', 'Dashboard Panitia')
@section('page_title', 'Beranda Panitia')

@section('content')
@php
    $chartMax = max(1, $activityChart->max(fn ($item) => max($item['visits'], $item['applicants'])));
    $routePrefix = request()->routeIs('admin.*') ? 'admin.' : 'panitia.';
@endphp

<style>
    .panitia-spmb-chart > div > div { gap: 4px; }
    .panitia-spmb-chart i { width: min(18px, 42%); }

    @media (max-width: 640px) {
        .panitia-spmb-chart > div > div { gap: 3px; }
        .panitia-spmb-chart i { width: 9px; }
    }
</style>

<div class="panitia-spmb-dashboard">
    <section class="panitia-spmb-hero">
        <div class="panitia-spmb-hero-copy">
            <p>PUSAT KENDALI PANITIA</p>
            <h2>Selamat datang, {{ Auth::user()->name }}.</h2>
            <span>Pantau pendaftar, kunjungan, dan proses seleksi dari satu tempat.</span>
        </div>
        <div class="panitia-spmb-meta">
            <a href="{{ route($routePrefix . 'pendaftar.index') }}"><span>BERKAS MENUNGGU</span><strong>{{ $readyForReview }}</strong></a>
            <a href="{{ route($routePrefix . 'pendaftar.index') }}"><span>PERLU PERBAIKAN</span><strong>{{ $needsCorrection }}</strong></a>
        </div>
    </section>

    <section class="panitia-spmb-stats">
        <article><span>TOTAL PENDAFTAR</span><strong>{{ $totalPendaftar }}</strong></article>
        <article><span>BERKAS MENUNGGU</span><strong>{{ $readyForReview }}</strong></article>
        <article><span>KUNJUNGAN HARI INI</span><strong>{{ $visitsToday }}</strong></article>
    </section>

    <section class="panitia-spmb-grid">
        <article class="panitia-spmb-panel panitia-spmb-chart-panel">
            <header><div><h3>Aktivitas Panitia</h3><p>Perbandingan pendaftar baru dan kunjungan selama 12 bulan terakhir.</p></div><span class="panitia-spmb-legend"><i></i>Pendaftar <i></i>Kunjungan</span></header>
            <div class="panitia-spmb-chart">
                @foreach($activityChart as $index => $item)
                    <div style="--delay: {{ $index * 40 }}ms"><div><i class="is-applicant" title="{{ $item['applicants'] }} pendaftar" style="--bar-height: {{ $item['applicants'] ? max(8, ($item['applicants'] / $chartMax) * 100) : 2 }}%"></i><i class="is-visit" title="{{ $item['visits'] }} kunjungan" style="--bar-height: {{ $item['visits'] ? max(8, ($item['visits'] / $chartMax) * 100) : 2 }}%"></i></div><span>{{ $item['label'] }}</span></div>
                @endforeach
            </div>
        </article>
        <aside class="panitia-spmb-panel panitia-spmb-priority">
            <header><div><h3>Prioritas hari ini</h3><p>Akses cepat untuk pekerjaan yang perlu diselesaikan.</p></div></header>
            <div>
                <a href="{{ route($routePrefix . 'pendaftar.index') }}"><span>{{ $totalPendaftar }}</span><b>Verifikasi pendaftar</b><i>→</i></a>
                <a href="{{ route('panitia.tes.btq') }}"><span>✓</span><b>Tes SPMB</b><i>→</i></a>
                <a href="{{ route($routePrefix . 'seleksi.index') }}"><span>★</span><b>Seleksi akhir</b><i>→</i></a>
            </div>
        </aside>
    </section>
</div>
@endsection
