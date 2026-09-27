<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panitia Dashboard') - Penerimaan Siswa Baru</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@500;600;700;800&family=Plus+Jakarta+Sans:opsz,wght@6..72,500;6..72,600;6..72,700;6..72,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none!important}</style>
</head>
<body data-private-page x-data="{ mobileMenuOpen: false, sidebarMini: false, isMobile: window.innerWidth < 768, tesMenuOpen: {{ str_starts_with(request()->route()?->getName() ?? '', 'panitia.tes') ? 'true' : 'false' }}, tesFlyoutOpen: false, tesFlyoutTop: 180, sidebarTooltip: '', sidebarTooltipTop: 0 }" @resize.window="isMobile = window.innerWidth < 768; if(isMobile){tesFlyoutOpen=false}" class="admin-clean portal-role-panitia portal-unified portal-density flex h-full flex-col overflow-hidden bg-slate-100 text-slate-800">
    @php
        $current = request()->route()?->getName() ?? '';
        $navItems = [
            ['route' => 'panitia.dashboard', 'match' => 'panitia.dashboard', 'label' => 'Dashboard', 'icon' => 'M4 5h7v7H4z M13 5h7v7h-7z M4 14h7v7H4z M13 14h7v7h-7z'],
            ['route' => 'panitia.pembayaran.index', 'match' => 'panitia.pembayaran', 'label' => 'Approve Pembayaran', 'icon' => 'M3 7h18v10H3z M3 10h18 M7 15h4'],
            ['route' => 'panitia.pendaftar.index', 'match' => 'panitia.pendaftar', 'label' => 'Pendaftar', 'icon' => 'M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4z M4 21a8 8 0 0 1 16 0'],
            ['route' => 'panitia.hasil-tes.index', 'match' => 'panitia.hasil-tes', 'label' => 'Hasil Tes', 'icon' => 'M5 3h14v18H5z M8 7h8 M8 11h8 M8 15h5'],
            ['route' => 'panitia.seleksi.index', 'match' => 'panitia.seleksi', 'label' => 'Seleksi Akhir', 'icon' => 'M12 3l2.6 5.3 5.9.9-4.2 4.1 1 5.8L12 16.4 6.7 19.1l1-5.8L3.5 9.2l5.9-.9z'],
            ['route' => 'panitia.kunjungan.index', 'match' => 'panitia.kunjungan', 'label' => 'Kunjungan Siswa', 'icon' => 'M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4z M4 21a8 8 0 0 1 16 0'],
        ];
        $testItems = [
            ['panitia.tes.attendance', 'Daftar Hadir Tes'],
            ['panitia.tes.btq', 'Baca Tulis Quran'],
            ['panitia.tes.uniform', 'Ukuran Seragam'],
            ['panitia.tes.health', 'Kesehatan'],
            ['panitia.tes.cbt', 'CBT'],
            ['panitia.tes.interview', 'Wawancara'],
        ];
    @endphp

    <x-portal-page-header
        :title="trim($__env->yieldContent('page_title', 'Dashboard'))"
        :description="trim($__env->yieldContent('page_description', 'Kelola layanan pendaftar.'))"
        accent="violet"
        nav-only
    />

    <div class="flex min-h-0 flex-1 overflow-hidden">
        <aside
            class="sidebar-shell panitia-sidebar fixed top-0 bottom-0 left-0 z-[70] flex w-[288px] shrink-0 flex-col border-r border-slate-300 bg-white shadow-2xl shadow-slate-950/20 transition-all duration-200 md:static md:pt-0 md:z-auto md:translate-x-0"
            style="margin-top:0 !important;height:100% !important"
            :class="{
                'translate-x-0': mobileMenuOpen,
                '-translate-x-full md:translate-x-0': !mobileMenuOpen,
                'is-mini md:w-[104px]': sidebarMini,
                'md:w-[288px]': !sidebarMini
            }"
        >
            <x-portal-sidebar-brand title="PANITIA" accent="violet" storage-key="spmb-panitia-sidebar-mini" />

            <nav id="panitia-sidebar-nav" class="sidebar-nav flex flex-1 flex-col gap-1 overflow-y-auto overflow-x-hidden py-4">
                @foreach($navItems as $item)
                    @if($item['match'] === 'panitia.pembayaran')
                        <p class="px-7 pb-2 pt-3 text-[10px] font-extrabold uppercase tracking-[.14em] text-slate-400" :class="sidebarMini ? 'md:hidden' : ''">Tahap Proses</p>
                    @endif
                    @if($item['match'] === 'panitia.kunjungan')
                        <div class="mx-6 my-2 border-t border-slate-100" :class="sidebarMini ? 'md:mx-4' : ''"></div>
                        <p class="px-7 pb-2 text-[10px] font-extrabold uppercase tracking-[.14em] text-slate-400" :class="sidebarMini ? 'md:hidden' : ''">Layanan Pendukung</p>
                    @endif
                    @php $active = $item['match'] === $current || str_starts_with($current, $item['match']); @endphp
                    <a href="{{ route($item['route']) }}" class="sidebar-link {{ $active ? 'is-active panitia-active' : '' }}" data-tooltip="{{ $item['label'] }}" :class="sidebarMini ? 'is-mini' : ''" @mouseenter="if(sidebarMini && !isMobile){ const r=$el.getBoundingClientRect(); sidebarTooltip='{{ $item['label'] }}'; sidebarTooltipTop=r.top+r.height/2 }" @mouseleave="sidebarTooltip=''">
                        <span class="sidebar-icon">
                            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="{{ $item['icon'] }}" /></svg>
                        </span>
                        <span class="sidebar-text">{{ $item['label'] }}</span>
                    </a>
                    @if($item['match'] === 'panitia.pendaftar')
                        @php $tesActive = str_starts_with($current, 'panitia.tes'); @endphp
                        <button type="button" @click="const r=$el.getBoundingClientRect(); sidebarTooltip=''; if(sidebarMini && !isMobile){tesMenuOpen=false;tesFlyoutTop=Math.min(r.top,window.innerHeight-310);tesFlyoutOpen=!tesFlyoutOpen}else{tesFlyoutOpen=false;tesMenuOpen=!tesMenuOpen;if(tesMenuOpen)$nextTick(()=>$refs.tesSubmenu?.scrollIntoView({block:'nearest',behavior:'smooth'}))}"
                                class="sidebar-link w-full {{ $tesActive ? 'is-active panitia-active' : '' }}" data-tooltip="Tes SPMB" :class="sidebarMini ? 'is-mini' : ''" @mouseenter="if(sidebarMini && !isMobile && !tesFlyoutOpen){const r=$el.getBoundingClientRect();sidebarTooltip='Tes SPMB';sidebarTooltipTop=r.top+r.height/2}" @mouseleave="sidebarTooltip=''">
                            <span class="sidebar-icon"><svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5h6 M9 9h6 M7 13h10 M7 17h6 M5 3h14v18H5z" /></svg></span>
                            <span class="sidebar-text flex flex-1 items-center justify-between">Tes SPMB
                                <svg class="h-4 w-4 transition-transform" :class="tesMenuOpen ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                            </span>
                        </button>
                        <div x-ref="tesSubmenu" x-cloak x-show="tesMenuOpen && (!sidebarMini || isMobile)" x-transition class="panitia-test-links mx-4 mb-3 space-y-1 rounded-2xl border border-emerald-100 bg-emerald-50/70 p-2">
                            @foreach($testItems as [$tesRoute, $tesLabel])
                                <a href="{{ route($tesRoute) }}" class="block rounded-xl px-4 py-3 text-sm font-bold transition {{ $current === $tesRoute ? 'bg-emerald-600 text-white shadow-sm' : 'bg-white text-slate-700 hover:bg-emerald-100 hover:text-emerald-800' }}">{{ $tesLabel }}</a>
                            @endforeach
                        </div>
                    @endif
                @endforeach
            </nav>

        </aside>

        <div x-cloak x-show="sidebarTooltip && sidebarMini && !isMobile && !(tesFlyoutOpen && sidebarTooltip === 'Tes SPMB')" class="pointer-events-none fixed left-[112px] z-[100] -translate-y-1/2 rounded-xl bg-slate-900 px-3 py-2 text-xs font-black text-white shadow-xl" :style="`top:${sidebarTooltipTop}px`" x-text="sidebarTooltip"></div>

        <div x-cloak x-show="tesFlyoutOpen && sidebarMini && !isMobile" x-transition @click.outside="tesFlyoutOpen=false" class="fixed left-[112px] z-[100] w-60 rounded-2xl border border-emerald-200 bg-white p-3 shadow-2xl" :style="`top:${tesFlyoutTop}px`">
            <p class="mb-2 px-2 text-xs font-black uppercase tracking-wide text-emerald-700">Tes SPMB</p>
            @foreach($testItems as [$tesRoute, $tesLabel])
                <a href="{{ route($tesRoute) }}" class="mb-1 block rounded-xl px-4 py-3 text-sm font-bold {{ $current === $tesRoute ? 'bg-emerald-600 text-white' : 'text-slate-700 hover:bg-emerald-50 hover:text-emerald-800' }}">{{ $tesLabel }}</a>
            @endforeach
        </div>

        <div x-cloak x-show="mobileMenuOpen" x-transition.opacity class="fixed inset-0 z-[60] bg-slate-950/45 backdrop-blur-sm md:hidden" @click="mobileMenuOpen = false"></div>

        <main class="flex min-w-0 flex-1 flex-col overflow-hidden">
            <x-portal-page-header :title="trim($__env->yieldContent('page_title', 'Dashboard'))" :description="trim($__env->yieldContent('page_description', 'Kelola layanan pendaftar.'))" accent="violet" heading-only />

        <section class="portal-page-content flex-1 overflow-y-auto p-4 lg:p-6">
                <div class="mx-auto max-w-[1500px]">
                    <x-portal-flash />
                    @yield('content')
                    <x-portal-footer />
                </div>
            </section>
        </main>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebarNav = document.getElementById('panitia-sidebar-nav');
            if (sidebarNav) {
                requestAnimationFrame(() => { sidebarNav.scrollTop = 0; });
            }
        });
    </script>
    <x-global-loading />
    <x-auto-list-tools />
    @stack('scripts')
</body>
</html>
