<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard Kepala Sekolah') - Penerimaan Siswa Baru</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@500;600;700;800&family=Plus+Jakarta+Sans:opsz,wght@6..72,500;6..72,600;6..72,700;6..72,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none!important}.portal-role-kepala{--portal-accent:#087a70;--portal-accent-deep:#0b625b;--portal-soft:#e6f5f2;--portal-line:#cce8e3}</style>
</head>
@php
    $currentRoute = request()->route()?->getName() ?? '';
    $testItems = [
        ['panitia.tes.attendance', 'Daftar Hadir Tes'],
        ['panitia.tes.btq', 'Baca Tulis Quran'],
        ['panitia.tes.uniform', 'Ukuran Seragam'],
        ['panitia.tes.health', 'Kesehatan'],
        ['panitia.tes.cbt', 'CBT'],
        ['panitia.tes.interview', 'Wawancara'],
    ];
@endphp
<body data-private-page x-data="{ mobileMenuOpen:false, sidebarMini:false, isMobile:window.innerWidth < 768, tesMenuOpen: {{ str_starts_with($currentRoute, 'panitia.tes') ? 'true' : 'false' }} }" @resize.window="isMobile=window.innerWidth < 768" class="admin-clean portal-role-kepala portal-unified portal-density flex h-full flex-col overflow-hidden bg-slate-100 text-slate-800">
    <x-portal-page-header :title="trim($__env->yieldContent('page_title', 'Dashboard'))" accent="teal" nav-only />
    <div class="flex min-h-0 flex-1 overflow-hidden">
        <aside class="sidebar-shell fixed top-0 bottom-0 left-0 z-[70] flex w-[288px] shrink-0 flex-col border-r border-slate-300 bg-white shadow-2xl shadow-slate-950/20 transition-all duration-200 md:static md:z-auto md:translate-x-0"
            :class="{'translate-x-0':mobileMenuOpen,'-translate-x-full md:translate-x-0':!mobileMenuOpen,'is-mini md:w-[104px]':sidebarMini,'md:w-[288px]':!sidebarMini}">
            <x-portal-sidebar-brand title="KEPALA SEKOLAH" accent="teal" storage-key="spmb-kepala-sekolah-sidebar-mini" />
            <nav class="sidebar-nav flex flex-1 flex-col gap-1 overflow-y-auto overflow-x-hidden py-4">
                <a href="{{ route('kepala-sekolah.dashboard') }}" class="sidebar-link {{ request()->routeIs('kepala-sekolah.dashboard') ? 'is-active' : '' }}" data-tooltip="Dashboard" :class="sidebarMini ? 'is-mini' : ''"><span class="sidebar-icon"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 5h7v7H4z M13 5h7v7h-7z M4 14h7v7H4z M13 14h7v7h-7z"/></svg></span><span class="sidebar-text">Dashboard</span></a>
                <a href="{{ route('kepala-sekolah.laporan-eksekutif.index') }}" class="sidebar-link {{ request()->routeIs('kepala-sekolah.laporan-eksekutif.*') ? 'is-active' : '' }}" data-tooltip="Laporan Eksekutif" :class="sidebarMini ? 'is-mini' : ''"><span class="sidebar-icon"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 19V5a2 2 0 0 1 2-2h8l4 4v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2z M14 3v5h5 M9 13h6 M9 17h6"/></svg></span><span class="sidebar-text">Laporan Eksekutif</span></a>
                <p class="px-7 pb-2 pt-3 text-[10px] font-extrabold uppercase tracking-[.14em] text-slate-400" :class="sidebarMini ? 'md:hidden' : ''">Pemantauan SPMB</p>
                @foreach([
                    [route('kepala-sekolah.pendaftar.index'), 'Pendaftar', 'M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4z M4 21a8 8 0 0 1 16 0'],
                    [route('panitia.pembayaran.index'), 'Pembayaran', 'M3 7h18v10H3z M3 10h18 M7 15h4'],
                    [route('panitia.hasil-tes.index'), 'Hasil Tes', 'M5 3h14v18H5z M8 7h8 M8 11h8 M8 15h5'],
                    [route('panitia.seleksi.index'), 'Seleksi Akhir', 'M12 3l2.6 5.3 5.9.9-4.2 4.1 1 5.8L12 16.4 6.7 19.1l1-5.8L3.5 9.2l5.9-.9z'],
                    [route('kepala-sekolah.kunjungan.index'), 'Kunjungan Siswa', 'M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4z M4 21a8 8 0 0 1 16 0'],
                    [route('kepala-sekolah.promosi-minat.index'), 'Hasil Promosi', 'M4 4h16v16H4z M8 8h8 M8 12h8 M8 16h5'],
                ] as [$url, $label, $icon])
                    <a href="{{ $url }}" class="sidebar-link {{ request()->url() === $url ? 'is-active' : '' }}" data-tooltip="{{ $label }}" :class="sidebarMini ? 'is-mini' : ''"><span class="sidebar-icon"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="{{ $icon }}"/></svg></span><span class="sidebar-text">{{ $label }}</span></a>
                    @if($label === 'Pendaftar')
                        <button type="button" @click="if(sidebarMini && !isMobile){sidebarMini=false;$nextTick(()=>tesMenuOpen=true)}else{tesMenuOpen=!tesMenuOpen}" class="sidebar-link w-full {{ str_starts_with($currentRoute, 'panitia.tes') ? 'is-active' : '' }}" data-tooltip="Tes SPMB" :class="sidebarMini ? 'is-mini' : ''">
                            <span class="sidebar-icon"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 3h14v18H5z M8 7h8 M8 11h8 M8 15h5"/></svg></span>
                            <span class="sidebar-text flex flex-1 items-center justify-between">Tes SPMB<svg class="h-4 w-4 transition-transform" :class="tesMenuOpen ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg></span>
                        </button>
                        <div x-cloak x-show="tesMenuOpen && (!sidebarMini || isMobile)" x-transition class="mx-4 mb-3 space-y-1 rounded-2xl border border-teal-100 bg-teal-50/70 p-2">
                            @foreach($testItems as [$testRoute, $testLabel])
                                <a href="{{ route($testRoute) }}" class="block rounded-xl px-4 py-3 text-sm font-bold transition {{ $currentRoute === $testRoute ? 'bg-teal-700 text-white shadow-sm' : 'bg-white text-slate-700 hover:bg-teal-100 hover:text-teal-800' }}">{{ $testLabel }}</a>
                            @endforeach
                        </div>
                    @endif
                @endforeach
                <p class="px-7 pb-2 pt-5 text-[10px] font-extrabold uppercase tracking-[.14em] text-slate-400" :class="sidebarMini ? 'md:hidden' : ''">Publikasi</p>
                <a href="{{ route('kepala-sekolah.berita-landing.index') }}" class="sidebar-link {{ request()->routeIs('kepala-sekolah.berita-landing.*') ? 'is-active' : '' }}" data-tooltip="Berita & Artikel" :class="sidebarMini ? 'is-mini' : ''"><span class="sidebar-icon"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 3h14v18H5z M8 7h8 M8 11h8 M8 15h5"/></svg></span><span class="sidebar-text">Berita & Artikel</span></a>
            </nav>
        </aside>
        <div x-cloak x-show="mobileMenuOpen" x-transition.opacity class="fixed inset-0 z-[60] bg-slate-950/45 backdrop-blur-sm md:hidden" @click="mobileMenuOpen=false"></div>
        <main class="flex min-w-0 flex-1 flex-col overflow-hidden"><x-portal-page-header :title="trim($__env->yieldContent('page_title', 'Dashboard'))" accent="teal" heading-only /><section class="portal-page-content flex-1 overflow-y-auto p-4 lg:p-6"><div class="mx-auto max-w-[1500px]"><x-portal-flash />@yield('content')<x-portal-footer /></div></section></main>
    </div>
    <x-global-loading /><x-delete-confirmation-modal /><x-auto-list-tools />@stack('scripts')
</body>
</html>
