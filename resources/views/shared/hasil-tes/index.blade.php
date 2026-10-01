@extends($layout)

@section('title', $isParticipant ? 'Tes SPMB' : 'Hasil Tes')
@section('page_title', $isParticipant ? 'Tes SPMB' : 'Hasil Tes')
@section('page_description', $isParticipant ? 'Persiapan, CBT, dan pengumuman hasil tes.' : 'Rekap pelaksanaan dan hasil tes peserta.')

@section('content')
@if($isParticipant)
    @php
        $testDate = $pendaftar?->preferredTestSchedule?->tanggal_mulai
            ? \Illuminate\Support\Carbon::parse($pendaftar->preferredTestSchedule->tanggal_mulai)
            : null;
    @endphp
    <div class="mx-auto max-w-[900px] space-y-4">
        <section class="overflow-hidden rounded-3xl border border-teal-200 bg-gradient-to-br from-teal-700 to-emerald-600 p-6 text-white shadow-xl shadow-teal-900/10">
            <p class="text-xs font-black uppercase tracking-[.16em] text-teal-100">Tahap Tes SPMB</p>
            <h2 class="mt-2 text-2xl font-black tracking-tight">{{ $testDate ? 'Persiapan Tes SPMB' : 'Jadwal Tes Belum Tersedia' }}</h2>
            @if($testDate)
                <p class="mt-2 text-sm font-semibold leading-relaxed text-teal-50">{{ $testDate->translatedFormat('l, d F Y') }} · {{ $testDate->format('H:i') }} WIB<br>Kampus E SMK Muhammadiyah 4 Cileungsi</p>
            @else
                <p class="mt-2 text-sm font-semibold text-teal-50">Tanggal tes akan tampil setelah formulir dan pilihan jadwalmu diproses.</p>
            @endif
        </section>

        @if(!$pendaftar?->preferredTestSchedule)
            <section class="rounded-3xl border border-amber-200 bg-amber-50 p-6"><h3 class="text-lg font-black text-amber-950">Menunggu jadwal tes</h3><p class="mt-2 text-sm font-semibold leading-relaxed text-amber-800">Pilih tanggal Tes SPMB saat mengirim formulir. Setelah itu jadwal akan muncul di halaman ini.</p></section>
        @elseif($activeCbtSession)
            <section class="rounded-3xl border border-emerald-200 bg-emerald-50 p-6 shadow-sm">
                <p class="text-xs font-black uppercase tracking-[.14em] text-emerald-700">Tahap berikutnya</p><h3 class="mt-2 text-xl font-black text-emerald-950">CBT sudah dibuka</h3>
                <p class="mt-2 text-sm font-semibold leading-relaxed text-emerald-800">Panitia sudah membuka ujian untuk akunmu. Setelah mulai, halaman ujian mengunci navigasi dan jawaban akan dikirim saat ujian selesai.</p>
                <a href="{{ route('peserta.cbt') }}" class="mt-5 inline-flex w-full items-center justify-center rounded-2xl bg-emerald-700 px-5 py-3.5 text-sm font-black text-white shadow-lg shadow-emerald-700/20 sm:w-auto">Mulai Tes CBT <span class="ml-2 text-lg">→</span></a>
            </section>
        @elseif($completedCbt && !$mayViewResult)
            <section class="rounded-3xl border border-sky-200 bg-sky-50 p-6 shadow-sm"><p class="text-xs font-black uppercase tracking-[.14em] text-sky-700">CBT selesai</p><h3 class="mt-2 text-xl font-black text-sky-950">Hasil sedang diproses</h3><p class="mt-2 text-sm font-semibold leading-relaxed text-sky-800">{{ $announcementAt && now()->greaterThanOrEqualTo($announcementAt) ? 'Panitia sedang menetapkan hasil seleksi. Keputusan diterima atau belum diterima akan tampil setelah ditetapkan.' : 'Hasil Tes SPMB akan diumumkan pada '.($announcementAt?->translatedFormat('l, d F Y') ?? 'waktu yang ditentukan sekolah').'.' }}</p></section>
        @elseif($mayViewResult)
            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4"><p class="text-xs font-black uppercase tracking-[.14em] text-teal-700">Hasil Tes SPMB</p><h3 class="mt-1 text-xl font-black text-slate-950">Hasil untuk {{ $pendaftar->biodata?->full_name ?? 'peserta' }}</h3><span class="mt-3 inline-flex rounded-full px-3 py-1.5 text-xs font-black {{ in_array($pendaftar->registration_status, ['accepted', 're_registered'], true) ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">{{ in_array($pendaftar->registration_status, ['accepted', 're_registered'], true) ? 'Diterima' : 'Belum diterima' }}</span></div>
                <div class="divide-y divide-slate-100">
                    @forelse($results as $result)
                        <article class="flex items-center justify-between gap-4 px-5 py-4"><div><h4 class="text-sm font-black text-slate-900">{{ $result->tes?->test_name ?? 'Tes SPMB' }}</h4><p class="mt-1 text-xs font-semibold text-slate-500">{{ $result->tes?->test_date ? \Illuminate\Support\Carbon::parse($result->tes->test_date)->translatedFormat('d F Y') : 'Sudah diproses' }}</p></div><span class="rounded-full bg-teal-50 px-3 py-1.5 text-xs font-black text-teal-700">{{ filled($result->score) ? 'Nilai ' . $result->score : 'Selesai' }}</span></article>
                    @empty
                        <p class="p-8 text-center text-sm font-semibold text-slate-500">Hasil Tes SPMB belum tersedia.</p>
                    @endforelse
                </div>
            </section>
        @else
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-black uppercase tracking-[.14em] text-teal-700">Sebelum Tes SPMB</p><h3 class="mt-2 text-xl font-black text-slate-950">Siapkan diri untuk tes</h3>
                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    @foreach($testPreparation as $item)
                        @php $iconKey = \App\Support\TestPreparationIcons::resolve($item['icon'] ?? null, $loop->index); @endphp
                        <article class="group rounded-2xl border border-slate-100 bg-gradient-to-br from-white to-teal-50/60 p-4 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-teal-200 hover:shadow-md">
                            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-teal-700 text-white shadow-lg shadow-teal-700/20">
                                <x-test-preparation-icon :icon="$iconKey" />
                            </span>
                            <h4 class="mt-4 text-sm font-black text-slate-900">{{ $item['title'] ?? '' }}</h4><p class="mt-1 text-xs font-semibold leading-relaxed text-slate-500">{{ $item['body'] ?? '' }}</p>
                        </article>
                    @endforeach
                </div>
                <p class="mt-5 rounded-2xl border border-teal-100 bg-teal-50 px-4 py-3 text-sm font-semibold leading-relaxed text-teal-800">Tombol Tes CBT akan muncul di sini ketika panitia membuka akses ujian untuk akunmu.</p>
            </section>
        @endif
    </div>
@else
    <div class="test-report mx-auto max-w-[1280px] space-y-4">
        <section class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm lg:flex-row lg:items-center lg:justify-between">
            <div><p class="interview-session-kicker">Rekap operasional</p><h2 class="text-lg font-black text-slate-950">Laporan hasil tes</h2><p class="mt-1 text-xs font-semibold text-slate-500">{{ $canViewNotes ? 'Nilai dan catatan hasil seluruh peserta.' : 'Status pelaksanaan tes untuk kebutuhan administrasi.' }}</p></div>
            <form method="GET" class="flex w-full flex-wrap gap-2 lg:w-auto lg:flex-nowrap"><x-list-search placeholder="Cari peserta atau nomor" class="min-w-0 flex-1 lg:w-56" /><select name="test_id" class="h-10 min-w-[136px] flex-1 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold lg:w-44 lg:flex-none"><option value="">Semua tes</option>@foreach($tests as $test)<option value="{{ $test->id }}" @selected($testId === $test->id)>{{ $test->test_name }}</option>@endforeach</select><button class="h-10 shrink-0 rounded-xl bg-slate-900 px-4 text-xs font-black text-white">Terapkan</button></form>
        </section>
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3"><h3 class="text-base font-black text-slate-950">Peserta dengan hasil tes</h3><span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-black text-slate-600">{{ $applicants->total() }} peserta</span></div>
            <div class="overflow-x-auto"><table class="w-full min-w-[720px] text-left text-sm"><thead class="bg-slate-50 text-[10px] font-black uppercase text-slate-500"><tr><th class="px-5 py-3">Peserta</th><th class="px-4 py-3">No. Pendaftaran</th><th class="px-4 py-3">Jurusan</th><th class="px-4 py-3">Tes tercatat</th><th class="px-5 py-3 text-right">Aksi</th></tr></thead><tbody class="divide-y divide-slate-100">
                @forelse($applicants as $applicant)
                    @php $participantResults = $resultsByApplicant->get($applicant->id, collect()); @endphp
                    <tr><td class="px-5 py-3 font-bold text-slate-900">{{ $applicant->biodata?->full_name ?? 'Belum isi nama' }}</td><td class="px-4 py-3 text-xs font-bold text-sky-700">{{ $applicant->registration_number ?? '-' }}</td><td class="px-4 py-3 text-xs font-semibold text-slate-600">{{ $applicant->jurusan1?->name ?? '-' }}</td><td class="px-4 py-3"><span class="rounded-full bg-sky-50 px-2.5 py-1 text-[11px] font-black text-sky-700">{{ $participantResults->count() }} tes</span></td><td class="px-5 py-3 text-right"><a href="{{ route($detailRoute, $applicant) }}" class="inline-flex rounded-xl bg-slate-900 px-3 py-2 text-xs font-black text-white">Lihat detail</a></td></tr>
                @empty
                    <tr><td colspan="5" class="p-8 text-center text-sm font-semibold text-slate-400">Belum ada hasil tes pada filter ini.</td></tr>
                @endforelse
            </tbody></table></div>
            <x-per-page-pagination :paginator="$applicants" />
        </section>
    </div>
@endif
@endsection

