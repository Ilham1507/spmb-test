@extends(auth()->user()?->hasRole('kepala_sekolah') ? 'layouts.kepala-sekolah' : (request()->routeIs('admin.*') ? 'layouts.admin' : (request()->routeIs('bendahara.*') ? 'layouts.bendahara' : 'layouts.panitia')))

@section('title', $activeTest ?? 'Tes SPMB')
@section('page_title', $activeTest ?? 'Tes SPMB')

@section('content')
@php
    $routePrefix = request()->routeIs('admin.*') ? 'admin.' : (request()->routeIs('bendahara.*') ? 'bendahara.' : 'panitia.');
    $isHeadmaster = auth()->user()?->hasRole('kepala_sekolah');
    $readOnlyDetailRoute = $isHeadmaster ? 'panitia.pendaftar.show' : 'bendahara.hasil-tes.show';
    $activeRoute = Route::currentRouteName();
    $healthStatusOptions = [
        '' => 'Pilih status',
        'normal' => 'Normal',
        'perlu_dicatat' => 'Perlu dicatat',
        'perlu_tindak_lanjut' => 'Perlu tindak lanjut',
    ];
    $answerOptions = ['' => 'Belum ditentukan', 'A' => 'A', 'B' => 'B', 'C' => 'C', 'D' => 'D'];
    $durationOptions = ['30' => '30 menit', '45' => '45 menit', '60' => '60 menit', '90' => '90 menit', '120' => '120 menit'];
    $resultFor = function (string $testName) use ($tests, $testResults) {
        $test = $tests->get($testName);
        return $test ? $testResults->get($test->id) : null;
    };
    $simpleResult = in_array($activeTest, ['Baca Tulis Quran', 'Tes CBT', 'Wawancara Orang Tua'], true)
        ? $resultFor($activeTest)
        : null;
    $cbtOpen = $cbtSession?->is_open;
    $testKey = match($activeTest) {
        'Baca Tulis Quran' => 'btq',
        'Tes Ukuran Seragam' => 'uniform',
        'Tes Kesehatan' => 'health',
        'Wawancara Orang Tua' => 'interview',
        default => null,
    };
@endphp

<div class="test-workspace mx-auto max-w-[1180px] space-y-3">
    <div class="block">
    @unless($selectedApplicant)
        <aside class="test-queue-panel overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="test-queue-header flex flex-col gap-2 border-b border-slate-100 px-4 py-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-3">
                    <div><h3 class="text-base font-black text-slate-950">Peserta</h3></div>
                    <span class="test-count-pending rounded-full px-2.5 py-1 text-[11px] font-black">{{ $pendingApplicants->count() }} belum tes</span>
                </div>
                <form method="GET" action="{{ route($activeRoute) }}" class="test-search-form flex w-full gap-2 lg:w-auto">
            <input type="hidden" name="pendaftar" value="{{ $selectedApplicant?->id }}">
                    <label class="sr-only" for="test-search">Cari peserta</label>
                    <x-list-search placeholder="Cari nama atau nomor pendaftaran" class="w-full lg:w-72" />
                    <button class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-black text-white hover:bg-emerald-700">Cari</button>
                </form>
                <x-per-page-pagination :paginator="$applicants" static />
            </div>
            <div class="test-pending-list max-h-[300px] overflow-y-auto divide-y divide-slate-100">
                @forelse($pendingApplicants as $applicant)
                    <a href="{{ $canManageTests ? route($activeRoute, ['pendaftar' => $applicant->id, 'search' => request('search')]) : route($readOnlyDetailRoute, $applicant) }}"
                       class="grid gap-1 px-3 py-2.5 transition hover:bg-emerald-50 sm:grid-cols-[1fr_180px_220px_auto] sm:items-center">
                        <p class="truncate font-black text-slate-900">{{ $applicant->biodata?->full_name ?? 'Belum isi nama' }}</p>
                        <p class="text-xs font-semibold text-emerald-700">{{ $applicant->registration_number ?? 'Belum ada nomor' }}</p>
                        <p class="truncate text-xs font-semibold text-slate-500">{{ $applicant->jurusan1?->name ?? 'Jurusan belum dipilih' }}</p>
                        <span class="text-xs font-black text-emerald-700 sm:text-right">{{ $canManageTests ? 'Input' : 'Lihat' }}</span>
                    </a>
                @empty
                    <div class="p-6 text-center text-sm font-semibold text-slate-400">Belum ada peserta yang siap tes.</div>
                @endforelse
            </div>
        </aside>
    @endunless

        <section class="space-y-5">
            @if($selectedApplicant)
                @if($activeTest === 'Wawancara Orang Tua')
                    <form method="POST" action="{{ route($routePrefix . 'tes.hasil.store', $selectedApplicant) }}" class="interview-session rounded-3xl border border-slate-200 bg-white shadow-sm">
                        @csrf
                        <input type="hidden" name="test_name" value="{{ $activeTest }}">
                        <div class="interview-session-head">
                            <div>
                                <p class="interview-session-kicker">Wawancara orang tua</p>
                                <h3>{{ $selectedApplicant->biodata?->full_name }}</h3>
                                <span>{{ $selectedApplicant->registration_number ?? 'Nomor pendaftaran belum tersedia' }}</span>
                            </div>
                            <a href="{{ route($activeRoute) }}" class="interview-back">Kembali</a>
                        </div>

                        <div class="interview-session-grid">
                            <section class="interview-guide" aria-labelledby="interview-guide-title">
                                <div class="interview-guide-head">
                                    <div>
                                        <p class="interview-session-kicker">Panduan percakapan</p>
                                        <h4 id="interview-guide-title">Pertanyaan untuk orang tua</h4>
                                    </div>
                                    <span>{{ $interviewQuestions->count() }} pertanyaan</span>
                                </div>
                                <ol>
                                    @forelse($interviewQuestions as $question)
                                        <li>{{ $question->question }}</li>
                                    @empty
                                        <li>Belum ada pertanyaan aktif. Hubungi admin untuk menyiapkan panduan wawancara.</li>
                                    @endforelse
                                </ol>
                            </section>

                            <section class="interview-record">
                                <label for="interview-score">Nilai <span>opsional</span></label>
                                <input id="interview-score" type="number" name="score" inputmode="decimal" min="0" max="100" step="0.01" value="{{ old('score', $simpleResult->score ?? '') }}" placeholder="0 - 100">
                                <label for="interview-notes">Ringkasan jawaban</label>
                                <textarea id="interview-notes" name="notes" rows="7" placeholder="Catat jawaban penting, kebutuhan khusus, atau tindak lanjut yang disepakati.">{{ old('notes', $simpleResult->notes ?? '') }}</textarea>
                                <button>Simpan wawancara</button>
                            </section>
                        </div>
                    </form>
                @elseif($activeTest === 'Baca Tulis Quran')
                    <form method="POST" action="{{ route($routePrefix . 'tes.hasil.store', $selectedApplicant) }}" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        @csrf
                        <input type="hidden" name="test_name" value="{{ $activeTest }}">
                        <h3 class="text-lg font-black text-slate-950">{{ $activeTest }} - {{ $selectedApplicant->biodata?->full_name }}</h3>
                        <div class="mt-4">
                            <label class="mb-1.5 block text-xs font-black uppercase tracking-wide text-slate-500">Nilai</label>
                            <input type="number" name="score" inputmode="decimal" min="0" max="100" step="0.01" value="{{ old('score', $simpleResult->score ?? '') }}" placeholder="0 - 100"
                                   class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-100">
                        </div>
                        <div class="mt-3">
                            <label class="mb-1.5 block text-xs font-black uppercase tracking-wide text-slate-500">Catatan</label>
                            <textarea name="notes" rows="4" placeholder="Tulis catatan singkat"
                                      class="w-full resize-none rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-100">{{ old('notes', $simpleResult->notes ?? '') }}</textarea>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-3"><a href="{{ route($activeRoute) }}" class="rounded-2xl bg-slate-100 px-5 py-3 text-sm font-black text-slate-700 hover:bg-slate-200">Kembali</a><button class="rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-emerald-200 hover:bg-emerald-700">Simpan {{ $activeTest }}</button></div>
                    </form>
                @elseif($activeTest === 'Tes Ukuran Seragam')
                    <form method="POST" action="{{ route($routePrefix . 'tes.seragam.store', $selectedApplicant) }}" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        @csrf
                        <h3 class="text-lg font-black text-slate-950">Tes Ukuran Seragam - {{ $selectedApplicant->biodata?->full_name }}</h3>
                        <div class="mt-4">
                            <x-form-select
                                name="uniform_size_id"
                                label="Ukuran Seragam"
                                :options="$uniformSizes->map(fn($size) => ['value' => $size->id, 'label' => $size->name])"
                                :value="$uniformResult->uniform_size_id ?? null"
                                placeholder="Pilih ukuran"
                                required
                            />
                        </div>
                        <div class="mt-3">
                            <label class="mb-1.5 block text-xs font-black uppercase tracking-wide text-slate-500">Catatan</label>
                            <textarea name="notes" rows="3" placeholder="Contoh: celana perlu ukuran khusus"
                                      class="w-full resize-none rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-100">{{ old('notes', $uniformResult->notes ?? '') }}</textarea>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-3"><a href="{{ route($activeRoute) }}" class="rounded-2xl bg-slate-100 px-5 py-3 text-sm font-black text-slate-700 hover:bg-slate-200">Kembali</a><button class="rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-emerald-200 hover:bg-emerald-700">Simpan Ukuran</button></div>
                    </form>
                @elseif($activeTest === 'Tes Kesehatan')
                    <form method="POST" action="{{ route($routePrefix . 'tes.kesehatan.store', $selectedApplicant) }}" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        @csrf
                        <h3 class="text-lg font-black text-slate-950">Tes Kesehatan - {{ $selectedApplicant->biodata?->full_name }}</h3>
                        <div class="mt-4 grid gap-3 lg:grid-cols-3">
                            @foreach($healthItems as $item)
                                @php $health = $healthResults->get($item->id); @endphp
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <h4 class="font-black text-slate-900">{{ $item->name }}</h4>
                                    <div class="mt-3">
                                        <label class="mb-1.5 block text-xs font-black uppercase tracking-wide text-slate-500">Hasil {{ $item->unit ? '(' . $item->unit . ')' : '' }}</label>
                                        @if(strtolower((string) $item->unit) === 'kg')
                                            <input type="number" inputmode="decimal" step="0.1" min="0" name="health[{{ $item->id }}][result_value]" value="{{ old("health.{$item->id}.result_value", $health->result_value ?? '') }}" placeholder="Contoh: 52.5"
                                                   class="w-full rounded-2xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-100">
                                        @elseif(strtolower((string) $item->unit) === 'mmhg')
                                            <input type="text" inputmode="numeric" pattern="[0-9/ ]*" name="health[{{ $item->id }}][result_value]" value="{{ old("health.{$item->id}.result_value", $health->result_value ?? '') }}" placeholder="Contoh: 120/80"
                                                   class="w-full rounded-2xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-100">
                                        @else
                                            <input type="text" name="health[{{ $item->id }}][result_value]" value="{{ old("health.{$item->id}.result_value", $health->result_value ?? '') }}" placeholder="Isi hasil pemeriksaan"
                                                   class="w-full rounded-2xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-100">
                                        @endif
                                    </div>
                                    <div class="mt-3">
                                        <x-form-select name="health[{{ $item->id }}][result_status]" label="Status" :options="$healthStatusOptions" :value="$health->result_status ?? ''" placeholder="Pilih status" />
                                    </div>
                                    <div class="mt-3">
                                        <label class="mb-1.5 block text-xs font-black uppercase tracking-wide text-slate-500">Catatan</label>
                                        <input name="health[{{ $item->id }}][notes]" value="{{ old("health.{$item->id}.notes", $health->notes ?? '') }}" placeholder="Opsional"
                                               class="w-full rounded-2xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-100">
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-4 flex flex-wrap gap-3"><a href="{{ route($activeRoute) }}" class="rounded-2xl bg-slate-100 px-5 py-3 text-sm font-black text-slate-700 hover:bg-slate-200">Kembali</a><button class="rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-emerald-200 hover:bg-emerald-700">Simpan Kesehatan</button></div>
                    </form>
                @elseif($activeTest === 'Tes CBT')
                    <div class="grid gap-5 lg:grid-cols-[1fr_420px]">
                        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                            <h3 class="text-lg font-black text-slate-950">Akses CBT - {{ $selectedApplicant->biodata?->full_name }}</h3>
                            <p class="mt-1 text-sm font-semibold text-slate-500">Buka akses hanya saat peserta hadir di lokasi tes.</p>

                            <div class="mt-4 rounded-2xl border {{ $cbtOpen ? 'border-emerald-200 bg-emerald-50' : ($cbtSession?->status === 'locked' ? 'border-rose-200 bg-rose-50' : 'border-amber-200 bg-amber-50') }} p-4">
                                <p class="text-sm font-black {{ $cbtOpen ? 'text-emerald-800' : ($cbtSession?->status === 'locked' ? 'text-rose-800' : 'text-amber-800') }}">
                                    {{ $cbtOpen ? 'CBT sedang dibuka' : ($cbtSession?->status === 'locked' ? 'CBT terkunci karena peserta meninggalkan halaman' : 'CBT belum dibuka untuk peserta ini') }}
                                </p>
                                <p class="mt-1 text-sm font-semibold text-slate-600">
                                    Jawaban masuk: {{ $cbtAnswerCount }} soal
                                    @if($cbtSession?->expires_at)
                                        <span class="mx-1">-</span> Berlaku sampai {{ $cbtSession->expires_at->format('H:i') }}
                                    @endif
                                </p>
                            </div>

                            @if($cbtOpen)
                                <form method="POST" action="{{ route($routePrefix . 'tes.cbt.close', $selectedApplicant) }}" class="mt-4">
                                    @csrf
                                    <button class="rounded-2xl bg-rose-600 px-5 py-3 text-sm font-black text-white hover:bg-rose-700">Tutup Akses CBT</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route($routePrefix . 'tes.cbt.open', $selectedApplicant) }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                                    @csrf
                                    @if($cbtSession?->status === 'locked')
                                        <input type="hidden" name="duration_minutes" value="10">
                                        <div class="sm:col-span-2 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800">
                                            Akses akan dilanjutkan dengan sisa waktu sesi sebelumnya. Durasi tidak diulang dari awal.
                                        </div>
                                    @else
                                        <x-form-select name="duration_minutes" label="Durasi Akses" :options="$durationOptions" value="60" required />
                                    @endif
                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold text-slate-500">Catatan Lokasi</label>
                                        <input name="location_note" value="{{ old('location_note', 'Dibuka saat peserta hadir di lokasi tes.') }}"
                                               class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3.5 text-sm font-semibold text-slate-700 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-100">
                                    </div>
                                    <button class="sm:col-span-2 rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-emerald-200 hover:bg-emerald-700">{{ $cbtSession?->status === 'locked' ? 'Buka Ulang Akses CBT' : 'Buka Akses CBT' }}</button>
                                </form>
                            @endif
                        </section>

                        <section class="rounded-3xl border border-sky-200 bg-sky-50 p-5 shadow-sm">
                            <h3 class="text-lg font-black text-slate-950">Import Soal CBT</h3>
                            <p class="mt-1 text-sm font-semibold text-slate-600">Download template, isi soal, lalu import file CSV.</p>
                            <a href="{{ route($routePrefix . 'tes.cbt-template') }}" class="mt-4 inline-flex rounded-2xl bg-white px-4 py-3 text-sm font-black text-sky-700 hover:bg-sky-100">Download Template CSV</a>
                            <form method="POST" action="{{ route($routePrefix . 'tes.cbt-import') }}" enctype="multipart/form-data" class="mt-4 space-y-3">
                                @csrf
                                <input type="file" name="file" accept=".csv,text/csv"
                                       class="w-full rounded-2xl border border-sky-200 bg-white px-4 py-3 text-sm font-bold text-slate-700">
                                <button class="w-full rounded-2xl bg-sky-600 px-5 py-3 text-sm font-black text-white hover:bg-sky-700">Import Soal</button>
                            </form>
                        </section>
                    </div>

                    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="text-lg font-black text-slate-950">Bank Soal CBT</h3>
                        <form method="POST" action="{{ route($routePrefix . 'tes.cbt-question.store') }}" class="mt-4 grid gap-3">
                            @csrf
                            <textarea name="question" rows="3" required placeholder="Tulis pertanyaan CBT"
                                      class="w-full resize-none rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-100">{{ old('question') }}</textarea>
                            <div class="grid gap-3 md:grid-cols-4">
                                @foreach(['a', 'b', 'c', 'd'] as $option)
                                    <input name="option_{{ $option }}" required value="{{ old('option_'.$option) }}" placeholder="Pilihan {{ strtoupper($option) }}"
                                           class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-100">
                                @endforeach
                            </div>
                            <div class="grid gap-3 md:grid-cols-[220px_auto] md:items-end">
                                <x-form-select name="correct_answer" label="Jawaban Benar" :options="$answerOptions" value="" placeholder="Pilih jawaban" required />
                                <label class="inline-flex items-center gap-2 text-sm font-bold text-slate-700">
                                    <input type="checkbox" name="status" value="1" checked class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-400">
                                    Soal aktif
                                </label>
                            </div>
                            <button class="w-fit rounded-2xl bg-slate-900 px-5 py-3 text-sm font-black text-white hover:bg-slate-700">Tambah Soal</button>
                        </form>

                        <div class="mt-5 overflow-x-auto">
                            <table class="w-full min-w-[720px] text-left text-sm">
                                <thead class="bg-slate-50 text-[10px] font-black uppercase tracking-wider text-slate-500">
                                    <tr>
                                        <th class="p-4">Pertanyaan</th>
                                        <th class="p-4">Jawaban</th>
                                        <th class="p-4">Status</th>
                                        <th class="p-4 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @forelse($cbtQuestions as $question)
                                        <tr>
                                            <td class="p-4">
                                                <p class="font-bold text-slate-900">{{ $question->question }}</p>
                                                <p class="mt-1 text-xs text-slate-500">A. {{ $question->option_a }} | B. {{ $question->option_b }} | C. {{ $question->option_c }} | D. {{ $question->option_d }}</p>
                                            </td>
                                            <td class="p-4 font-black text-emerald-700">{{ $question->correct_answer ?? '-' }}</td>
                                            <td class="p-4">
                                                <span class="rounded-full px-2.5 py-1 text-xs font-black {{ $question->status ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $question->status ? 'Aktif' : 'Nonaktif' }}</span>
                                            </td>
                                            <td class="p-4 text-right">
                                                <form method="POST" action="{{ route($routePrefix . 'tes.cbt-question.destroy', $question) }}" onsubmit="return confirm('Hapus pertanyaan ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="rounded-full bg-rose-50 px-3 py-1.5 text-xs font-black text-rose-700 hover:bg-rose-100">Hapus</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="p-8 text-center text-sm font-semibold text-slate-400">Belum ada pertanyaan CBT.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4"><x-per-page-pagination :paginator="$cbtQuestions" /></div>
                    </section>
                @endif
            @endif
        </section>
    </div>

    @if($testKey && !$selectedApplicant && $completedApplicants->isNotEmpty())
        <section class="test-completed-panel overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-3 py-2.5">
                <div>
                    <h3 class="text-base font-black text-slate-950">Selesai</h3>
                </div>
                <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-black text-emerald-700">{{ $completedApplicants->count() }} selesai</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm">
                    <thead class="bg-slate-50 text-[10px] font-black uppercase text-slate-500">
                        <tr>
                            <th class="px-3 py-2">Peserta</th>
                            <th class="px-3 py-2">No. Pendaftaran</th>
                            <th class="px-3 py-2">Jurusan</th>
                            <th class="px-3 py-2">{{ $activeTest === 'Tes Kesehatan' ? 'Hasil Pemeriksaan' : 'Hasil' }}</th>
                            <th class="px-3 py-2 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($completedApplicants as $applicant)
                            @php
                                $completedBaseResult = $completedTestResults->get($applicant->id);
                                $healthSummary = $activeTest === 'Tes Kesehatan'
                                    ? $completedHealthResults->get($applicant->id, collect())->map(function ($result) use ($healthItems) {
                                        $item = $healthItems->firstWhere('id', $result->health_check_item_id);
                                        return [
                                            'label' => $item?->name ?? 'Pemeriksaan',
                                            'value' => $result->result_value ?: '-',
                                            'status' => $result->result_status ?: '-',
                                            'notes' => $result->notes,
                                        ];
                                    })->values()
                                    : collect();
                                $uniformResult = $activeTest === 'Tes Ukuran Seragam' ? $completedUniformResults->get($applicant->id) : null;
                            @endphp
                            <tr>
                                <td class="px-3 py-2 font-bold text-slate-900">{{ $applicant->biodata?->full_name ?? 'Belum isi nama' }}</td>
                                <td class="px-3 py-2 font-semibold text-emerald-700">{{ $applicant->registration_number ?? '-' }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $applicant->jurusan1?->name ?? '-' }}</td>
                                <td class="px-3 py-2">
                                    @if($activeTest === 'Tes Kesehatan')
                                        <div class="flex flex-wrap gap-1.5">
                                            @forelse($healthSummary as $item)
                                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-700">
                                                    {{ $item['label'] }}: {{ $item['value'] }} / {{ Str::of($item['status'])->replace('_', ' ')->title() }}
                                                </span>
                                            @empty
                                                <span class="text-xs font-semibold text-slate-400">Belum ada detail.</span>
                                            @endforelse
                                        </div>
                                    @elseif($activeTest === 'Tes Ukuran Seragam')
                                        <span class="text-xs font-semibold text-slate-600">{{ $uniformResult?->notes ?: $completedBaseResult?->notes ?: '-' }}</span>
                                    @else
                                        <span class="text-xs font-semibold text-slate-600">{{ filled($completedBaseResult?->score) ? 'Nilai ' . $completedBaseResult->score : ($completedBaseResult?->notes ?: '-') }}</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route($activeRoute, ['pendaftar' => $applicant->id]) }}" class="rounded-xl bg-sky-100 px-3 py-1.5 text-xs font-black text-sky-700">Edit</a>
                                        <form method="POST" action="{{ route($routePrefix . 'tes.hasil.destroy', ['pendaftar' => $applicant, 'testKey' => $testKey]) }}" onsubmit="return confirm('Hapus hasil tes peserta ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="rounded-xl bg-rose-100 px-3 py-1.5 text-xs font-black text-rose-700">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="p-5 text-center font-semibold text-slate-400">Belum ada peserta yang selesai tes ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</div>
@endsection
