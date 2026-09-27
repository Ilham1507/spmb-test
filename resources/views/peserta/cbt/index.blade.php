@extends('layouts.peserta')

@section('title', 'Tes CBT')
@section('page_title', 'Tes CBT')

@section('content')
<div class="mx-auto max-w-5xl space-y-5">
    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm font-black text-sky-700">Petunjuk Ujian</p>
        <h2 class="mt-1 text-xl font-black text-slate-950">Fokus mengerjakan CBT</h2>
        <p class="mt-2 text-sm font-semibold text-slate-500">Baca setiap soal dengan tenang. Jangan pindah tab, kembali ke dashboard, atau menutup halaman. Jika halaman ditinggalkan, sesi dikunci dan panitia perlu membuka akses kembali.</p>
    </section>

    @if(!$pendaftar)
        <section class="rounded-3xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
            <h3 class="text-lg font-black text-amber-900">Akun belum terhubung ke data pendaftar</h3>
            <p class="mt-1 text-sm font-semibold text-amber-800">Silakan lengkapi pendaftaran dulu sebelum mengikuti tes CBT.</p>
        </section>
    @elseif(!$session && $lockedSession)
        <section class="rounded-3xl border border-rose-200 bg-rose-50 p-5 shadow-sm">
            <div class="flex items-start gap-3">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-rose-600 text-white">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path d="M12 9v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                </span>
                <div>
                    <h3 class="text-lg font-black text-rose-900">Sesi CBT terkunci</h3>
                    <p class="mt-1 text-sm font-semibold leading-relaxed text-rose-800">Jawaban yang sudah dipilih tetap tersimpan. Hubungi panitia di lokasi tes untuk membuka akses CBT kembali.</p>
                </div>
            </div>
        </section>
    @elseif(!$session)
        <section class="rounded-3xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
            <div class="flex items-start gap-3">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-amber-500 text-white">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path d="M12 9v2m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                </span>
                <div>
                    <h3 class="text-lg font-black text-amber-900">CBT belum dibuka</h3>
                    <p class="mt-1 text-sm font-semibold leading-relaxed text-amber-800">
                        Datang ke lokasi tes sesuai jadwal, lalu minta panitia membuka akses CBT untuk akun kamu.
                    </p>
                </div>
            </div>
        </section>
    @elseif($questions->isEmpty())
        <section class="rounded-3xl border border-slate-200 bg-white p-5 text-center shadow-sm">
            <h3 class="text-lg font-black text-slate-950">Soal CBT belum tersedia</h3>
            <p class="mt-1 text-sm font-semibold text-slate-500">Panitia perlu menambahkan atau mengaktifkan soal terlebih dahulu.</p>
        </section>
    @else
        <section class="rounded-3xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h3 class="text-lg font-black text-emerald-900">Akses CBT aktif</h3>
                    <p class="mt-1 text-sm font-semibold text-emerald-800">Berakhir pukul {{ $session->expires_at?->timezone('Asia/Jakarta')->format('H:i') }} WIB · Sisa waktu: <span id="cbt-countdown" class="font-black">--:--</span>. Jawaban otomatis dikirim saat waktu habis.</p>
                    @if($answers->filter()->isNotEmpty())
                        <p class="mt-2 text-xs font-black text-emerald-700">{{ $answers->filter()->count() }} jawaban sebelumnya tersimpan. Lanjutkan dari soal yang belum dijawab.</p>
                    @endif
                </div>
                <span class="rounded-full bg-white px-3 py-1 text-xs font-black text-emerald-700">{{ $questions->count() }} soal</span>
            </div>
        </section>

        <div id="cbt-network-notice" class="hidden rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-900">
            Koneksi sedang terputus. Tenang, CBT tidak diakhiri. Sambungkan kembali internet atau muat ulang halaman untuk melanjutkan.
        </div>

        <form id="cbt-answer-form" method="POST" action="{{ route('peserta.cbt.submit') }}" class="space-y-4">
            @csrf
            <input id="cbt-timed-out" type="hidden" name="timed_out" value="0">
            @foreach($questions as $question)
                <div data-cbt-question="{{ $question->id }}" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition">
                    <div class="flex gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl bg-sky-100 text-sm font-black text-sky-700">{{ $loop->iteration }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="font-bold leading-relaxed text-slate-950">{{ $question->question }}</p>
                            <div class="mt-4 grid gap-2">
                                @foreach(['A' => $question->option_a, 'B' => $question->option_b, 'C' => $question->option_c, 'D' => $question->option_d] as $key => $option)
                                    @if($option)
                                        <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 transition hover:border-sky-300 hover:bg-sky-50">
                                            <input type="radio" name="answers[{{ $question->id }}]" value="{{ $key }}" {{ ($answers[$question->id] ?? '') === $key ? 'checked' : '' }} class="h-4 w-4 text-sky-600 focus:ring-sky-500">
                                            <span class="text-sm font-bold text-slate-700">{{ $key }}. {{ $option }}</span>
                                        </label>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="flex justify-end rounded-3xl border border-emerald-200 bg-emerald-50 p-4">
                <div class="w-full">
                    <div id="cbt-unanswered-notice" class="mb-3 hidden rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-800"></div>
                    <div class="flex justify-end"><button id="cbt-review-submit" type="button" class="w-full rounded-2xl bg-emerald-600 px-6 py-3.5 text-sm font-black text-white shadow-lg shadow-emerald-200 hover:bg-emerald-700 md:w-auto">Kirim Jawaban CBT</button></div>
                </div>
            </div>
        </form>

        <div id="cbt-modal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="m5 13 4 4L19 7"/></svg>
                </div>
                <h3 class="mt-4 text-xl font-black text-slate-950">Kirim jawaban sekarang?</h3>
                <p class="mt-2 text-sm font-semibold leading-relaxed text-slate-600"><span id="cbt-answered-count">0</span> dari {{ $questions->count() }} soal sudah dijawab. Setelah dikirim, jawaban tidak dapat diubah.</p>
                <div class="mt-5 grid grid-cols-2 gap-2">
                    <button id="cbt-modal-cancel" type="button" class="rounded-2xl bg-slate-100 px-4 py-3 text-sm font-black text-slate-700">Periksa Lagi</button>
                    <button id="cbt-modal-confirm" type="button" class="rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-black text-white">Ya, Kirim</button>
                </div>
            </div>
        </div>

        <div id="cbt-locking-overlay" class="fixed inset-0 z-[110] hidden items-center justify-center bg-slate-950/85 p-5 text-center text-white backdrop-blur-sm">
            <div class="max-w-sm rounded-3xl border border-white/20 bg-slate-900 p-6 shadow-2xl">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                </div>
                <h3 class="mt-4 text-xl font-black">Sesi CBT dikunci</h3>
                <p class="mt-2 text-sm font-semibold leading-relaxed text-slate-200">Halaman ujian ditinggalkan. Hubungi panitia untuk membuka akses kembali.</p>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('cbt-answer-form');
            const cards = [...document.querySelectorAll('[data-cbt-question]')];
            const modal = document.getElementById('cbt-modal');
            const answeredCount = document.getElementById('cbt-answered-count');
            const unansweredNotice = document.getElementById('cbt-unanswered-notice');
            const countdown = document.getElementById('cbt-countdown');
            const expiresAt = {{ ($session->expires_at?->timestamp ?? now()->timestamp) * 1000 }};
            const serverTimeAtLoad = {{ now()->timestamp * 1000 }};
            const browserPerformanceAtLoad = performance.now();
            let submitted = false;
            let pageReady = false;
            let lockRequested = false;
            let pageIsUnloading = false;
            let wakeLock = null;
            const networkNotice = document.getElementById('cbt-network-notice');
            const lockingOverlay = document.getElementById('cbt-locking-overlay');
            const answerStorageKey = 'cbt-answers-{{ $session->id }}';
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            let savedLocally = {};
            try { savedLocally = JSON.parse(localStorage.getItem(answerStorageKey) || '{}'); } catch (_) {}

            const storeLocally = (questionId, answer) => {
                savedLocally[questionId] = answer;
                localStorage.setItem(answerStorageKey, JSON.stringify(savedLocally));
            };

            const saveAnswer = (questionId, answer) => fetch('{{ route('peserta.cbt.answers.save') }}', {
                method: 'POST',
                keepalive: true,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ question_id: questionId, answer })
            }).catch(() => null);

            cards.forEach(card => {
                const questionId = card.dataset.cbtQuestion;
                const storedAnswer = savedLocally[questionId];
                if (storedAnswer && !card.querySelector('input[type="radio"]:checked')) {
                    const storedInput = card.querySelector(`input[value="${storedAnswer}"]`);
                    if (storedInput) storedInput.checked = true;
                }
                card.querySelectorAll('input[type="radio"]').forEach(input => input.addEventListener('change', () => {
                    storeLocally(questionId, input.value);
                    saveAnswer(questionId, input.value);
                }));
            });

            Object.entries(savedLocally).forEach(([questionId, answer]) => saveAnswer(questionId, answer));

            const keepScreenAwake = async () => {
                if (!('wakeLock' in navigator) || submitted || lockRequested) return;
                try { wakeLock = await navigator.wakeLock.request('screen'); } catch (_) {}
            };

            const lockSession = () => {
                if (submitted || lockRequested) return;
                lockRequested = true;
                sessionStorage.setItem('cbt-lock-requested', '1');
                lockingOverlay.classList.remove('hidden');
                lockingOverlay.classList.add('flex');
                form.querySelectorAll('input, button').forEach(element => { element.disabled = true; });
                const data = new FormData();
                data.append('_token', csrfToken);
                const queued = navigator.sendBeacon('{{ route('peserta.cbt.lock') }}', data);
                if (!queued) {
                    fetch('{{ route('peserta.cbt.lock') }}', {
                        method: 'POST',
                        keepalive: true,
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    }).catch(() => {});
                }
            };

            history.pushState(null, '', location.href);
            window.addEventListener('popstate', () => history.pushState(null, '', location.href));
            window.addEventListener('pagehide', () => { pageIsUnloading = true; });
            window.addEventListener('blur', () => {
                if (pageReady && !pageIsUnloading) lockSession();
            });
            setInterval(() => {
                if (pageReady && !pageIsUnloading && !document.hasFocus()) lockSession();
            }, 500);
            document.addEventListener('visibilitychange', () => {
                if (!pageReady || submitted) return;
                if (document.hidden && !pageIsUnloading) {
                    lockSession();
                } else if (!document.hidden && (lockRequested || sessionStorage.getItem('cbt-lock-requested') === '1')) {
                    setTimeout(() => location.reload(), 800);
                } else if (!document.hidden) {
                    keepScreenAwake();
                }
            });

            window.addEventListener('offline', () => networkNotice?.classList.remove('hidden'));
            window.addEventListener('online', () => networkNotice?.classList.add('hidden'));
            if (!navigator.onLine) networkNotice?.classList.remove('hidden');
            keepScreenAwake();
            setTimeout(() => { pageReady = true; }, 1500);

            const firstUnanswered = cards.find(card => !card.querySelector('input[type="radio"]:checked'));
            if (firstUnanswered && cards.some(card => card.querySelector('input[type="radio"]:checked'))) {
                setTimeout(() => firstUnanswered.scrollIntoView({ behavior: 'smooth', block: 'center' }), 250);
            }

            document.getElementById('cbt-review-submit')?.addEventListener('click', () => {
                const unanswered = cards.filter(card => !card.querySelector('input[type="radio"]:checked'));
                cards.forEach(card => card.classList.remove('border-rose-400', 'ring-4', 'ring-rose-100'));
                if (unanswered.length) {
                    unanswered.forEach(card => card.classList.add('border-rose-400', 'ring-4', 'ring-rose-100'));
                    unansweredNotice.textContent = `${unanswered.length} soal belum dijawab. Lengkapi semua jawaban sebelum mengirim.`;
                    unansweredNotice.classList.remove('hidden');
                    unanswered[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return;
                }
                unansweredNotice.classList.add('hidden');
                answeredCount.textContent = cards.length;
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            });
            document.getElementById('cbt-modal-cancel')?.addEventListener('click', () => {
                modal.classList.add('hidden'); modal.classList.remove('flex');
            });
            document.getElementById('cbt-modal-confirm')?.addEventListener('click', () => {
                if (submitted) return;
                submitted = true;
                form.submit();
            });

            const tick = () => {
                const synchronizedServerTime = serverTimeAtLoad + (performance.now() - browserPerformanceAtLoad);
                const remaining = Math.max(0, Math.floor((expiresAt - synchronizedServerTime) / 1000));
                const minutes = String(Math.floor(remaining / 60)).padStart(2, '0');
                const seconds = String(remaining % 60).padStart(2, '0');
                countdown.textContent = `${minutes}:${seconds}`;
                if (remaining <= 0 && !submitted) {
                    submitted = true;
                    document.getElementById('cbt-timed-out').value = '1';
                    form.submit();
                }
            };
            tick();
            setInterval(tick, 1000);
        });
        </script>
    @endif
</div>
@endsection
