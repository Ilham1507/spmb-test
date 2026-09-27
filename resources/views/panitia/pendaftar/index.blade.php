@extends(auth()->user()?->hasRole('kepala_sekolah') ? 'layouts.kepala-sekolah' : (request()->routeIs('admin.*') ? 'layouts.admin' : (request()->routeIs('bendahara.*') ? 'layouts.bendahara' : 'layouts.panitia')))

@section('title', 'Data Pendaftar')
@section('page_title', 'Data Pendaftar')

@section('content')
@php
    $routePrefix = request()->routeIs('admin.*') ? 'admin.' : (request()->routeIs('bendahara.*') ? 'bendahara.' : (request()->routeIs('kepala-sekolah.*') ? 'kepala-sekolah.' : 'panitia.'));
    $isHeadmaster = auth()->user()?->hasRole('kepala_sekolah');
@endphp
{{-- Filter & Search --}}
<div class="applicant-directory mx-auto max-w-[1280px]">
<div class="applicant-filter mb-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <form method="GET" action="{{ route($routePrefix . 'pendaftar.index') }}" class="grid w-full gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_220px_220px_auto] lg:items-center" @form-select-changed.window="$nextTick(() => $el.requestSubmit())">
        <x-list-search placeholder="Cari nama pendaftar" @input.debounce.600ms="$el.form.requestSubmit()" class="min-w-0 sm:col-span-2 lg:col-span-1" />
        <x-form-select name="status" :options="['draft' => 'Draft', 'submitted' => 'Pending', 'verified' => 'Approved', 'accepted' => 'Diterima', 'rejected' => 'Ditolak']" :value="request('status')" placeholder="Semua Status" />
        <x-form-select name="correction" :options="['requested' => 'Perlu Perbaikan', 'resubmitted' => 'Sudah Diperbaiki']" :value="request('correction')" placeholder="Semua Revisi" />
        <div class="flex flex-wrap items-center gap-2 sm:col-span-2 lg:col-span-1">
            @if(request('search') || request('status') || request('correction'))
                <a href="{{ route($routePrefix . 'pendaftar.index') }}" class="applicant-reset inline-flex items-center justify-center rounded-xl bg-slate-100 px-4 py-3 text-xs font-black text-slate-600 hover:bg-slate-200">Reset</a>
            @endif
            <a href="{{ route($routePrefix . 'pendaftar.export', request()->query()) }}" class="inline-flex w-full items-center justify-center rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-xs font-black text-blue-800 hover:bg-blue-100 sm:w-auto">Ekspor Excel</a>
            @if(in_array($routePrefix, ['admin.', 'bendahara.', 'kepala-sekolah.', 'panitia.'], true))
                <a href="{{ route($routePrefix . 'pendaftaran_bantuan.create') }}" class="applicant-create inline-flex w-full items-center justify-center rounded-xl bg-emerald-600 px-4 py-3 text-xs font-black text-white hover:bg-emerald-700 sm:w-auto">+ Peserta bantu</a>
            @endif
        </div>
    </form>
</div>

{{-- Table --}}
<div class="space-y-3 md:hidden">
    @forelse($pendaftars as $p)
        @php
            $statusColors = ['draft' => 'bg-slate-100 text-slate-600', 'submitted' => 'bg-amber-100 text-amber-700', 'verified' => 'bg-emerald-100 text-emerald-700', 'accepted' => 'bg-emerald-100 text-emerald-700', 'rejected' => 'bg-rose-100 text-rose-700', 're_registered' => 'bg-indigo-100 text-indigo-700'];
            $statusLabels = ['draft' => 'Draft', 'submitted' => 'Pending', 'verified' => 'Approved', 'accepted' => 'Diterima', 'rejected' => 'Ditolak', 're_registered' => 'Daftar Ulang'];
            $label = $p->correction_status === 'resubmitted' ? 'Sudah Diperbaiki' : ($p->correction_status === 'requested' ? 'Perlu Perbaikan' : ($statusLabels[$p->registration_status] ?? ucfirst(str_replace('_', ' ', $p->registration_status))));
            $color = $p->correction_status === 'resubmitted' ? 'bg-sky-100 text-sky-700' : ($p->correction_status === 'requested' ? 'bg-orange-100 text-orange-700' : ($statusColors[$p->registration_status] ?? 'bg-slate-100 text-slate-600'));
        @endphp
        <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0"><p class="text-xs font-black {{ $p->registration_number ? 'text-emerald-700' : 'text-slate-400' }}">{{ $p->registration_number ?? 'Nomor belum dibuat' }}</p><h3 class="mt-1 truncate text-base font-black text-slate-900">{{ $p->biodata?->full_name ?? '-' }}</h3></div>
                <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-bold {{ $color }}">{{ $label }}</span>
            </div>
            <dl class="mt-4 grid grid-cols-2 gap-3 border-t border-slate-100 pt-3 text-sm"><div><dt class="text-xs text-slate-400">Jurusan</dt><dd class="mt-1 font-semibold text-slate-700">{{ $p->jurusan1?->name ?? '-' }}</dd></div><div><dt class="text-xs text-slate-400">Tanggal daftar</dt><dd class="mt-1 font-semibold text-slate-700">{{ $p->created_at?->format('d M Y') ?? '-' }}</dd></div></dl>
            <a href="{{ route($routePrefix . 'pendaftar.show', $p->id) }}" class="mt-4 inline-flex w-full items-center justify-center rounded-xl bg-sky-50 px-4 py-2.5 text-sm font-bold text-sky-800">Lihat detail</a>
        </article>
    @empty
        <p class="rounded-2xl bg-white p-6 text-center text-sm text-slate-500">Tidak ada data pendaftar.</p>
    @endforelse
    <div class="rounded-xl bg-white"><x-per-page-pagination :paginator="$pendaftars" /></div>
</div>

<div class="applicant-table hidden overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm md:block">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="bg-slate-50 text-slate-400 font-bold uppercase text-[10px] tracking-wider border-b border-slate-100">
                    <th class="p-4 pl-6">No. Pendaftaran</th>
                    <th class="p-4">Nama Lengkap</th>
                    <th class="p-4">Jurusan Pilihan 1</th>
                    <th class="p-4">Status</th>
                    <th class="p-4">Tanggal Daftar</th>
                    <th class="p-4 pr-6 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                @forelse($pendaftars as $p)
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <td class="p-4 pl-6 font-mono text-xs font-bold {{ $p->registration_number ? 'text-emerald-600' : 'text-slate-400' }}">
                        {{ $p->registration_number ?? 'Belum dibuat' }}
                    </td>
                    <td class="p-4 font-semibold text-slate-900">{{ $p->biodata?->full_name ?? '-' }}</td>
                    <td class="p-4">{{ $p->jurusan1?->name ?? '-' }}</td>
                    <td class="p-4">
                        @php
                            $statusColors = [
                                'draft' => 'bg-slate-100 text-slate-600',
                                'submitted' => 'bg-amber-100 text-amber-700',
                                'verified' => 'bg-emerald-100 text-emerald-700',
                                'accepted' => 'bg-emerald-100 text-emerald-700',
                                'rejected' => 'bg-rose-100 text-rose-700',
                                're_registered' => 'bg-indigo-100 text-indigo-700',
                            ];
                            $statusLabels = [
                                'draft' => 'Draft',
                                'submitted' => 'Pending',
                                'verified' => 'Approved',
                                'accepted' => 'Diterima',
                                'rejected' => 'Ditolak',
                                're_registered' => 'Daftar Ulang',
                            ];
                        @endphp
                        @if($p->correction_status === 'resubmitted')
                            <span class="rounded-full bg-sky-100 px-2.5 py-1 text-xs font-bold text-sky-700">Sudah Diperbaiki</span>
                        @elseif($p->correction_status === 'requested')
                            <span class="rounded-full bg-orange-100 px-2.5 py-1 text-xs font-bold text-orange-700">Perlu Perbaikan</span>
                        @else
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $statusColors[$p->registration_status] ?? 'bg-slate-100 text-slate-600' }}">
                                {{ $statusLabels[$p->registration_status] ?? ucfirst(str_replace('_', ' ', $p->registration_status)) }}
                            </span>
                        @endif
                    </td>
                    <td class="p-4 text-slate-500 text-xs">{{ $p->created_at?->format('d M Y H:i') ?? '-' }}</td>
                    <td class="p-4 pr-6 text-right">
                        <a href="{{ route($routePrefix . 'pendaftar.show', $p->id) }}" class="applicant-detail-button">Lihat detail <span>→</span></a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="p-8 text-center text-slate-500 font-medium">Tidak ada data pendaftar.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <x-per-page-pagination :paginator="$pendaftars" />
</div>
</div>
@endsection
