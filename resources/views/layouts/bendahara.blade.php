<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Bendahara Dashboard') - Penerimaan Siswa Baru</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@500;600;700;800&family=Plus+Jakarta+Sans:opsz,wght@6..72,500;6..72,600;6..72,700;6..72,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
    </style>
</head>
<body data-private-page x-data="{ mobileMenuOpen: false, sidebarMini: false, isMobile: window.innerWidth < 768, sidebarTesOpen: {{ (str_starts_with(request()->route()?->getName() ?? '', 'bendahara.tes') || str_starts_with(request()->route()?->getName() ?? '', 'bendahara.hasil-tes')) ? 'true' : 'false' }} }" @resize.window="isMobile = window.innerWidth < 768" class="admin-clean portal-role-bendahara portal-unified portal-density flex h-full flex-col overflow-hidden bg-slate-100 text-slate-800">
    @php
        $current = request()->route()?->getName() ?? '';
        $navItems = [
            ['route' => 'bendahara.dashboard', 'match' => 'bendahara.dashboard', 'label' => 'Dashboard', 'icon' => 'M4 5h7v7H4z M13 5h7v7h-7z M4 14h7v7H4z M13 14h7v7h-7z'],
            ['route' => 'bendahara.pembayaran.index', 'match' => 'bendahara.pembayaran', 'label' => 'Manajemen Pembayaran', 'icon' => 'M3 7h18v10H3z M3 10h18 M7 15h4'],
            ['route' => 'bendahara.pendaftar.index', 'match' => 'bendahara.pendaftar', 'label' => 'Data Pendaftar', 'icon' => 'M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4z M4 21a8 8 0 0 1 16 0'],
            ['route' => 'bendahara.kunjungan.index', 'match' => 'bendahara.kunjungan', 'label' => 'Kunjungan Siswa', 'icon' => 'M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4z M4 21a8 8 0 0 1 16 0'],
            ['route' => 'bendahara.promosi-minat.index', 'match' => 'bendahara.promosi-minat', 'label' => 'Hasil Promosi', 'icon' => 'M4 5h16v14H4z M7 9h10 M7 13h7 M7 17h5'],
            ['route' => 'bendahara.keuangan.biaya-pendaftaran.index', 'match' => 'bendahara.keuangan.biaya-pendaftaran', 'label' => 'Biaya Pendaftaran', 'icon' => 'M4 7h16v10H4z M8 11h8 M8 15h4'],
            ['route' => 'bendahara.keuangan.biaya-jurusan.index', 'match' => 'bendahara.keuangan.biaya-jurusan', 'label' => 'Biaya Jurusan', 'icon' => 'M4 7h16v10H4z M8 11h8 M8 15h4'],
            ['route' => 'bendahara.laporan.index', 'match' => 'bendahara.laporan', 'label' => 'Laporan', 'icon' => 'M5 19V5a2 2 0 0 1 2-2h8l4 4v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2z M14 3v5h5 M9 14h6 M9 17h6 M9 11h2'],
        ];
    @endphp

    <x-portal-page-header
        :title="trim($__env->yieldContent('page_title', 'Dashboard'))"
        :description="trim($__env->yieldContent('page_description', 'Kelola keuangan peserta.'))"
        accent="amber"
        nav-only
    />

    <div class="flex min-h-0 flex-1 overflow-hidden">
    <!-- Sidebar -->
    <aside
        class="sidebar-shell fixed top-0 bottom-0 left-0 z-[70] flex w-[288px] shrink-0 flex-col border-r border-slate-300 bg-white shadow-2xl shadow-slate-950/20 transition-all duration-200 md:static md:pt-0 md:z-auto md:translate-x-0"
        :class="{
            'translate-x-0': mobileMenuOpen,
            '-translate-x-full md:translate-x-0': !mobileMenuOpen,
            'is-mini md:w-[104px]': sidebarMini,
            'md:w-[288px]': !sidebarMini
        }"
    >
        <x-portal-sidebar-brand title="BENDAHARA" accent="amber" storage-key="spmb-bendahara-sidebar-mini" />
        <nav class="sidebar-nav flex flex-1 flex-col gap-1 overflow-y-auto overflow-x-hidden py-4">
            @foreach($navItems as $item)
                @if($item['match'] === 'bendahara.pembayaran')
                    <p class="px-7 pb-2 pt-3 text-[10px] font-extrabold uppercase tracking-[.14em] text-slate-400" :class="sidebarMini ? 'md:hidden' : ''">Tahap Pembayaran</p>
                @endif
                @if($item['match'] === 'bendahara.pendaftar')
                    <div class="mx-6 my-2 border-t border-slate-100" :class="sidebarMini ? 'md:mx-4' : ''"></div>
                    <p class="px-7 pb-2 text-[10px] font-extrabold uppercase tracking-[.14em] text-slate-400" :class="sidebarMini ? 'md:hidden' : ''">Data & Laporan</p>
                @endif
                @php $active = $item['match'] === $current || str_starts_with($current, $item['match']); @endphp
                <a href="{{ route($item['route']) }}" class="sidebar-link {{ $active ? 'is-active bendahara-active' : '' }}" data-tooltip="{{ $item['label'] }}" :class="sidebarMini ? 'is-mini' : ''">
                    <span class="sidebar-icon">
                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="{{ $item['icon'] }}" /></svg>
                    </span>
                    <span class="sidebar-text">{{ $item['label'] }}</span>
                </a>
                @if($item['match'] === 'bendahara.pendaftar')
                    @php $tesActive = str_starts_with($current, 'bendahara.tes') || str_starts_with($current, 'bendahara.hasil-tes'); @endphp
                    <button type="button" @click="sidebarTesOpen = !sidebarTesOpen" class="sidebar-link w-full {{ $tesActive ? 'is-active bendahara-active' : '' }}" data-tooltip="Tes SPMB" :class="sidebarMini ? 'is-mini' : ''">
                        <span class="sidebar-icon"><svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5h6 M9 9h6 M7 13h10 M7 17h6 M5 3h14v18H5z" /></svg></span>
                        <span class="sidebar-text flex flex-1 items-center justify-between">Tes SPMB
                            <svg class="h-4 w-4 transition-transform" :class="sidebarTesOpen ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                        </span>
                    </button>
                    <div x-cloak x-show="sidebarTesOpen && (!sidebarMini || isMobile)" x-transition class="panitia-test-links mx-4 mb-3 space-y-1 rounded-2xl border border-amber-100 bg-amber-50/70 p-2">
                        <a href="{{ route('bendahara.tes.attendance') }}" class="block rounded-xl px-4 py-3 text-sm font-bold transition {{ $current === 'bendahara.tes.attendance' ? 'bg-amber-600 text-white shadow-sm' : 'bg-white text-slate-700 hover:bg-amber-100 hover:text-amber-800' }}">Daftar Hadir Tes</a>
                        <a href="{{ route('bendahara.tes.btq') }}" class="block rounded-xl px-4 py-3 text-sm font-bold transition {{ $current === 'bendahara.tes.btq' ? 'bg-amber-600 text-white shadow-sm' : 'bg-white text-slate-700 hover:bg-amber-100 hover:text-amber-800' }}">Baca Tulis Quran</a>
                        <a href="{{ route('bendahara.tes.uniform') }}" class="block rounded-xl px-4 py-3 text-sm font-bold transition {{ $current === 'bendahara.tes.uniform' ? 'bg-amber-600 text-white shadow-sm' : 'bg-white text-slate-700 hover:bg-amber-100 hover:text-amber-800' }}">Ukuran Seragam</a>
                        <a href="{{ route('bendahara.tes.health') }}" class="block rounded-xl px-4 py-3 text-sm font-bold transition {{ $current === 'bendahara.tes.health' ? 'bg-amber-600 text-white shadow-sm' : 'bg-white text-slate-700 hover:bg-amber-100 hover:text-amber-800' }}">Kesehatan</a>
                        <a href="{{ route('bendahara.tes.cbt') }}" class="block rounded-xl px-4 py-3 text-sm font-bold transition {{ $current === 'bendahara.tes.cbt' ? 'bg-amber-600 text-white shadow-sm' : 'bg-white text-slate-700 hover:bg-amber-100 hover:text-amber-800' }}">CBT</a>
                        <a href="{{ route('bendahara.tes.interview') }}" class="block rounded-xl px-4 py-3 text-sm font-bold transition {{ $current === 'bendahara.tes.interview' ? 'bg-amber-600 text-white shadow-sm' : 'bg-white text-slate-700 hover:bg-amber-100 hover:text-amber-800' }}">Wawancara</a>
                        <a href="{{ route('bendahara.hasil-tes.index') }}" class="block rounded-xl px-4 py-3 text-sm font-bold transition {{ str_starts_with($current, 'bendahara.hasil-tes') ? 'bg-amber-600 text-white shadow-sm' : 'bg-white text-slate-700 hover:bg-amber-100 hover:text-amber-800' }}">Hasil Tes</a>
                    </div>
                @endif
            @endforeach
        </nav>
    </aside>

    <div x-cloak x-show="mobileMenuOpen" x-transition.opacity class="fixed inset-0 z-[60] bg-slate-950/45 backdrop-blur-sm md:hidden" @click="mobileMenuOpen = false"></div>

    <!-- Main Content Area -->
    <main class="flex min-w-0 flex-1 flex-col overflow-hidden">
        <x-portal-page-header :title="trim($__env->yieldContent('page_title', 'Dashboard'))" :description="trim($__env->yieldContent('page_description', 'Kelola keuangan peserta.'))" accent="amber" heading-only />

        <!-- Page Content -->
        <section class="portal-page-content flex-1 overflow-y-auto p-4 md:p-8">
            <x-portal-flash />
            @yield('content')
            <x-portal-footer />
        </section>
    </main>
    </div>
    <x-global-loading />
    <x-delete-confirmation-modal />
    <x-auto-list-tools />
</body>
</html>
