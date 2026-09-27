@extends('layouts.peserta')

@section('title', 'Dashboard Peserta')
@section('page_title', 'Dashboard Pendaftaran')

@section('content')
@php
    $status = $pendaftar?->registration_status ?? 'draft';
    $studentName = $pendaftar?->biodata?->full_name ?: auth()->user()?->name;
    $registrationFeePaid = $registrationFeePaid ?? false;
    $registrationFeePending = $registrationFeePending ?? false;
    $jadwalSpmb = $jadwalSpmb ?? collect();
    $jadwalTes = $jadwalTes ?? collect();
    $jadwalTesSessions = $jadwalSpmb->filter(fn ($jadwal) => str_contains(strtolower($jadwal->kegiatan), 'tes spmb'))->values();
    $firstTestDate = $jadwalTesSessions->first()?->tanggal_mulai
        ? \Carbon\Carbon::parse($jadwalTesSessions->first()->tanggal_mulai)
        : ($jadwalTes->first()?->test_date ? \Carbon\Carbon::parse($jadwalTes->first()->test_date) : null);
    $mandatoryKeys = ['biodata', 'alamat', 'ayah', 'ibu', 'sekolah', 'jurusan', 'kontak', 'dokumen'];
    $completedSteps = collect($mandatoryKeys)->filter(fn ($key) => $sections[$key] ?? false)->count();
    $allMandatoryDone = $completedSteps === count($mandatoryKeys);
    $labels = [
        'biodata' => ['label' => 'Biodata diri', 'route' => 'peserta.biodata'],
        'alamat' => ['label' => 'Alamat domisili', 'route' => 'peserta.alamat'],
        'ayah' => ['label' => 'Data ayah', 'route' => 'peserta.ayah'],
        'ibu' => ['label' => 'Data ibu', 'route' => 'peserta.ibu'],
        'sekolah' => ['label' => 'Sekolah asal', 'route' => 'peserta.sekolah'],
        'jurusan' => ['label' => 'Pilihan jurusan', 'route' => 'peserta.jurusan'],
        'kontak' => ['label' => 'Data kontak', 'route' => 'peserta.kontak'],
        'dokumen' => ['label' => 'Dokumen', 'route' => 'peserta.dokumen'],
    ];
    $nextRoute = 'peserta.review';
    $nextTarget = 'Review & kirim pendaftaran';
    foreach ($mandatoryKeys as $key) {
        if (!($sections[$key] ?? false)) {
            $nextRoute = $labels[$key]['route'];
            $nextTarget = 'Isi ' . $labels[$key]['label'];
            break;
        }
    }
    if (!$registrationFeePaid) {
        $nextRoute = 'peserta.pembayaran';
        $nextTarget = $registrationFeePending ? 'Lihat status pembayaran' : 'Bayar formulir';
    }
    if ($status === 'submitted') {
        $nextRoute = $pendaftar?->verification_notes ? 'peserta.biodata' : 'peserta.formulir';
        $nextTarget = $pendaftar?->verification_notes ? 'Perbaiki formulir' : 'Lihat formulir pendaftaran';
    } elseif ($status === 'verified') {
        $nextRoute = 'peserta.hasil-tes.index';
        $nextTarget = 'Lihat jadwal Tes SPMB';
    } elseif ($status === 'accepted') {
        $nextRoute = 'peserta.pembayaran';
        $nextTarget = 'Lanjutkan daftar ulang';
    } elseif ($status === 're_registered') {
        $nextRoute = 'peserta.formulir';
        $nextTarget = 'Lihat formulir pendaftaran';
    }
    $tutorialStage = !$registrationFeePaid
        ? 'pembayaran'
        : ($allMandatoryDone ? 'review' : collect($mandatoryKeys)->first(fn ($key) => !($sections[$key] ?? false), 'review'));
    $statusTitle = match ($status) {
        'submitted' => $pendaftar?->verification_notes ? 'Ada catatan dari panitia' : 'Menunggu verifikasi',
        'verified' => 'Berkas terverifikasi',
        'accepted' => 'Selamat, kamu diterima',
        're_registered' => 'Daftar ulang selesai',
        'rejected' => 'Belum diterima',
        default => (!$registrationFeePaid ? ($registrationFeePending ? 'Pembayaran sedang dicek' : 'Selesaikan pembayaran formulir') : ($allMandatoryDone ? 'Siap kirim pendaftaran' : 'Lengkapi data pendaftaran')),
    };
    $statusText = match ($status) {
        'submitted' => $pendaftar?->verification_notes ? 'Buka catatan lalu perbarui data yang diminta.' : 'Panitia sedang memeriksa data dan dokumenmu.',
        'verified' => 'Pantau jadwal tes dari sini.',
        'accepted' => 'Lanjutkan administrasi daftar ulang melalui menu pembayaran.',
        're_registered' => 'Administrasi akhir sudah tercatat.',
        'rejected' => 'Hubungi panitia untuk informasi berikutnya.',
        default => (!$registrationFeePaid ? 'Pembayaran membuka formulir pendaftaran.' : ($allMandatoryDone ? 'Periksa sekali lagi sebelum mengirim.' : 'Satu langkah per satu, kamu tidak perlu mengisi semuanya sekarang.')),
    };
    $cbtSessionOpen = $pendaftar ? \App\Models\CbtAccessSession::where('applicant_id', $pendaftar->id)->where('status', 'open')->whereNull('closed_at')->where('expires_at', '>=', now())->latest()->first() : null;
    $isSubmitted = in_array($status, ['submitted', 'verified', 'accepted', 'rejected', 're_registered'], true);
    $isVerified = in_array($status, ['verified', 'accepted', 'rejected', 're_registered'], true);
    $isAnnounced = in_array($status, ['accepted', 'rejected', 're_registered'], true);
    $journey = [
        ['number' => '01', 'title' => 'Registrasi', 'caption' => 'Akun dibuat', 'state' => 'done'],
        ['number' => '02', 'title' => 'Pembayaran', 'caption' => $registrationFeePaid ? 'Tercatat' : ($registrationFeePending ? 'Sedang diperiksa' : 'Belum dibayar'), 'state' => $registrationFeePaid ? 'done' : 'active'],
        ['number' => '03', 'title' => 'Isi data', 'caption' => $allMandatoryDone ? 'Data lengkap' : $completedSteps.'/'.count($mandatoryKeys).' bagian', 'state' => !$registrationFeePaid ? 'locked' : ($allMandatoryDone ? 'done' : 'active')],
        ['number' => '04', 'title' => 'Verifikasi', 'caption' => $isVerified ? 'Terverifikasi' : ($isSubmitted ? 'Sedang diperiksa' : 'Menunggu kirim'), 'state' => !$isSubmitted ? 'locked' : ($isVerified ? 'done' : 'active')],
        ['number' => '05', 'title' => 'Tes SPMB', 'caption' => $isAnnounced ? 'Selesai' : ($isVerified ? 'Menunggu jadwal' : 'Belum dibuka'), 'state' => !$isVerified ? 'locked' : ($isAnnounced ? 'done' : 'active')],
        ['number' => '06', 'title' => 'Pengumuman', 'caption' => $isAnnounced ? ($status === 'rejected' ? 'Belum diterima' : 'Sudah tersedia') : 'Menunggu hasil', 'state' => $isAnnounced ? 'done' : 'locked'],
    ];
@endphp

<div x-data="{ tutorialOpen: false, tutorialForce: @js($forceTutorial ?? false), tutorialKey: 'spmb-action-tour-v5-{{ auth()->id() }}-{{ $pendaftar?->id }}-{{ $tutorialStage }}', init() { if (this.tutorialForce) { this.tutorialOpen = true; return } try { this.tutorialOpen = !localStorage.getItem(this.tutorialKey) } catch (e) { this.tutorialOpen = true } }, finishTutorial() { try { localStorage.setItem(this.tutorialKey, 'done') } catch (e) {} this.tutorialOpen = false } }" x-init="init()" class="participant-dashboard mx-auto max-w-5xl space-y-4">
    <section class="participant-summary rounded-3xl border p-5 md:p-7">
        <div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
            <div class="min-w-0">
                <p class="text-sm font-extrabold text-teal-700">Halo, {{ $studentName ?? 'Peserta Baru' }}</p>
                <h2 class="mt-1 text-2xl font-black tracking-tight text-slate-950 md:text-3xl">{{ $statusTitle }}</h2>
                <p class="mt-2 max-w-xl text-sm font-medium leading-relaxed text-slate-600">{{ $statusText }}</p>
                @if($pendaftar?->registration_number)
                    <p class="mt-3 inline-flex items-center rounded-xl bg-teal-50 px-3 py-2 text-xs font-black text-teal-800">No. pendaftaran: {{ $pendaftar->registration_number }}</p>
                @endif
                @if($pendaftar?->preferredTestSchedule && in_array($status, ['submitted', 'verified', 'accepted', 're_registered'], true))
                    @php $preferredTestDate = \Illuminate\Support\Carbon::parse($pendaftar->preferredTestSchedule->tanggal_mulai); @endphp
                    <a href="{{ route('peserta.hasil-tes.index') }}" class="mt-4 flex max-w-xl items-center gap-3 rounded-2xl border border-teal-200 bg-teal-50/80 px-4 py-3 text-left transition hover:border-teal-400 hover:bg-teal-50">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal-700 text-sm font-black text-white">✓</span>
                        <span class="min-w-0 flex-1"><span class="block text-[10px] font-black uppercase tracking-[.13em] text-teal-700">Tanggal Tes SPMB</span><b class="mt-0.5 block text-sm font-black text-slate-900">{{ $preferredTestDate->translatedFormat('d F Y, H:i') }} WIB</b><small class="mt-0.5 block text-xs font-semibold text-slate-600">Kampus E SMK Muhammadiyah 4 Cileungsi</small></span>
                        <span class="text-lg font-black text-teal-700">→</span>
                    </a>
                @endif
            </div>
            <div class="participant-progress-card shrink-0">
                <span>Progress pendaftaran</span>
                <strong>{{ $percent }}%</strong>
                <small>{{ $completedSteps }}/{{ count($mandatoryKeys) }} langkah selesai</small>
            </div>
        </div>
        @if($status !== 'rejected')
            <a id="participant-primary-action" href="{{ route($nextRoute) }}{{ $nextRoute === 'peserta.pembayaran' ? '?tour=1' : '' }}" @click="tutorialOpen && finishTutorial()" :class="tutorialOpen ? 'participant-tour-target relative z-[130]' : ''" class="participant-main-action mt-5 inline-flex w-full items-center justify-center gap-2 rounded-2xl px-5 py-3.5 text-sm font-black md:w-auto">
                {{ $nextTarget }} <span aria-hidden="true">→</span>
            </a>
        @endif
    </section>

    <section class="rounded-3xl border border-teal-100 bg-white p-4 shadow-sm md:p-5">
        <div class="flex items-end justify-between gap-4">
            <div><p class="text-xs font-black uppercase tracking-[0.14em] text-teal-700">Peta perjalanan</p><h3 class="mt-1 text-lg font-black text-slate-950">Proses pendaftaranmu</h3></div>
            <span class="hidden rounded-full bg-teal-50 px-3 py-1 text-xs font-black text-teal-700 sm:block">Sampai pengumuman</span>
        </div>
        <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
            @foreach($journey as $stage)
                <article @class([
                    'relative min-h-28 rounded-2xl border p-3 transition',
                    'border-teal-200 bg-teal-50' => $stage['state'] === 'done',
                    'border-teal-500 bg-white ring-2 ring-teal-100' => $stage['state'] === 'active',
                    'border-slate-100 bg-slate-50 opacity-70' => $stage['state'] === 'locked',
                ])>
                    <div class="flex items-center justify-between gap-2">
                        <span @class([
                            'flex h-8 w-8 items-center justify-center rounded-xl text-xs font-black',
                            'bg-teal-700 text-white' => $stage['state'] === 'done',
                            'bg-teal-100 text-teal-800' => $stage['state'] === 'active',
                            'bg-slate-200 text-slate-500' => $stage['state'] === 'locked',
                        ])>{{ $stage['state'] === 'done' ? '✓' : $stage['number'] }}</span>
                        @if($stage['state'] === 'active')<span class="h-2 w-2 rounded-full bg-teal-500"></span>@endif
                    </div>
                    <p class="mt-4 text-sm font-black text-slate-900">{{ $stage['title'] }}</p>
                    <p class="mt-1 text-xs font-semibold leading-snug text-slate-500">{{ $stage['caption'] }}</p>
                </article>
            @endforeach
        </div>
        <p class="mt-4 text-xs font-medium text-slate-500">Ikuti tahap yang memiliki penanda hijau. Tahap berikutnya terbuka otomatis setelah proses sebelumnya selesai.</p>
    </section>

    @if($status === 'submitted' && $pendaftar?->verification_notes && $pendaftar?->correction_status !== 'resubmitted')
        <section class="rounded-3xl border border-amber-200 bg-amber-50 p-4 md:p-5">
            <p class="text-xs font-black uppercase tracking-wide text-amber-700">Catatan panitia</p>
            <p class="mt-1 text-sm font-semibold leading-relaxed text-amber-950">{{ $pendaftar->verification_notes }}</p>
            <a href="{{ route('peserta.biodata') }}" class="mt-3 inline-flex text-sm font-black text-amber-800 underline underline-offset-4">Perbarui data</a>
        </section>
    @endif

    <section class="participant-next-steps rounded-3xl border bg-white p-4 md:p-5">
        <div class="flex items-center justify-between gap-4">
            <div><p class="text-xs font-black uppercase tracking-wide text-teal-700">Akses cepat</p><h3 class="mt-1 text-lg font-black text-slate-950">Lanjutkan prosesmu</h3></div>
            <span class="rounded-full bg-teal-50 px-3 py-1 text-xs font-black text-teal-700">{{ $completedSteps }}/{{ count($mandatoryKeys) }}</span>
        </div>
        <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
            <a href="{{ route('peserta.pembayaran') }}" class="participant-shortcut"><span>01</span><div><b>Pembayaran</b><small>{{ $registrationFeePaid ? 'Sudah tercatat' : 'Formulir & rincian' }}</small></div><i>→</i></a>
            <a href="{{ route('peserta.biodata') }}" class="participant-shortcut"><span>02</span><div><b>Formulir data</b><small>Isi bertahap</small></div><i>→</i></a>
            <a href="{{ route('peserta.dokumen') }}" class="participant-shortcut"><span>03</span><div><b>Dokumen</b><small>Unggah berkas</small></div><i>→</i></a>
            <a href="{{ route('peserta.hasil-tes.index') }}" class="participant-shortcut"><span>04</span><div><b>Tes SPMB</b><small>Jadwal & hasil</small></div><i>→</i></a>
        </div>
    </section>

    @if($cbtSessionOpen)
        <section class="rounded-3xl border border-teal-200 bg-teal-50 p-4 md:flex md:items-center md:justify-between md:p-5">
            <div><p class="font-black text-teal-900">Tes CBT sedang dibuka</p><p class="mt-1 text-sm font-medium text-teal-800">Akses sampai {{ $cbtSessionOpen->expires_at?->timezone('Asia/Jakarta')->format('H:i') }} WIB.</p></div>
            <a href="{{ route('peserta.cbt') }}" class="participant-main-action mt-3 inline-flex rounded-xl px-4 py-2.5 text-sm font-black md:mt-0">Mulai CBT</a>
        </section>
    @endif

    @if(in_array($status, ['submitted', 'verified', 'accepted', 're_registered'], true))
        <section class="participant-status-row rounded-3xl border bg-white p-4 md:flex md:items-center md:justify-between md:p-5">
            <div><p class="text-sm font-black text-slate-900">Formulir pendaftaran</p><p class="mt-1 text-sm text-slate-500">Lihat atau simpan data yang sudah kamu kirim.</p></div>
            <div class="mt-3 flex gap-2 md:mt-0"><a href="{{ route('peserta.formulir') }}" class="rounded-xl bg-teal-50 px-4 py-2.5 text-sm font-black text-teal-800">Lihat</a><a href="{{ route('peserta.pdf') }}" class="rounded-xl border border-teal-200 px-4 py-2.5 text-sm font-black text-teal-800">PDF</a></div>
        </section>
    @endif

@if($status === 'draft' && (!$visitMatchCandidate || ($forceTutorial ?? false)))
    <div x-cloak x-show="tutorialOpen" x-transition.opacity class="participant-action-tour fixed inset-0 z-[125]" aria-live="polite">
        <div class="absolute inset-0 bg-slate-950/65 backdrop-blur-[1px]"></div>
        <div x-init="$nextTick(() => { window.positionParticipantTourTip?.($el, document.getElementById('participant-primary-action')); requestAnimationFrame(() => window.positionParticipantTourTip?.($el, document.getElementById('participant-primary-action'))); })" @resize.window="window.positionParticipantTourTip?.($el, document.getElementById('participant-primary-action'))" class="participant-page-tour-tip participant-tour-tooltip fixed z-[131] w-[calc(100%-2rem)] max-w-sm rounded-2xl bg-white p-4 shadow-2xl">
            <div class="flex items-start gap-3">
                <div class="participant-tour-pulse mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-lg">☝</div>
                <div>
                    <p class="text-sm font-black text-slate-950">Mulai dari sini</p>
                    <p class="mt-1 text-xs font-semibold leading-relaxed text-slate-600">Tekan <strong>{{ $nextTarget }}</strong> untuk melihat nominal dan menyelesaikan tahap pertama.</p>
                </div>
            </div>
            <button @click="finishTutorial()" class="mt-3 text-xs font-black text-teal-700 underline underline-offset-4">Lewati tutorial</button>
        </div>
    </div>
@endif
</div>

@if($visitMatchCandidate)
    @php
        $visitPhone = preg_replace('/^(\d{4})\d+(\d{3})$/', '$1••••$2', (string) $visitMatchCandidate->visitor_phone);
        $otpMode = (int) session('visit_otp_visit_id') === (int) $visitMatchCandidate->id;
    @endphp
    <div x-data="{ open: true, otpMode: @js($otpMode) }" x-cloak x-show="open" class="fixed inset-0 z-[120] flex items-center justify-center bg-slate-950/55 p-4 backdrop-blur-sm" role="dialog" aria-modal="true">
        <div @click.outside="" class="w-full max-w-lg overflow-hidden rounded-3xl bg-white shadow-2xl">
            <div class="h-2 bg-emerald-500"></div>
            <div class="p-5 sm:p-7">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-xl font-black text-emerald-700">✓</div>
                <h2 class="mt-4 text-2xl font-black text-slate-950">Kami menemukan data kunjunganmu</h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-600">Nama lengkap pada akun cocok dengan buku kunjungan sekolah. Pastikan informasi berikut memang milikmu.</p>

                <div class="mt-5 grid gap-3 rounded-2xl bg-slate-50 p-4 text-sm sm:grid-cols-2">
                    <div><p class="text-xs font-bold uppercase text-slate-400">Nama calon siswa</p><p class="mt-1 font-black text-slate-900">{{ $visitMatchCandidate->full_name }}</p></div>
                    <div><p class="text-xs font-bold uppercase text-slate-400">Sekolah asal</p><p class="mt-1 font-black text-slate-900">{{ $visitMatchCandidate->origin_school }}</p></div>
                    <div><p class="text-xs font-bold uppercase text-slate-400">WA saat kunjungan</p><p class="mt-1 font-black text-slate-900">{{ $visitPhone }}</p></div>
                    <div><p class="text-xs font-bold uppercase text-slate-400">Guru penerima</p><p class="mt-1 font-black text-emerald-700">{{ $visitMatchCandidate->penerima?->name ?? '-' }}</p></div>
                </div>

                <div x-show="!otpMode" class="mt-5 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <form method="POST" action="{{ route('peserta.kunjungan.dismiss', $visitMatchCandidate) }}">@csrf<button class="w-full rounded-2xl bg-slate-100 px-5 py-3 text-sm font-black text-slate-700">Bukan saya</button></form>
                    <form method="POST" action="{{ route('peserta.kunjungan.otp.send', $visitMatchCandidate) }}">@csrf<button class="w-full rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-emerald-200">Ya, ini kunjungan saya</button></form>
                </div>

                <div x-show="otpMode" class="mt-5">
                    <p class="mb-3 rounded-2xl bg-sky-50 p-3 text-sm font-semibold text-sky-800">Masukkan kode yang dikirim ke nomor akun <strong>{{ auth()->user()->phone }}</strong>. Setelah terhubung, nomor akun ini menjadi kontak utama.</p>
                    <form method="POST" action="{{ route('peserta.kunjungan.otp.verify', $visitMatchCandidate) }}" class="space-y-3">
                        @csrf
                        <input name="otp" inputmode="numeric" maxlength="6" required autofocus class="w-full rounded-2xl border-2 border-slate-200 px-4 py-3 text-center text-2xl font-black tracking-[0.35em] focus:border-emerald-500 focus:outline-none" placeholder="000000">
                        @error('otp')<p class="text-sm font-bold text-rose-600">{{ $message }}</p>@enderror
                        <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                            <button type="button" @click="otpMode=false" class="rounded-2xl bg-slate-100 px-5 py-3 text-sm font-black text-slate-700">Kembali</button>
                            <button class="rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-black text-white">Verifikasi & Hubungkan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endif
@endsection
