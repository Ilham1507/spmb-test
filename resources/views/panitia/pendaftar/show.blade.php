@extends(auth()->user()?->hasRole('kepala_sekolah') ? 'layouts.kepala-sekolah' : (request()->routeIs('admin.*') ? 'layouts.admin' : (request()->routeIs('bendahara.*') ? 'layouts.bendahara' : 'layouts.panitia')))

@section('title', 'Detail Pendaftar')
@section('page_title', 'Detail: ' . ($pendaftar->biodata?->full_name ?? 'Pendaftar'))

@section('content')
@php
    $routePrefix = request()->routeIs('admin.*') ? 'admin.' : (request()->routeIs('bendahara.*') ? 'bendahara.' : (request()->routeIs('kepala-sekolah.*') ? 'kepala-sekolah.' : 'panitia.'));
    $isHeadmaster = auth()->user()?->hasRole('kepala_sekolah');
@endphp
<div class="max-w-4xl space-y-6">

    {{-- Back --}}
    <a href="{{ route($routePrefix . 'pendaftar.index') }}" class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-slate-700 font-medium">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7"/></svg>
        Kembali ke Daftar
    </a>

    {{-- Header Card --}}
    <div class="bg-gradient-to-br from-emerald-600 to-teal-700 rounded-2xl p-6 text-white shadow-xl flex justify-between items-start">
        <div>
            <h2 class="text-2xl font-bold">{{ $pendaftar->biodata?->full_name ?? '-' }}</h2>
            <p class="text-emerald-100 text-sm mt-1">No. Pendaftaran: <strong>{{ $pendaftar->registration_number ?? 'Belum ada' }}</strong></p>
        </div>
        @php
            $statusColors = [
                'draft' => 'bg-white/20 text-white',
                'submitted' => 'bg-amber-400 text-amber-900',
                'verified' => 'bg-emerald-400 text-emerald-900',
                'accepted' => 'bg-emerald-400 text-emerald-900',
                'rejected' => 'bg-rose-400 text-rose-900',
            ];
            $statusLabels = [
                'draft' => 'Draft',
                'submitted' => 'Pending',
                'verified' => 'Approved',
                'accepted' => 'Diterima',
                'rejected' => 'Ditolak',
                're_registered' => 'Daftar Ulang',
            ];
            if ($isHeadmaster) {
                $statusColors = array_merge($statusColors, [
                    'verified' => 'bg-teal-100 text-teal-900',
                    'accepted' => 'bg-teal-100 text-teal-900',
                ]);
            }
        @endphp
        <span class="px-3 py-1.5 rounded-full text-xs font-bold {{ $statusColors[$pendaftar->registration_status] ?? 'bg-white/20 text-white' }}">
            {{ $statusLabels[$pendaftar->registration_status] ?? ucfirst(str_replace('_', ' ', $pendaftar->registration_status)) }}
        </span>
    </div>
    <div class="flex flex-wrap gap-2">
        @if(in_array($routePrefix, ['admin.', 'bendahara.', 'kepala-sekolah.', 'panitia.'], true))
            <a href="{{ route($routePrefix . 'pendaftaran_bantuan.edit', $pendaftar) }}" class="inline-flex items-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-black text-white hover:bg-emerald-700">
                Isi / edit formulir
            </a>
        @endif
        <a href="{{ route($routePrefix . 'pendaftar.cetak', $pendaftar) }}" class="inline-flex items-center rounded-xl {{ $isHeadmaster ? 'bg-teal-700 hover:bg-teal-800' : 'bg-sky-600 hover:bg-sky-700' }} px-4 py-2 text-sm font-black text-white">
            Cetak Formulir
        </a>
        <a data-no-loading download href="{{ route($routePrefix . 'pendaftar.pdf', $pendaftar) }}" class="inline-flex items-center rounded-xl border {{ $isHeadmaster ? 'border-teal-300 text-teal-800 hover:bg-teal-50' : 'border-sky-200 text-sky-700 hover:bg-sky-50' }} bg-white px-4 py-2 text-sm font-black">
            Simpan sebagai PDF
        </a>
    </div>

    <section class="rounded-2xl border {{ $isHeadmaster ? 'border-teal-200 bg-teal-50' : 'border-sky-200 bg-sky-50' }} p-5 shadow-sm">
        <p class="text-xs font-black uppercase tracking-[.14em] {{ $isHeadmaster ? 'text-teal-800' : 'text-sky-700' }}">Pilihan kehadiran tes siswa</p>
        @if($pendaftar->preferredTestSchedule)
            @php $chosenTest = \Illuminate\Support\Carbon::parse($pendaftar->preferredTestSchedule->tanggal_mulai); @endphp
            <h3 class="mt-2 text-lg font-black text-slate-900">{{ $chosenTest->translatedFormat('d F Y, H:i') }} WIB</h3>
            <p class="mt-1 text-sm font-semibold text-slate-600">{{ $pendaftar->preferredTestSchedule->kegiatan }}{{ $pendaftar->preferredTestSchedule->lokasi ? ' · '.$pendaftar->preferredTestSchedule->lokasi : '' }}</p>
            <p class="mt-2 text-xs font-semibold {{ $isHeadmaster ? 'text-teal-800' : 'text-sky-700' }}">Dipilih siswa {{ $pendaftar->preferred_test_selected_at?->translatedFormat('d M Y H:i') ?? '' }}</p>
        @else
            <p class="mt-2 text-sm font-semibold text-slate-600">Siswa belum memilih tanggal tes.</p>
        @endif
    </section>

    @php
        $totalDocsForVerify = $pendaftar->dokumenPendaftars->count();
        $activeStatuses = \App\Support\FormFieldCatalog::activeStatuses($pendaftar, ['Dokumen Pendukung']);
        $formChecks = ['Pembayaran Formulir' => $registrationFeePaid];
        $formCheckDetails = [];
        foreach (\App\Support\FormFieldCatalog::groups() as $group => $fields) {
            if ($group === 'Dokumen Pendukung') continue;
            $activeInGroup = collect($fields)->filter(fn ($label, $key) => \App\Support\FormFieldCatalog::isEnabled($key));
            if ($activeInGroup->isNotEmpty()) {
                $filledCount = $activeInGroup->filter(fn ($label) => ($activeStatuses[$label] ?? false) === true)->count();
                $formCheckDetails[$group] = [$filledCount, $activeInGroup->count()];
                $formChecks[$group] = $group === 'Wali'
                    ? ($filledCount === 0 || $filledCount === $activeInGroup->count())
                    : $filledCount === $activeInGroup->count();
            }
        }
        if (\App\Support\FormFieldCatalog::isEnabled('foto_3x4') || \App\Support\FormFieldCatalog::isEnabled('skl_skhu_ijazah')) {
            $formChecks['Dokumen Pendukung'] = $totalDocsForVerify > 0;
        }
        $canVerifyForm = ! $isHeadmaster && $pendaftar->registration_status !== 'draft' && $registrationFeePaid;
        $readyForOverallVerification = $canVerifyForm && collect($formChecks)->every(fn ($isComplete) => $isComplete === true);
    @endphp

    <div class="rounded-2xl border {{ $isHeadmaster ? 'border-teal-200 bg-teal-50' : ($readyForOverallVerification ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50') }} p-5 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h3 class="text-lg font-black text-slate-900">Verifikasi Formulir Keseluruhan</h3>
                @if($pendaftar->registration_status === 'draft')
                    <div class="mt-3 rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-black text-slate-700">
                        Belum bisa diperiksa: formulir masih Draft dan belum dikirim oleh siswa.
                    </div>
                @elseif(!$registrationFeePaid)
                    <div class="mt-3 rounded-2xl border border-rose-200 bg-rose-100 px-4 py-3 text-sm font-black text-rose-800">
                        Belum bisa disetujui: pembayaran formulir belum lunas.
                    </div>
                @endif
                @if($pendaftar->correction_status === 'resubmitted')
                    <div class="mt-3 rounded-2xl border border-sky-200 bg-sky-100 px-4 py-3 text-sm font-black text-sky-800">
                        Sudah diperbaiki siswa {{ $pendaftar->correction_submitted_at?->diffForHumans() }}. Periksa kembali bagian pada catatan sebelumnya.
                    </div>
                @elseif($pendaftar->correction_status === 'requested')
                    <div class="mt-3 rounded-2xl border border-orange-200 bg-orange-100 px-4 py-3 text-sm font-black text-orange-800">
                        Menunggu siswa menyelesaikan perbaikan.
                    </div>
                @endif
                <p class="mt-1 text-sm leading-relaxed {{ $isHeadmaster ? 'text-teal-900' : ($readyForOverallVerification ? 'text-emerald-800' : 'text-amber-800') }}">
                    Periksa setiap bagian di bawah. Persetujuan hanya dilakukan setelah semua bagian berstatus lengkap.
                </p>
                @if($pendaftar->verification_notes)
                    <div class="mt-3 rounded-2xl border border-amber-200 bg-white px-4 py-3 text-sm font-semibold text-amber-800">
                        Catatan perbaikan: {{ $pendaftar->verification_notes }}
                    </div>
                @endif
                <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2">
                    @foreach($formChecks as $label => $done)
                        <span class="flex items-center justify-between rounded-xl border border-white/80 bg-white px-3 py-2 text-xs font-bold {{ $isHeadmaster ? 'text-teal-900' : ($done ? 'text-emerald-700' : 'text-amber-700') }}">
                            <span>{{ $label }} @if(isset($formCheckDetails[$label]))<small class="ml-1 font-semibold text-slate-500">({{ $formCheckDetails[$label][0] }}/{{ $formCheckDetails[$label][1] }} terisi)</small>@endif</span>
                            <span class="ml-2 rounded-full px-2 py-0.5 text-[10px] font-black {{ $isHeadmaster ? 'bg-teal-100 text-teal-900' : ($done ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800') }}">
                                @if($label === 'Pembayaran Formulir'){{ $done ? 'Lunas' : 'Belum lunas' }}@elseif($label === 'Wali' && (($formCheckDetails[$label][0] ?? 0) === 0))Opsional @else{{ $done ? 'Lengkap' : 'Belum lengkap' }}@endif
                            </span>
                        </span>
                    @endforeach
                </div>
            </div>
            @if($canVerifyForm)
            <form method="POST" action="{{ route($routePrefix . 'pendaftar.verify', $pendaftar) }}" class="w-full shrink-0 space-y-3 lg:w-[360px]" data-overall-verification-form>
                @csrf
                @method('PUT')
                <div>
                    <label for="verification_notes" class="mb-1.5 block text-xs font-black uppercase tracking-wide text-slate-500">
                        Catatan Pending
                    </label>
                    <textarea id="verification_notes" name="notes" rows="3" placeholder="Wajib diisi jika memilih Pendingkan"
                           class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-100">{{ old('notes', $pendaftar->verification_notes) }}</textarea>
                    <p class="mt-1 text-[11px] font-semibold text-slate-500">Catatan ini akan muncul di dashboard siswa.</p>
                    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <button type="submit" name="status" value="verified" data-verification-action="verified"
                            class="rounded-2xl bg-emerald-600 px-4 py-3 text-xs font-black text-white shadow-lg shadow-emerald-200 transition hover:bg-emerald-700">
                        Setujui Formulir
                    </button>
                    <button type="submit" name="status" value="submitted" data-verification-action="submitted"
                            class="rounded-2xl bg-amber-500 px-4 py-3 text-xs font-black text-white shadow-lg shadow-amber-200 transition hover:bg-amber-600">
                        Pendingkan
                    </button>
                </div>
            </form>
            @else
                <div class="w-full shrink-0 rounded-2xl border border-slate-200 bg-white p-4 text-center lg:w-[360px]">
                    <p class="font-black text-slate-700">{{ $isHeadmaster ? 'Mode pemantauan' : 'Approval belum tersedia' }}</p>
                    <p class="mt-1 text-xs leading-relaxed text-slate-500">{{ $isHeadmaster ? 'Kepala Sekolah dapat melihat kelengkapan dan status pendaftar tanpa mengubah data.' : 'Tunggu siswa membayar dan mengirim formulir terlebih dahulu.' }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Semua data mengikuti field aktif di Kelola Formulir --}}
    @php
        $detailValue = function (string $key) use ($pendaftar) {
            if ($key === 'jurusan') return $pendaftar->jurusan1?->name;
            if ($key === 'jenis_kelamin') return ['L' => 'Laki-laki', 'P' => 'Perempuan'][$pendaftar->biodata?->gender] ?? null;
            if ($key === 'jalur_pendaftaran') return $pendaftar->jalurPendaftaran?->name;
            return \App\Support\FormFieldCatalog::valueFor($pendaftar, $key);
        };
    @endphp
    @foreach(\App\Support\FormFieldCatalog::groups() as $group => $fields)
        @php $visibleFields = collect($fields)->filter(fn ($label, $key) => \App\Support\FormFieldCatalog::isEnabled($key)); @endphp
        @if($visibleFields->isNotEmpty() && $group !== 'Dokumen Pendukung')
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-4"><h3 class="font-bold text-slate-800">{{ $group }}</h3></div>
                <div class="grid grid-cols-1 gap-x-8 gap-y-3 px-6 py-4 text-sm sm:grid-cols-2">
                    @foreach($visibleFields as $key => $label)
                        <div class="min-w-0"><span class="text-xs text-slate-400">{{ $label }}{{ \App\Support\FormFieldCatalog::isRequired($key) ? ' *' : '' }}</span><p class="break-words font-medium text-slate-800">{{ $detailValue($key) ?: '-' }}</p></div>
                    @endforeach
                </div>
            </section>
        @endif
    @endforeach

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-6 py-4"><h3 class="font-bold text-slate-800">Riwayat Tes SPMB</h3><p class="mt-1 text-xs text-slate-500">Tes yang telah dicatat untuk peserta ini.</p></div>
        <div class="space-y-3 px-6 py-4">
            @forelse($pendaftar->pesertaTes as $test)
                <div class="flex flex-col gap-2 rounded-2xl bg-slate-50 p-4 sm:flex-row sm:items-center sm:justify-between"><div><p class="font-black text-slate-800">{{ $test->tes?->test_name ?? 'Tes SPMB' }}</p><p class="mt-1 text-xs text-slate-500">Kehadiran: {{ $test->attendance ? 'Hadir' : 'Belum hadir' }}{{ $test->notes ? ' · '.$test->notes : '' }}</p></div><span class="rounded-full bg-emerald-50 px-3 py-1.5 text-sm font-black text-emerald-700">Nilai {{ $test->score ?? '-' }}</span></div>
            @empty
                <p class="text-sm text-slate-500">Belum ada hasil tes nilai.</p>
            @endforelse
            @if($pendaftar->uniformMeasurement)<div class="rounded-2xl bg-slate-50 p-4"><p class="font-black text-slate-800">Ukuran seragam</p><p class="mt-1 text-xs text-slate-500">{{ $pendaftar->uniformMeasurement->size?->name ?? '-' }}{{ $pendaftar->uniformMeasurement->notes ? ' · '.$pendaftar->uniformMeasurement->notes : '' }}</p></div>@endif
            @if($pendaftar->healthChecks->isNotEmpty())<div class="rounded-2xl bg-slate-50 p-4"><p class="font-black text-slate-800">Pemeriksaan kesehatan</p>@foreach($pendaftar->healthChecks as $check)<p class="mt-1 text-xs text-slate-500">{{ $check->item?->name ?? 'Pemeriksaan' }}: {{ $check->result_value ?? '-' }} · {{ $check->result_status ?? '-' }}{{ $check->notes ? ' · '.$check->notes : '' }}</p>@endforeach</div>@endif
        </div>
    </section>

    {{-- Dokumen --}}
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        @php
            $totalDocs = $pendaftar->dokumenPendaftars->count();
        @endphp
        <div class="px-6 py-4 border-b border-slate-100">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h3 class="font-bold text-slate-800">Dokumen Pendaftar</h3>
                    <p class="mt-1 text-xs text-slate-500">Buka file untuk dicek. Status dokumen mengikuti keputusan Setujui Formulir di atas.</p>
                </div>
                @if($totalDocs > 0)
                    <span class="rounded-full {{ $isHeadmaster ? 'bg-teal-50 text-teal-800' : 'bg-sky-50 text-sky-700' }} px-3 py-1.5 text-xs font-black">{{ $totalDocs }} dokumen terunggah</span>
                @endif
            </div>
        </div>
        <div class="px-6 py-4 space-y-4">
            @forelse($pendaftar->dokumenPendaftars as $doc)
                <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-start gap-3">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl {{ $isHeadmaster ? 'bg-teal-50 text-teal-700' : 'bg-sky-50 text-sky-500' }}">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6M8 4h6l4 4v12H8a2 2 0 01-2-2V6a2 2 0 012-2z"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-black text-slate-900">{{ $doc->jenisDokumen?->name ?? 'Dokumen' }}</p>
                                <p class="mt-0.5 text-xs text-slate-400">Diunggah: {{ $doc->created_at?->format('d M Y H:i') }}</p>
                                @if($doc->notes)
                                    <p class="mt-2 rounded-xl bg-white px-3 py-2 text-xs font-semibold text-slate-500">Catatan: {{ $doc->notes }}</p>
                                @endif
                            </div>
                        </div>
                        @if($doc->file_path)
                            <a href="{{ route($routePrefix . 'dokumen.show', $doc) }}" target="_blank" class="inline-flex shrink-0 justify-center rounded-full {{ $isHeadmaster ? 'bg-teal-50 text-teal-800 hover:bg-teal-100' : 'bg-sky-50 text-sky-700 hover:bg-sky-100' }} px-4 py-2 text-xs font-black">Lihat File</a>
                        @endif
                    </div>
                </div>
            @empty
            <p class="text-sm text-slate-500">Belum ada dokumen.</p>
            @endforelse
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('[data-overall-verification-form]').forEach((form) => {
        const notes = form.querySelector('textarea[name="notes"]');

        form.querySelectorAll('[data-verification-action]').forEach((button) => {
            button.addEventListener('click', () => {
                if (!notes) {
                    return;
                }

                const isPending = button.dataset.verificationAction === 'submitted';
                notes.required = isPending;
                notes.setAttribute('aria-required', isPending ? 'true' : 'false');
            });
        });
    });
</script>
@endsection
