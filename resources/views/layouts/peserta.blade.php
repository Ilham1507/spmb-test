<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard Peserta') - {{ $siteSettings['portal_name'] }} {{ $siteSettings['school_short_name'] }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@500;600;700;800&family=Plus+Jakarta+Sans:opsz,wght@6..72,500;6..72,600;6..72,700;6..72,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
    </style>
    @stack('styles')
</head>
<body data-private-page x-data="{ mobileMenuOpen: false, sidebarMini: false }" class="portal-role-peserta portal-unified portal-density flex h-full flex-col overflow-hidden text-slate-800">
    @php
        $pendaftar = auth()->user()?->pendaftar;
        $cbtSessionOpen = $pendaftar ? \App\Models\CbtAccessSession::where('applicant_id', $pendaftar->id)
            ->where('status', 'open')
            ->whereNull('closed_at')
            ->where('expires_at', '>=', now())
            ->exists() : false;
        $isCbtExam = request()->routeIs('peserta.cbt') && $cbtSessionOpen;
        $isPrintPage = request()->routeIs('peserta.cetak', 'peserta.pdf', 'admin.pendaftar.cetak', 'admin.pendaftar.pdf', 'panitia.pendaftar.cetak', 'panitia.pendaftar.pdf');
        $participantPageTours = [
            'peserta.pembayaran' => ['target' => '#payment-digital-option', 'title' => 'Pilih cara pembayaran', 'text' => 'Gunakan Transfer digital untuk virtual account bank, QRIS, dan e-wallet.'],
            'peserta.biodata' => ['target' => '#nama_lengkap', 'title' => 'Mulai dari biodata', 'text' => 'Isi data pada kolom ini, lalu lanjutkan sesuai tombol simpan di halaman.'],
            'peserta.alamat' => ['target' => '#alamat', 'title' => 'Isi alamat domisili', 'text' => 'Mulai dari alamat lengkap, kemudian lengkapi detail wilayah.'],
            'peserta.ayah' => ['target' => '#nama', 'title' => 'Isi data ayah', 'text' => 'Lengkapi data orang tua sebelum melanjutkan ke tahap berikutnya.'],
            'peserta.ibu' => ['target' => '#nama', 'title' => 'Isi data ibu', 'text' => 'Lengkapi data orang tua sebelum melanjutkan ke tahap berikutnya.'],
            'peserta.wali' => ['target' => '#nama', 'title' => 'Data wali bersifat opsional', 'text' => 'Isi bagian ini hanya bila wali berbeda dari orang tua.'],
            'peserta.sekolah' => ['target' => '#search_sekolah', 'title' => 'Cari sekolah asal', 'text' => 'Ketik nama sekolah, NPSN, atau kecamatan, lalu pilih hasil yang sesuai.'],
            'peserta.jurusan' => ['target' => '#jurusan-pilihan-utama', 'title' => 'Pilih jurusan', 'text' => 'Tekan pilihan jurusan, lalu tentukan jurusan yang diminati.'],
            'peserta.kontak' => ['target' => 'form input', 'title' => 'Pastikan kontak aktif', 'text' => 'Nomor WhatsApp digunakan untuk masuk. Isi dan verifikasi email untuk melanjutkan proses pendaftaran.'],
            'peserta.dokumen' => ['target' => 'input[type=file]', 'title' => 'Unggah dokumen', 'text' => 'Tekan di sini untuk memilih berkas yang diminta.'],
            'peserta.review' => ['target' => 'form button[type=submit]', 'title' => 'Periksa sebelum mengirim', 'text' => 'Pastikan data sudah benar, lalu kirim pendaftaran.'],
        ];
        $participantTourRoute = session('participant_tour');
        $participantPageTour = $participantTourRoute === request()->route()?->getName()
            ? ($participantPageTours[$participantTourRoute] ?? null)
            : null;
    @endphp

    @unless($isPrintPage)
    <x-portal-page-header
        :title="trim($__env->yieldContent('page_title', 'Dashboard'))"
        :description="trim($__env->yieldContent('page_description', 'Pantau proses pendaftaran.'))"
        accent="teal"
        :show-menu="!$isCbtExam"
        :badge="$isCbtExam ? 'Mode Ujian - Navigasi Terkunci' : (request()->routeIs('peserta.formulir') && $pendaftar?->registration_number ? 'No. '.$pendaftar->registration_number : null)"
        nav-only
    />
    @endunless

    <div class="flex min-h-0 flex-1 overflow-hidden">
    <!-- Sidebar -->
    @unless($isCbtExam || $isPrintPage)
    <aside
        id="participant-sidebar-shell"
        class="sidebar-shell participant-sidebar fixed top-0 bottom-0 left-0 z-[70] flex w-[288px] shrink-0 flex-col border-r border-slate-300 bg-white shadow-2xl shadow-slate-950/20 transition-all duration-200 md:static md:z-auto md:translate-x-0"
        :class="{
            'translate-x-0': mobileMenuOpen,
            '-translate-x-full md:translate-x-0': !mobileMenuOpen,
            'is-mini md:w-[104px]': sidebarMini,
            'md:w-[288px]': !sidebarMini
        }"
    >
        <x-portal-sidebar-brand title="SPMB ONLINE" accent="teal" storage-key="spmb-sidebar-mini" />
        
        <nav id="participant-sidebar-navigation" class="sidebar-nav flex-1 py-6 flex flex-col gap-1 overflow-y-auto overflow-x-hidden">
            @php $route = Route::currentRouteName(); @endphp

            <a href="{{ route('peserta.dashboard') }}"
               class="sidebar-link {{ $route === 'peserta.dashboard' ? 'is-active' : '' }}"
               data-tooltip="Dashboard"
               :class="sidebarMini ? 'is-mini' : ''">
                <span class="sidebar-icon">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13h8V3H3v10zm10 8h8V11h-8v10zM3 21h8v-6H3v6zm10-12h8V3h-8v6z"/></svg>
                </span>
                <span class="sidebar-text">Dashboard</span>
            </a>
            
            <div class="sidebar-divider mt-6 mb-2 px-8" :class="sidebarMini ? 'is-mini' : ''">
                <hr class="border-slate-100">
            </div>

            @if($pendaftar)
                <a href="{{ route('peserta.pembayaran') }}"
                   class="sidebar-link {{ $route === 'peserta.pembayaran' ? 'is-active' : '' }}"
                   data-tooltip="{{ in_array($pendaftar->registration_status, ['accepted', 're_registered']) ? 'Daftar Ulang & Keuangan' : 'Pembayaran Formulir' }}"
                   :class="sidebarMini ? 'is-mini' : ''">
                    <span class="sidebar-icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </span>
                    <span class="sidebar-text">{{ in_array($pendaftar->registration_status, ['accepted', 're_registered']) ? 'Daftar Ulang & Keuangan' : 'Pembayaran Formulir' }}</span>
                </a>

                @if(! $pendaftar->major_choice_1)
                    <a href="{{ route('peserta.biaya-jurusan') }}"
                       class="sidebar-link {{ $route === 'peserta.biaya-jurusan' ? 'is-active' : '' }}"
                       data-tooltip="Rincian Biaya Jurusan"
                       :class="sidebarMini ? 'is-mini' : ''">
                        <span class="sidebar-icon">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        <span class="sidebar-text">Rincian Biaya Jurusan</span>
                    </a>
                @endif

                <a href="{{ route('peserta.biodata') }}"
                   class="sidebar-link {{ in_array($route, ['peserta.biodata', 'peserta.alamat', 'peserta.ayah', 'peserta.ibu', 'peserta.wali', 'peserta.sekolah', 'peserta.jurusan', 'peserta.kontak', 'peserta.review'], true) ? 'is-active' : '' }}"
                   data-tooltip="Formulir Data"
                   :class="sidebarMini ? 'is-mini' : ''">
                    <span class="sidebar-icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
                    </span>
                    <span class="sidebar-text">Formulir Data</span>
                </a>

                <a href="{{ route('peserta.dokumen') }}"
                   class="sidebar-link {{ $route === 'peserta.dokumen' ? 'is-active' : '' }}"
                   data-tooltip="Dokumen"
                   :class="sidebarMini ? 'is-mini' : ''">
                    <span class="sidebar-icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zm0 0v6h6M8 13h8M8 17h5"/></svg>
                    </span>
                    <span class="sidebar-text">Dokumen</span>
                </a>

                @if($cbtSessionOpen)
                    <a href="{{ route('peserta.cbt') }}"
                       class="sidebar-link {{ $route === 'peserta.cbt' ? 'is-active' : '' }}"
                       data-tooltip="Tes CBT"
                       :class="sidebarMini ? 'is-mini' : ''">
                        <span class="sidebar-icon">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5h6m-7 4h8M7 3h10a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z"/></svg>
                        </span>
                        <span class="sidebar-text">Tes CBT</span>
                    </a>
                @endif

                <a href="{{ route('peserta.hasil-tes.index') }}"
                   class="sidebar-link {{ $route === 'peserta.hasil-tes.index' ? 'is-active' : '' }}"
                   data-tooltip="Tes SPMB"
                   :class="sidebarMini ? 'is-mini' : ''">
                    <span class="sidebar-icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3h14v18H5zM8 7h8M8 11h8M8 15h5"/></svg>
                    </span>
                    <span class="sidebar-text">Tes SPMB</span>
                </a>

                <div class="sidebar-divider mt-6 mb-2 px-8" :class="sidebarMini ? 'is-mini' : ''">
                    <hr class="border-slate-100">
                </div>
            @endif

        </nav>

    </aside>

    <div x-cloak x-show="mobileMenuOpen" x-transition.opacity class="fixed inset-0 z-[60] bg-slate-950/45 backdrop-blur-sm md:hidden" @click="mobileMenuOpen = false"></div>
    @endunless

    <!-- Main Content Area -->
    <main class="flex-1 min-w-0 flex flex-col overflow-hidden">
        @unless($isPrintPage)
        <x-portal-page-header
            :title="trim($__env->yieldContent('page_title', 'Dashboard'))"
            :description="trim($__env->yieldContent('page_description', 'Pantau proses pendaftaran.'))"
            accent="teal"
            :badge="$isCbtExam ? 'Mode Ujian - Navigasi Terkunci' : (request()->routeIs('peserta.formulir') && $pendaftar?->registration_number ? 'No. '.$pendaftar->registration_number : null)"
            heading-only
        />
        @endunless
        @unless($isPrintPage)<div class="px-4 pt-3 md:px-6"><x-portal-flash /></div>@endunless

        <!-- Page Content -->
        <section class="portal-page-content flex-1 overflow-y-auto p-4 pb-28 md:p-8">
            @yield('content')
            <x-portal-footer />
        </section>
        @if($participantPageTour && !$isPrintPage)
            <div id="participant-page-tour" hidden data-target="{{ $participantPageTour['target'] }}">
                <div class="participant-page-tour-backdrop fixed inset-0 z-[125] bg-slate-950/65 backdrop-blur-[1px]"></div>
                <aside class="participant-page-tour-tip fixed z-[132] w-[calc(100%-2rem)] max-w-sm rounded-2xl bg-white p-4 shadow-2xl">
                    <p class="text-sm font-black text-slate-950">{{ $participantPageTour['title'] }}</p>
                    <p class="mt-1 text-xs font-semibold leading-relaxed text-slate-600">{{ $participantPageTour['text'] }}</p>
                    <button type="button" data-page-tour-close class="mt-3 text-xs font-black text-teal-700 underline underline-offset-4">Mengerti</button>
                </aside>
            </div>
        @endif
    </main>
    </div>
    <script>
        window.smpbFormFields = @json(\App\Support\FormFieldCatalog::enabled());
        window.smpbFormRequired = @json(\App\Support\FormFieldCatalog::required());
        document.addEventListener('DOMContentLoaded', function () {
            const path = window.location.pathname;
            const returnTo = new URLSearchParams(window.location.search).get('return_to');
            if (returnTo === 'review') {
                const form = Array.from(document.querySelectorAll('main form')).find((candidate) => (candidate.method || 'get').toLowerCase() === 'post');
                if (form && !form.querySelector('input[name="return_to"]')) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'return_to';
                    input.value = 'review';
                    form.appendChild(input);
                }
            }
            const selectors = {};
            if (path.endsWith('/biodata')) Object.assign(selectors, {
                nama_peserta: '#nama_lengkap', nisn: '#nisn', nik: '#nik', no_kartu_keluarga: '#no_kk',
                jenis_kelamin: '[name="jenis_kelamin"]', tempat_lahir: '#tempat_lahir', tanggal_lahir: '#tanggal_lahir',
                agama: '[name="agama"]', no_handphone: '#no_hp',
            });
            if (path.endsWith('/alamat')) Object.assign(selectors, {
                alamat: '#alamat', rt: '#rt', rw: '#rw', kelurahan: '#kelurahan', kecamatan: '#kecamatan',
                kabupaten_kota: '#kota', provinsi: '#provinsi', kode_pos: '#kode_pos',
                jarak_ke_sekolah: '[name="jarak_ke_sekolah"]',
            });
            if (path.endsWith('/ayah')) Object.assign(selectors, {
                nama_ayah: '#nama', nik_ayah: '#nik', pekerjaan_ayah: '[name="pekerjaan"]',
                pendidikan_ayah: '[name="pendidikan"]', penghasilan_ayah: '[name="penghasilan"]', no_hp_ayah: '[name="no_hp"]',
            });
            if (path.endsWith('/ibu')) Object.assign(selectors, {
                nama_ibu: '#nama', nik_ibu: '#nik', pekerjaan_ibu: '[name="pekerjaan"]',
                pendidikan_ibu: '[name="pendidikan"]', penghasilan_ibu: '[name="penghasilan"]', no_hp_ibu: '[name="no_hp"]',
            });
            if (path.endsWith('/wali')) Object.assign(selectors, {
                nama_wali: '#nama', nik_wali: '#nik', pekerjaan_wali: '[name="pekerjaan"]',
                pendidikan_wali: '[name="pendidikan"]', penghasilan_wali: '[name="penghasilan"]', no_hp_wali: '[name="no_hp"]',
            });
            if (path.endsWith('/sekolah-asal')) Object.assign(selectors, {
                asal_sekolah: '#search_sekolah, #manual_nama_sekolah',
                npsn: '#manual_npsn', alamat_sekolah: '#manual_alamat_sekolah', tahun_lulus: '#tahun_lulus',
            });
            if (path.endsWith('/jurusan')) Object.assign(selectors, { jurusan: '[name^="jurusan_id_"]' });
            if (path.endsWith('/kontak')) Object.assign(selectors, { email: '#email' });
            Object.entries(selectors).forEach(([key, selector]) => {
                document.querySelectorAll(selector).forEach((field) => {
                    const active = window.smpbFormFields.includes(key);
                    field.required = active && window.smpbFormRequired.includes(key);
                    if (active) return;
                    const wrapper = field.closest('div');
                    if (wrapper) wrapper.style.display = 'none';
                });
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const formPaths = ['/peserta/biodata', '/peserta/alamat', '/peserta/ayah', '/peserta/ibu', '/peserta/wali', '/peserta/sekolah-asal', '/peserta/jurusan', '/peserta/kontak', '/peserta/dokumen'];
            if (!formPaths.includes(window.location.pathname)) return;

            const form = Array.from(document.querySelectorAll('main form')).find(candidate => (candidate.method || 'get').toLowerCase() === 'post');
            if (!form) return;
            form.noValidate = true;

            const fieldLabel = function (field) {
                const explicit = field.labels && field.labels[0] ? field.labels[0].textContent : '';
                const nearby = field.closest('.relative, div')?.querySelector('label')?.textContent || '';
                return (explicit || nearby || field.getAttribute('aria-label') || field.name || 'Isian').replace(/\*/g, '').trim();
            };
            const fieldTarget = function (field) {
                return field.type === 'hidden'
                    ? field.closest('.relative, div')?.querySelector('button[type="button"]') || field
                    : field;
            };
            const clearFieldState = function (field) {
                const target = fieldTarget(field);
                target?.classList.remove('border-rose-500', 'ring-4', 'ring-rose-100');
                target?.removeAttribute('aria-invalid');
            };
            const showMessage = function (fields, onContinue) {
                document.getElementById('participant-form-validation')?.remove();
                const names = fields.slice(0, 4).map(fieldLabel).filter(Boolean);
                const more = fields.length > names.length ? `<li>dan ${fields.length - names.length} isian lainnya</li>` : '';
                const alert = document.createElement('div');
                alert.id = 'participant-form-validation';
                alert.className = 'fixed inset-0 z-[220] flex items-end justify-center bg-slate-950/45 p-4 backdrop-blur-sm sm:items-center';
                alert.innerHTML = `<section role="alertdialog" aria-modal="true" aria-labelledby="participant-form-validation-title" class="w-full max-w-md rounded-3xl bg-white p-5 shadow-2xl"><div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-rose-100 text-xl font-black text-rose-700">!</div><h2 id="participant-form-validation-title" class="mt-4 text-xl font-black text-slate-950">Masih ada data yang belum diisi</h2><p class="mt-2 text-sm font-medium leading-relaxed text-slate-600">Lengkapi isian wajib berikut, lalu tekan Simpan & Lanjut lagi.</p><ul class="mt-4 list-disc space-y-1 pl-5 text-sm font-bold text-slate-700">${names.map(name => `<li>${name}</li>`).join('')}${more}</ul><button type="button" class="mt-5 w-full rounded-2xl bg-teal-700 px-4 py-3 text-sm font-black text-white">Lihat isian pertama</button></section>`;
                const close = function () { alert.remove(); };
                alert.addEventListener('click', event => { if (event.target === alert) close(); });
                alert.querySelector('button').addEventListener('click', function () {
                    close();
                    onContinue?.();
                });
                document.body.appendChild(alert);
            };

            form.addEventListener('submit', function (event) {
                const seenRadioNames = new Set();
                const missing = [];
                form.querySelectorAll('[required]').forEach(function (field) {
                    if (field.disabled || (field.type !== 'hidden' && field.offsetParent === null)) return;
                    if (field.type === 'radio') {
                        if (seenRadioNames.has(field.name)) return;
                        seenRadioNames.add(field.name);
                        if (!form.querySelector(`input[type="radio"][name="${CSS.escape(field.name)}"]:checked`)) missing.push(field);
                        return;
                    }
                    if (!String(field.value || '').trim()) missing.push(field);
                });
                if (!missing.length) return;

                event.preventDefault();
                missing.forEach(function (field) {
                    const target = fieldTarget(field);
                    target?.classList.add('border-rose-500', 'ring-4', 'ring-rose-100');
                    target?.setAttribute('aria-invalid', 'true');
                    field.addEventListener('input', () => clearFieldState(field), { once: true });
                    field.addEventListener('change', () => clearFieldState(field), { once: true });
                });
                const first = fieldTarget(missing[0]);
                first?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                showMessage(missing, () => {
                    window.setTimeout(() => first?.focus({ preventScroll: true }), 150);
                });
            });
            form.addEventListener('form-select-changed', function (event) {
                const field = form.querySelector(`[name="${CSS.escape(event.detail?.name || '')}"]`);
                if (field) clearFieldState(field);
            });
        });
    </script>
    @unless($isPrintPage)<x-global-loading />@endunless
    <script>
        window.positionParticipantTourTip = function (tip, target) {
            if (!tip || !target) return;
            const viewportPadding = 16;
            const isCompactScreen = (window.visualViewport?.width || window.innerWidth) < 768
                || window.matchMedia?.('(pointer: coarse)').matches;
            if (isCompactScreen) {
                // Mulai dari bagian atas layar. Jika kartu tidak menabrak target,
                // posisi ini dipakai. Jika menabrak, pindahkan ke bawah. Dengan
                // begitu target selalu terlihat, termasuk saat halaman berada
                // paling atas dan tidak bisa digulir lagi.
                tip.style.left = `${viewportPadding}px`;
                tip.style.right = `${viewportPadding}px`;
                tip.style.width = 'auto';
                tip.style.top = `${viewportPadding}px`;
                tip.style.bottom = 'auto';
                tip.style.maxHeight = 'min(38dvh, 300px)';
                tip.style.overflowY = 'hidden';
                const topCard = tip.getBoundingClientRect();
                const focus = target.getBoundingClientRect();
                const overlapsTopCard = focus.top < topCard.bottom + 16 && focus.bottom > topCard.top - 16;

                if (overlapsTopCard) {
                    tip.style.top = 'auto';
                    tip.style.bottom = `${viewportPadding}px`;
                }
                tip.dataset.placement = 'mobile';
                return;
            }
            const gap = 28;
            const targetRect = target.getBoundingClientRect();
            const viewportWidth = window.visualViewport?.width || window.innerWidth;
            const viewportHeight = window.visualViewport?.height || window.innerHeight;
            const tipWidth = Math.min(400, viewportWidth - (viewportPadding * 2));

            tip.style.width = `${tipWidth}px`;
            tip.style.maxHeight = 'calc(100dvh - 32px)';
            tip.style.overflowY = 'hidden';
            tip.style.right = 'auto';
            tip.style.bottom = 'auto';

            // Letakkan kartu sedekat mungkin dengan bagian yang disorot. Garis
            // pendek pada kartu cukup untuk menunjukkan hubungan keduanya.
            const cardHeight = Math.min(tip.getBoundingClientRect().height || 220, viewportHeight - (viewportPadding * 2));
            const clampTop = (top) => Math.max(viewportPadding, Math.min(top, viewportHeight - cardHeight - viewportPadding));
            const canPlaceRight = targetRect.right + gap + tipWidth <= viewportWidth - viewportPadding;
            const canPlaceLeft = targetRect.left - gap - tipWidth >= viewportPadding;

            if (canPlaceRight) {
                tip.style.left = `${Math.round(targetRect.right + gap)}px`;
                tip.style.top = `${Math.round(clampTop(targetRect.top))}px`;
                tip.dataset.placement = 'right';
            } else if (canPlaceLeft) {
                tip.style.left = `${Math.round(targetRect.left - gap - tipWidth)}px`;
                tip.style.top = `${Math.round(clampTop(targetRect.top))}px`;
                tip.dataset.placement = 'left';
            } else {
                const canPlaceBelow = targetRect.bottom + gap + cardHeight <= viewportHeight - viewportPadding;
                tip.style.left = `${Math.round(Math.max(viewportPadding, Math.min(targetRect.left, viewportWidth - tipWidth - viewportPadding)))}px`;
                tip.style.top = `${Math.round(canPlaceBelow ? targetRect.bottom + gap : clampTop(targetRect.top - cardHeight - gap))}px`;
                tip.dataset.placement = canPlaceBelow ? 'bottom' : 'top';
            }
        };
        window.positionParticipantTourBackdrop = function (panels, target) {
            if (!panels || !target) return;
            const rect = target.getBoundingClientRect();
            const viewportWidth = window.visualViewport?.width || window.innerWidth;
            const viewportHeight = window.visualViewport?.height || window.innerHeight;
            const gap = 9;
            const top = Math.max(0, rect.top - gap);
            const bottom = Math.min(viewportHeight, rect.bottom + gap);
            const left = Math.max(0, rect.left - gap);
            const right = Math.min(viewportWidth, rect.right + gap);
            const set = (panel, styles) => {
                if (!panel) return;
                Object.assign(panel.style, styles);
            };
            set(panels.top, { left: '0px', top: '0px', width: `${viewportWidth}px`, height: `${top}px` });
            set(panels.bottom, { left: '0px', top: `${bottom}px`, width: `${viewportWidth}px`, height: `${Math.max(0, viewportHeight - bottom)}px` });
            set(panels.left, { left: '0px', top: `${top}px`, width: `${left}px`, height: `${Math.max(0, bottom - top)}px` });
            set(panels.right, { left: `${right}px`, top: `${top}px`, width: `${Math.max(0, viewportWidth - right)}px`, height: `${Math.max(0, bottom - top)}px` });
        };
        window.positionParticipantTourFocus = function (focus, target) {
            if (!focus || !target) return;
            const rect = target.getBoundingClientRect();
            const gap = 7;
            focus.style.left = `${Math.max(0, rect.left - gap)}px`;
            focus.style.top = `${Math.max(0, rect.top - gap)}px`;
            focus.style.width = `${Math.max(0, rect.width + (gap * 2))}px`;
            focus.style.height = `${Math.max(0, rect.height + (gap * 2))}px`;
            focus.style.opacity = '1';
        };
        document.addEventListener('DOMContentLoaded', function () {
            const tour = document.getElementById('participant-page-tour');
            if (!tour) return;
            const tourKey = `spmb-participant-tour:${window.location.pathname}`;
            if (window.localStorage.getItem(tourKey) === 'seen') {
                tour.remove();
                return;
            }
            const target = Array.from(document.querySelectorAll(tour.dataset.target)).find((candidate) => {
                const rect = candidate.getBoundingClientRect();
                return candidate.closest('.portal-page-content') && rect.width > 0 && rect.height > 0;
            });
            if (!target) return;
            const closeTour = function () {
                window.localStorage.setItem(tourKey, 'seen');
                target.classList.remove('participant-page-tour-target');
                tour.remove();
            };
            target.classList.add('participant-page-tour-target');
            const tip = tour.querySelector('.participant-page-tour-tip');
            const placeTip = () => window.positionParticipantTourTip(tip, target);
            const targetRect = target.getBoundingClientRect();
            const outsideViewport = targetRect.top < 16 || targetRect.bottom > window.innerHeight - 16;
            if (outsideViewport) {
                target.scrollIntoView({ behavior: 'auto', block: 'center', inline: 'nearest' });
            }
            requestAnimationFrame(() => {
                tour.hidden = false;
                placeTip();
                requestAnimationFrame(() => {
                    placeTip();
                });
            });
            window.addEventListener('resize', placeTip);
            document.querySelector('.portal-page-content')?.addEventListener('scroll', placeTip, { passive: true });
            tour.querySelector('[data-page-tour-close]').addEventListener('click', closeTour);
            target.addEventListener('click', closeTour, { once: true });
        });
    </script>
    @stack('scripts')
</body>
</html>
