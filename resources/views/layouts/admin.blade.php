<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') - {{ $siteSettings['portal_name'] }} {{ $siteSettings['school_short_name'] }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@500;600;700;800&family=Plus+Jakarta+Sans:opsz,wght@6..72,500;6..72,600;6..72,700;6..72,800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none!important}</style>
</head>
<body data-private-page x-data="{ profileMenu:false, sidebarUserOpen:false, sidebarTestOpen:false, sidebarMasterOpen:false, sidebarAdminOpen:false, sidebarOpsOpen:false, sidebarFinanceOpen:false, mobileMenuOpen:false }" class="admin-clean portal-role-admin portal-density h-full overflow-hidden bg-slate-100 text-slate-800">
    @php
        $current = request()->route()?->getName() ?? '';
        $isPrintPage = request()->routeIs('admin.pendaftar.cetak', 'admin.pendaftar.pdf', 'panitia.pendaftar.cetak', 'panitia.pendaftar.pdf', 'peserta.cetak', 'peserta.pdf');
        $navItems = [
            ['admin.dashboard', 'admin.dashboard', 'Dashboard'],
            ['admin.whatsapp-chat.index', 'admin.whatsapp-chat', 'Chat WhatsApp', 'M4 4h16v12H8l-4 4V4z M8 8h8 M8 12h5'],
            ['admin.laporan-eksekutif.index', 'admin.laporan-eksekutif', 'Laporan Eksekutif', 'M5 19V5a2 2 0 0 1 2-2h8l4 4v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2z M14 3v5h5 M9 14h6 M9 17h6 M9 11h2'],
            ['admin.pendaftar.index', 'admin.pendaftar', 'Pendaftar', 'M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4z M4 21a8 8 0 0 1 16 0'],
        ];
        $testActive = str_starts_with($current, 'admin.tes');
        $userActive = str_starts_with($current, 'admin.users') || str_starts_with($current, 'admin.siswa');
        $masterActive = str_starts_with($current, 'admin.master') || str_starts_with($current, 'admin.jurusan') || str_starts_with($current, 'admin.gelombang') || str_starts_with($current, 'admin.pengaturan') || str_starts_with($current, 'admin.informasi-tes');
        $adminPortalActive = str_starts_with($current, 'admin.konfigurasi') || str_starts_with($current, 'admin.identitas') || str_starts_with($current, 'admin.formulir') || str_starts_with($current, 'admin.database-backup') || str_starts_with($current, 'admin.activity-log') || str_starts_with($current, 'admin.arsip-periode');
        $opsActive = str_starts_with($current, 'admin.seleksi') || str_starts_with($current, 'admin.kunjungan') || str_starts_with($current, 'admin.promosi-minat');
        $financeActive = str_starts_with($current, 'admin.tagihan') || str_starts_with($current, 'admin.laporan') || str_starts_with($current, 'admin.event') || str_starts_with($current, 'admin.biaya-jurusan') || str_starts_with($current, 'admin.pembayaran') || str_starts_with($current, 'admin.keuangan');
        $testItems = [
            [route('admin.tes.btq'), 'Baca Tulis Quran', 'admin.tes.btq'],
            [route('admin.tes.uniform'), 'Ukuran Seragam', 'admin.tes.uniform'],
            [route('admin.tes.health'), 'Kesehatan', 'admin.tes.health'],
            [route('admin.tes.cbt'), 'Kelola Soal CBT', 'admin.tes.cbt'],
            [route('admin.tes.interview'), 'Pertanyaan Wawancara', 'admin.tes.interview'],
            [route('admin.hasil-tes.index'), 'Laporan Hasil Tes', 'admin.hasil-tes'],
        ];
        $opsItems = [
            [route('admin.seleksi.index'), 'Seleksi Akhir', 'admin.seleksi'],
            [route('admin.kunjungan.index'), 'Kunjungan Piket', 'admin.kunjungan'],
            [route('admin.promosi-minat.index'), 'Hasil Promosi', 'admin.promosi-minat'],
        ];
        $financeItems = [
            [route('admin.pembayaran.index'), 'Manajemen Pembayaran', 'admin.pembayaran'],
            [route('admin.biaya-jurusan.index'), 'Biaya Jurusan', 'admin.biaya-jurusan'],
            [route('admin.keuangan.biaya-pendaftaran.index'), 'Biaya Pendaftaran', 'admin.keuangan'],
            [route('admin.event.index'), 'Event & Promo', 'admin.event'],
            [route('admin.laporan.index'), 'Laporan Keuangan', 'admin.laporan'],
        ];
        $userItems = [
            [route('admin.siswa.index'), 'Data Siswa', 'admin.siswa'],
            [route('admin.users.index'), 'Panitia & Bendahara', 'admin.users'],
        ];
        $masterItems = [
            [route('admin.master.sekolah.index'), 'Asal Sekolah', 'admin.master.sekolah'],
            [route('admin.master.agama.index'), 'Agama', 'admin.master.agama'],
            [route('admin.master.pekerjaan.index'), 'Pekerjaan', 'admin.master.pekerjaan'],
            [route('admin.master.pendidikan.index'), 'Pendidikan', 'admin.master.pendidikan'],
            [route('admin.master.penghasilan.index'), 'Penghasilan', 'admin.master.penghasilan'],
            [route('admin.master.jalur-pendaftaran.index'), 'Jalur Pendaftaran', 'admin.master.jalur-pendaftaran'],
            [route('admin.gelombang.index'), 'Gelombang', 'admin.gelombang'],
            [route('admin.jurusan.index'), 'Jurusan', 'admin.jurusan'],
            [route('admin.informasi-tes.index'), 'Informasi Tes', 'admin.informasi-tes'],
        ];
        $adminPortalItems = [
            [route('admin.konfigurasi.index'), 'Konfigurasi SPMB', 'admin.konfigurasi'],
            [route('admin.identitas.index'), 'Identitas Sekolah', 'admin.identitas'],
            [route('admin.formulir.index'), 'Kelola Formulir', 'admin.formulir'],
            [route('admin.database-backup.index'), 'Backup Database', 'admin.database-backup'],
            [route('admin.activity-log.index'), 'Audit Aktivitas', 'admin.activity-log'],
            [route('admin.arsip-periode.index'), 'Arsip Periode', 'admin.arsip-periode'],
        ];
    @endphp

    <div class="flex h-full flex-col">
        <header class="portal-topbar portal-topbar-admin relative z-50 shrink-0 border-b border-slate-200 bg-white shadow-sm">
            <div class="mx-auto flex h-[70px] max-w-[1600px] items-center gap-5 px-4 lg:px-7">
                @unless($isPrintPage)<button type="button" @click="mobileMenuOpen=true" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-800 lg:hidden" aria-label="Buka menu">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
                </button>@endunless
                <a href="{{ route('admin.dashboard') }}" class="flex shrink-0 items-center gap-3 text-slate-950 no-underline">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl border border-blue-100 bg-white shadow-sm"><img src="{{ asset($siteSettings['school_logo']) }}" alt="Logo sekolah" class="h-9 w-9 object-contain"></span>
                    <span class="hidden sm:block"><strong class="block text-sm font-black leading-tight">ADMIN {{ strtoupper($siteSettings['portal_name']) }}</strong><small class="text-[11px] font-bold text-slate-500">{{ $siteSettings['school_short_name'] }}</small></span>
                </a>

                <div class="ml-auto flex items-center gap-2">
                    <span data-active-academic-year class="inline-flex rounded-full bg-blue-50 px-2 py-1 text-[10px] font-extrabold text-blue-800 sm:px-3 sm:py-1.5 sm:text-xs">TA {{ $activeAcademicYear?->name ?? '-' }}</span>
                    <x-portal-account-menu accent="emerald" />
                </div>
            </div>
        </header>

        <div class="flex min-h-0 flex-1 overflow-hidden">
            @unless($isPrintPage)<aside class="admin-sidebar portal-sidebar-shell fixed inset-y-0 left-0 z-[80] flex w-[280px] shrink-0 -translate-x-full flex-col border-r border-slate-200 bg-white shadow-2xl transition-transform duration-200 lg:static lg:z-auto lg:w-60 lg:translate-x-0 lg:shadow-none" :class="mobileMenuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
                <div class="portal-sidebar-brand flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 lg:hidden">
                    <a href="{{ route('admin.dashboard') }}" class="flex min-w-0 items-center gap-3"><span class="portal-sidebar-logo"><img src="{{ asset($siteSettings['school_logo']) }}" alt="Logo sekolah"></span><span class="min-w-0"><strong class="block truncate">ADMIN SPMB</strong><small>Panel pengelolaan</small></span></a><button type="button" @click="mobileMenuOpen=false" class="portal-sidebar-close lg:hidden" aria-label="Tutup menu"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 6 12 12M18 6 6 18"/></svg></button>
                </div>
                <nav class="flex-1 space-y-1 overflow-y-auto p-3">
                    @foreach($navItems as $item)
                        @if($item[1] === 'admin.pendaftar')
                        @endif
                        @php [$routeName,$match,$label,$icon] = array_pad($item, 4, 'M4 5h7v7H4z M13 5h7v7h-7z M4 14h7v7H4z M13 14h7v7h-7z'); @endphp
                        @php $active = $current === $match || str_starts_with($current, $match.'.'); @endphp
                        <a href="{{ route($routeName) }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-bold transition {{ $active ? 'bg-blue-700 text-white shadow-sm' : 'text-slate-600 hover:bg-blue-50 hover:text-blue-800' }}">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg {{ $active ? 'bg-white/20' : 'bg-slate-100' }}">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="{{ $icon }}"/></svg>
                            </span>
                            {{ $label }}
                            @if($match === 'admin.whatsapp-chat')
                                <span x-data="whatsappUnreadBadge()" @whatsapp-unread.window="count = $event.detail.unread" x-cloak x-show="count > 0" x-text="count > 99 ? '99+' : count" class="ml-auto rounded-full bg-emerald-500 px-2 py-0.5 text-xs text-white" aria-label="Pesan WhatsApp belum dibaca"></span>
                            @endif
                        </a>
                    @endforeach

                    @php($articleActive = str_starts_with($current, 'admin.berita-landing'))
                    <a href="{{ route('admin.berita-landing.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-bold transition {{ $articleActive ? 'bg-blue-700 text-white shadow-sm' : 'text-slate-600 hover:bg-blue-50 hover:text-blue-800' }}">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg {{ $articleActive ? 'bg-white/20' : 'bg-slate-100' }}">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 3h14v18H5z M8 7h8 M8 11h8 M8 15h5"/></svg>
                        </span>
                        Berita & Artikel
                    </a>

                    <div class="pt-2">
                        <button type="button" x-init="sidebarTestOpen={{ $testActive ? 'true' : 'false' }}" @click="sidebarTestOpen=!sidebarTestOpen; sidebarUserOpen=false; sidebarOpsOpen=false; sidebarFinanceOpen=false; sidebarMasterOpen=false; sidebarAdminOpen=false" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-bold transition {{ $testActive ? 'bg-blue-50 text-blue-800' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg {{ $testActive ? 'bg-blue-100' : 'bg-slate-100' }}">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5h6 M9 9h6 M7 13h10 M7 17h6 M5 3h14v18H5z"/></svg>
                            </span>
                            <span class="flex-1">Tes SPMB</span>
                            <svg class="h-4 w-4 transition-transform" :class="sidebarTestOpen ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                        </button>
                        <div x-cloak x-show="sidebarTestOpen" x-transition class="ml-5 mt-1 space-y-0.5 border-l border-blue-200 pl-3">
                            @foreach ($testItems as [$url,$label,$match])
                                <a href="{{ $url }}" @click="mobileMenuOpen=false" class="admin-test-link block rounded-lg px-3 py-2 text-[13px] font-bold transition {{ $current === $match ? 'bg-blue-100 text-blue-800' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-950' }}">{{ $label }}</a>
                            @endforeach
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="button" x-init="sidebarOpsOpen={{ $opsActive ? 'true' : 'false' }}" @click="sidebarOpsOpen=!sidebarOpsOpen; sidebarUserOpen=false; sidebarTestOpen=false; sidebarFinanceOpen=false; sidebarMasterOpen=false; sidebarAdminOpen=false" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-bold transition {{ $opsActive ? 'bg-blue-50 text-blue-800' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg {{ $opsActive ? 'bg-blue-100' : 'bg-slate-100' }}">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                            </span>
                            <span class="flex-1">Proses SPMB</span>
                            <svg class="h-4 w-4 transition-transform" :class="sidebarOpsOpen ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                        </button>
                        <div x-cloak x-show="sidebarOpsOpen" x-transition class="ml-5 mt-1 space-y-0.5 border-l border-blue-200 pl-3">
                            @foreach ($opsItems as [$url,$label,$match])
                                <a href="{{ $url }}" class="block rounded-lg px-3 py-2 text-[13px] font-bold transition {{ str_starts_with($current, $match) ? 'bg-blue-100 text-blue-800' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-950' }}">{{ $label }}</a>
                            @endforeach
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="button" x-init="sidebarFinanceOpen={{ $financeActive ? 'true' : 'false' }}" @click="sidebarFinanceOpen=!sidebarFinanceOpen; sidebarUserOpen=false; sidebarTestOpen=false; sidebarOpsOpen=false; sidebarMasterOpen=false; sidebarAdminOpen=false" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-bold transition {{ $financeActive ? 'bg-amber-50 text-amber-800' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg {{ $financeActive ? 'bg-amber-100' : 'bg-slate-100' }}">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7h18v10H3z"/><path d="M7 11h.01M17 13h.01"/></svg>
                            </span>
                            <span class="flex-1">Keuangan</span>
                            <svg class="h-4 w-4 transition-transform" :class="sidebarFinanceOpen ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                        </button>
                        <div x-cloak x-show="sidebarFinanceOpen" x-transition class="ml-5 mt-1 space-y-0.5 border-l border-amber-200 pl-3">
                            @foreach ($financeItems as [$url,$label,$match])
                                <a href="{{ $url }}" class="block rounded-lg px-3 py-2 text-[13px] font-bold transition {{ str_starts_with($current, $match) ? 'bg-amber-100 text-amber-800' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-950' }}">{{ $label }}</a>
                            @endforeach
                        </div>
                    </div>

                    <div class="mx-3 my-3 border-t border-slate-100"></div>
                    <p class="px-3 pb-1 text-[10px] font-extrabold uppercase tracking-[.14em] text-slate-400">Pengelolaan Sistem</p>
                    <div class="pt-2">
                        <button type="button" x-init="sidebarUserOpen={{ $userActive ? 'true' : 'false' }}" @click="sidebarUserOpen=!sidebarUserOpen; sidebarTestOpen=false; sidebarOpsOpen=false; sidebarFinanceOpen=false; sidebarMasterOpen=false; sidebarAdminOpen=false" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-bold transition {{ $userActive ? 'bg-blue-50 text-blue-800' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg {{ $userActive ? 'bg-blue-100' : 'bg-slate-100' }}">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            </span>
                            <span class="flex-1">Manajemen User</span>
                            <svg class="h-4 w-4 transition-transform" :class="sidebarUserOpen ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                        </button>
                        <div x-cloak x-show="sidebarUserOpen" x-transition class="ml-5 mt-1 space-y-0.5 border-l border-blue-200 pl-3">
                            @foreach ($userItems as [$url,$label,$match])
                                <a href="{{ $url }}" class="block rounded-lg px-3 py-2 text-[13px] font-bold transition {{ str_starts_with($current, $match) ? 'bg-blue-100 text-blue-800' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-950' }}">{{ $label }}</a>
                            @endforeach
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="button" x-init="sidebarMasterOpen={{ $masterActive ? 'true' : 'false' }}" @click="sidebarMasterOpen=!sidebarMasterOpen; sidebarUserOpen=false; sidebarTestOpen=false; sidebarOpsOpen=false; sidebarFinanceOpen=false; sidebarAdminOpen=false" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-bold transition {{ $masterActive ? 'bg-blue-50 text-blue-800' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg {{ $masterActive ? 'bg-blue-100' : 'bg-slate-100' }}">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 5h16v5H4z M4 14h16v5H4z"/></svg>
                            </span>
                            <span class="flex-1">Master Data</span>
                            <svg class="h-4 w-4 transition-transform" :class="sidebarMasterOpen ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                        </button>
                        <div x-cloak x-show="sidebarMasterOpen" x-transition class="ml-5 mt-1 space-y-0.5 border-l border-blue-200 pl-3">
                            @foreach ($masterItems as [$url,$label,$match])
                                @php($childActive = str_starts_with($current, $match))
                                <a href="{{ $url }}" class="block rounded-lg px-3 py-2 text-[13px] font-bold transition {{ $childActive ? 'bg-blue-100 text-blue-800' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-950' }}">{{ $label }}</a>
                            @endforeach
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="button" x-init="sidebarAdminOpen={{ $adminPortalActive ? 'true' : 'false' }}" @click="sidebarAdminOpen=!sidebarAdminOpen; sidebarUserOpen=false; sidebarTestOpen=false; sidebarOpsOpen=false; sidebarFinanceOpen=false; sidebarMasterOpen=false" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-bold transition {{ $adminPortalActive ? 'bg-sky-50 text-sky-800' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg {{ $adminPortalActive ? 'bg-sky-100' : 'bg-slate-100' }}">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h10M4 18h16"/><path d="M17 10l3 2-3 2"/></svg>
                            </span>
                            <span class="flex-1">Administrasi Sistem</span>
                            <svg class="h-4 w-4 transition-transform" :class="sidebarAdminOpen ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                        </button>
                        <div x-cloak x-show="sidebarAdminOpen" x-transition class="ml-5 mt-1 space-y-0.5 border-l border-sky-200 pl-3">
                            @foreach ($adminPortalItems as [$url,$label,$match])
                                <a href="{{ $url }}" class="block rounded-lg px-3 py-2 text-[13px] font-bold transition {{ str_starts_with($current, $match) ? 'bg-sky-100 text-sky-800' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-950' }}">{{ $label }}</a>
                            @endforeach
                        </div>
                    </div>
                </nav>
            </aside>@endunless

            @unless($isPrintPage)<div x-cloak x-show="mobileMenuOpen" x-transition.opacity class="fixed inset-0 z-[70] bg-slate-950/45 backdrop-blur-sm lg:hidden" @click="mobileMenuOpen=false"></div>@endunless

            <main class="flex min-w-0 flex-1 flex-col overflow-hidden">
            @unless($isPrintPage)
            <div class="shrink-0 border-b border-slate-200 bg-white px-4 py-3 lg:px-8">
                <div class="mx-auto max-w-[1500px]"><h1 class="text-lg font-black text-slate-950">@yield('page_title', 'Dashboard Admin')</h1></div>
            </div>
            @endunless
            <section class="portal-page-content flex-1 overflow-y-auto p-4 lg:p-6">
                <div class="mx-auto max-w-[1500px]">
                    @unless($isPrintPage)<x-portal-flash />@endunless
                    @yield('content')
                    <x-portal-footer />
                </div>
            </section>
            </main>
        </div>
    </div>
    @if(!$isPrintPage && !request()->routeIs('admin.whatsapp-chat.*'))
    <a href="{{ route('admin.whatsapp-chat.index') }}" x-data="whatsappUnreadBadge()" @whatsapp-unread.window="count = $event.detail.unread" class="wa-chat-shortcut" aria-label="Buka Chat WhatsApp" title="Chat WhatsApp">
        <img src="{{ asset('images/brand/whatsapp-glyph-white.svg') }}" width="30" height="30" alt="" aria-hidden="true">
        <span class="sr-only">WhatsApp</span><span x-cloak x-show="count > 0" x-text="count > 99 ? '99+' : count" class="wa-chat-shortcut-count" aria-label="Pesan belum dibaca"></span>
    </a>
    @endif
    @unless($isPrintPage)<x-global-loading />@endunless
    <x-delete-confirmation-modal />
    <x-auto-list-tools />
    <script>
        document.addEventListener('spmb-academic-year-preview', (event) => {
            const name = event.detail?.label;
            if (!name) return;
            document.querySelectorAll('[data-active-academic-year]').forEach((badge) => { badge.textContent = `TA ${name}`; });
        });
    </script>
    @stack('scripts')
<script>
function whatsappUnreadBadge() {
    return { count: 0, timer: null,
        init() { this.check(); this.timer = setInterval(() => { if(!document.hidden) this.check(); }, 8000); },
        destroy() { clearInterval(this.timer); },
        async check() { try { const r = await fetch(@json(route('admin.whatsapp-chat.unread')), {headers:{Accept:'application/json'},cache:'no-store'}); if(r.ok) this.count = (await r.json()).unread; } catch {} }
    };
}
</script>
</body>
</html>
