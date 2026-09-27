@extends(auth()->user()?->hasRole('kepala_sekolah') ? 'layouts.kepala-sekolah' : (request()->routeIs('admin.*') ? 'layouts.admin' : (request()->routeIs('bendahara.*') ? 'layouts.bendahara' : 'layouts.panitia')))

@section('title', 'Daftar Hadir Tes SPMB')
@section('page_title', 'Daftar Hadir Tes SPMB')
@section('page_description', 'Peserta dikelompokkan berdasarkan tanggal tes yang mereka pilih.')

@section('content')
@php
    $isHeadmaster = auth()->user()?->hasRole('kepala_sekolah');
    $statusLabels = [
        'submitted' => ['Menunggu verifikasi', 'bg-amber-50 text-amber-700 ring-amber-200'],
        'verified' => ['Terverifikasi', 'bg-sky-50 text-sky-700 ring-sky-200'],
        'accepted' => ['Diterima', 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
        're_registered' => ['Daftar ulang', 'bg-teal-50 text-teal-700 ring-teal-200'],
    ];
    $detailRoute = request()->routeIs('bendahara.*') ? 'bendahara.pendaftar.show' : 'panitia.pendaftar.show';
@endphp

<div class="test-attendance-page space-y-5">
    <section class="rounded-3xl border {{ $isHeadmaster ? 'border-teal-200 bg-gradient-to-br from-teal-800 via-teal-700 to-cyan-700 shadow-teal-900/10' : 'border-violet-100 bg-gradient-to-br from-violet-700 via-violet-600 to-fuchsia-600 shadow-violet-900/10' }} p-6 text-white shadow-xl sm:p-8">
        <p class="text-xs font-black uppercase tracking-[.18em] text-white/80">Kehadiran tes</p>
        <div class="mt-2 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-2xl font-black tracking-tight sm:text-3xl">Daftar peserta per sesi tes</h2>
                <p class="mt-2 max-w-2xl text-sm font-semibold leading-6 text-white/85">Tanggal tes dikelola admin. Peserta masuk ke daftar ini setelah memilih salah satu sesi saat mengirim formulir.</p>
            </div>
            <div class="rounded-2xl bg-white/15 px-5 py-3 text-center backdrop-blur-sm">
                <p class="text-2xl font-black">{{ $participants->flatten(1)->count() }}</p>
                <p class="text-xs font-bold text-white/85">peserta memilih sesi</p>
            </div>
        </div>
    </section>

    <form method="GET" class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-end">
        <div class="min-w-0 flex-1">
            <label for="jadwal" class="text-sm font-extrabold text-slate-700">Tampilkan sesi</label>
            <select id="jadwal" name="jadwal" class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold text-slate-800 {{ $isHeadmaster ? 'focus:border-teal-600 focus:ring-teal-600' : 'focus:border-violet-500 focus:ring-violet-500' }}">
                <option value="">Semua tanggal tes</option>
                @foreach($schedules as $schedule)
                    <option value="{{ $schedule->id }}" @selected($selectedScheduleId === $schedule->id)>
                        {{ \Carbon\Carbon::parse($schedule->tanggal_mulai)->translatedFormat('l, d F Y · H:i') }} — {{ $schedule->kegiatan }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <button class="rounded-xl {{ $isHeadmaster ? 'bg-teal-700 shadow-teal-700/20 hover:bg-teal-800' : 'bg-violet-700 shadow-violet-700/20 hover:bg-violet-800' }} px-5 py-3 text-sm font-black text-white shadow-lg transition">Tampilkan</button>
            @if($selectedScheduleId)
                <a href="{{ url()->current() }}" class="rounded-xl border border-slate-200 px-5 py-3 text-sm font-black text-slate-600 hover:bg-slate-50">Reset</a>
            @endif
        </div>
    </form>

    @forelse($schedules->when($selectedScheduleId, fn ($items) => $items->where('id', $selectedScheduleId)) as $schedule)
        @php $roster = $participants->get($schedule->id, collect()); @endphp
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <header class="flex flex-col gap-4 border-b border-slate-100 bg-slate-50 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div>
                    <p class="text-xs font-black uppercase tracking-[.15em] {{ $isHeadmaster ? 'text-teal-800' : 'text-violet-600' }}">{{ $schedule->kegiatan }}</p>
                    <h3 class="mt-1 text-lg font-black text-slate-900">{{ \Carbon\Carbon::parse($schedule->tanggal_mulai)->translatedFormat('l, d F Y') }}</h3>
                    <p class="mt-1 text-sm font-semibold text-slate-500">
                        {{ \Carbon\Carbon::parse($schedule->tanggal_mulai)->format('H:i') }}
                        @if($schedule->tanggal_selesai) · {{ \Carbon\Carbon::parse($schedule->tanggal_selesai)->format('H:i') }} @endif
                        @if($schedule->keterangan) · {{ $schedule->keterangan }} @endif
                    </p>
                </div>
                <span class="inline-flex w-fit items-center rounded-full {{ $isHeadmaster ? 'bg-teal-100 text-teal-900' : 'bg-violet-100 text-violet-700' }} px-4 py-2 text-sm font-black">{{ $roster->count() }} peserta</span>
            </header>

            @if($roster->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-left">
                        <thead class="bg-white text-xs font-black uppercase tracking-wide text-slate-400">
                            <tr><th class="px-5 py-4">Peserta</th><th class="px-5 py-4">Sekolah asal</th><th class="px-5 py-4">Pilihan jurusan</th><th class="px-5 py-4">Status</th><th class="px-5 py-4 text-right">Aksi</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            @foreach($roster as $pendaftar)
                                @php $status = $statusLabels[$pendaftar->registration_status] ?? [ucfirst(str_replace('_', ' ', $pendaftar->registration_status)), 'bg-slate-100 text-slate-600 ring-slate-200']; @endphp
                                <tr class="{{ $isHeadmaster ? 'hover:bg-teal-50/60' : 'hover:bg-violet-50/40' }}">
                                    <td class="px-5 py-4"><p class="font-black text-slate-900">{{ $pendaftar->biodata?->full_name ?? 'Nama belum diisi' }}</p><p class="mt-1 text-xs font-bold text-slate-500">{{ $pendaftar->registration_number ?? 'Nomor belum terbit' }}</p></td>
                                    <td class="px-5 py-4 font-semibold text-slate-600">{{ $pendaftar->sekolahAsal?->school_name ?? 'Belum diisi' }}</td>
                                    <td class="px-5 py-4 font-semibold text-slate-600">{{ $pendaftar->jurusan1?->name ?? 'Belum dipilih' }}</td>
                                    <td class="px-5 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-black ring-1 {{ $status[1] }}">{{ $status[0] }}</span></td>
                                    <td class="px-5 py-4 text-right"><a class="inline-flex rounded-xl border {{ $isHeadmaster ? 'border-teal-200 text-teal-800 hover:bg-teal-50' : 'border-violet-200 text-violet-700 hover:bg-violet-50' }} px-3 py-2 text-xs font-black" href="{{ route($detailRoute, $pendaftar) }}">Lihat data</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="px-6 py-10 text-center"><p class="font-black text-slate-700">Belum ada peserta memilih sesi ini.</p><p class="mt-1 text-sm font-semibold text-slate-500">Daftar akan terisi setelah peserta mengirim formulir dan memilih tanggal tes.</p></div>
            @endif
        </section>
    @empty
        <section class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center"><h3 class="font-black text-slate-800">Belum ada jadwal tes yang dibuka untuk pilihan siswa.</h3><p class="mt-2 text-sm font-semibold text-slate-500">Admin perlu menandai jadwal Tes SPMB sebagai pilihan tanggal siswa terlebih dahulu.</p></section>
    @endforelse
</div>
@endsection
