@extends('layouts.admin')

@section('title', 'Arsip Periode')
@section('page_title', 'Arsip Periode')

@section('content')
<div class="mx-auto max-w-[1180px] space-y-5 pb-8">
    <section class="overflow-hidden rounded-[22px] border border-slate-200 bg-white shadow-[0_12px_32px_rgba(18,50,96,.08)]">
        <div class="flex flex-col gap-5 border-b border-slate-100 px-6 py-6 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-blue-700 text-sm font-black text-white">⌁</span>
                    <p class="text-[11px] font-black uppercase tracking-[.16em] text-blue-700">Administrasi sistem</p>
                </div>
                <h2 class="mt-3 text-[28px] font-black tracking-tight text-slate-950">Arsip periode</h2>
                <p class="mt-1 text-sm font-medium text-slate-500">Kunci periode yang sudah selesai. Data tetap dapat dibuka dan diekspor.</p>
            </div>
            <div class="rounded-2xl border border-blue-100 bg-blue-50 px-5 py-3.5 lg:min-w-[245px]">
                <p class="text-[10px] font-black uppercase tracking-[.14em] text-blue-600">Periode aktif</p>
                <p class="mt-1 text-lg font-black text-blue-950">{{ $activeYear?->name ?? 'Belum ada periode aktif' }}</p>
                @if($activeYear)
                    <p class="mt-1 text-xs font-semibold text-blue-700">{{ $activeYear->start_date?->format('d M Y') }} — {{ $activeYear->end_date?->format('d M Y') }}</p>
                @endif
            </div>
        </div>
        <div class="grid divide-y divide-slate-100 sm:grid-cols-3 sm:divide-x sm:divide-y-0">
            <div class="px-6 py-4">
                <p class="text-[10px] font-black uppercase tracking-[.14em] text-slate-400">Periode tercatat</p>
                <p class="mt-1 text-2xl font-black text-slate-900">{{ $summary['periods'] }}</p>
            </div>
            <div class="px-6 py-4">
                <p class="text-[10px] font-black uppercase tracking-[.14em] text-slate-400">Sudah diarsipkan</p>
                <p class="mt-1 text-2xl font-black text-slate-900">{{ $summary['archived'] }}</p>
            </div>
            <div class="px-6 py-4">
                <p class="text-[10px] font-black uppercase tracking-[.14em] text-slate-400">Pendaftar periode aktif</p>
                <p class="mt-1 text-2xl font-black text-slate-900">{{ number_format($summary['active_applicants']) }}</p>
            </div>
        </div>
    </section>

    <section data-no-auto-tools class="overflow-hidden rounded-[22px] border border-slate-200 bg-white shadow-[0_12px_32px_rgba(18,50,96,.07)]">
        <div class="flex flex-col gap-2 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-black text-slate-950">Daftar tahun ajaran</h3>
                <p class="mt-1 text-sm text-slate-500">Periode aktif baru dapat diarsipkan setelah periode lain diaktifkan.</p>
            </div>
            <span class="w-fit rounded-full bg-slate-100 px-3 py-1.5 text-[11px] font-bold text-slate-600">{{ $years->total() }} periode</span>
        </div>

        <div class="flex flex-col gap-3 border-b border-slate-100 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
            <form method="GET" class="w-full sm:max-w-md">
                <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}">
                <x-list-search name="search" :value="request('search')" placeholder="Cari tahun ajaran" />
            </form>
            <x-per-page-pagination :paginator="$years" static />
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[800px] text-left">
                <thead class="border-b border-slate-100 bg-slate-50/80 text-[10px] font-black uppercase tracking-[.12em] text-slate-500">
                    <tr>
                        <th class="px-6 py-3.5">Tahun ajaran</th>
                        <th class="px-5 py-3.5">Periode</th>
                        <th class="px-5 py-3.5 text-center">Pendaftar</th>
                        <th class="px-5 py-3.5">Status</th>
                        <th class="px-6 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($years as $year)
                        <tr class="transition hover:bg-slate-50/70">
                            <td class="px-6 py-5">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl {{ $year->is_active ? 'bg-emerald-100 text-emerald-700' : ($year->is_archived ? 'bg-slate-100 text-slate-500' : 'bg-amber-100 text-amber-700') }} text-base font-black">{{ $year->is_archived ? '⌁' : '◷' }}</span>
                                    <div>
                                        <p class="font-black text-slate-950">{{ $year->name }}</p>
                                        @if($year->is_archived && $year->archived_at)
                                            <p class="mt-0.5 text-[11px] font-medium text-slate-400">Diarsipkan {{ $year->archived_at->format('d M Y') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-5 text-sm font-medium text-slate-600">{{ $year->start_date?->format('d M Y') ?? '-' }} <span class="px-1 text-slate-300">—</span> {{ $year->end_date?->format('d M Y') ?? '-' }}</td>
                            <td class="px-5 py-5 text-center text-base font-black text-slate-900">{{ number_format($year->pendaftars_count) }}</td>
                            <td class="px-5 py-5">
                                @if($year->is_active)
                                    <span class="rounded-full bg-emerald-100 px-3 py-1.5 text-[11px] font-black text-emerald-700">Aktif</span>
                                @elseif($year->is_archived)
                                    <span class="rounded-full bg-slate-200 px-3 py-1.5 text-[11px] font-black text-slate-600">Diarsipkan</span>
                                @else
                                    <span class="rounded-full bg-amber-100 px-3 py-1.5 text-[11px] font-black text-amber-800">Siap diarsipkan</span>
                                @endif
                            </td>
                            <td class="px-6 py-5">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.pendaftar.index', ['academic_year_id' => $year->id]) }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-700 transition hover:border-blue-200 hover:text-blue-800">Data</a>
                                    <a href="{{ route('admin.pendaftar.export', ['academic_year_id' => $year->id]) }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-700 transition hover:border-blue-200 hover:text-blue-800">Excel</a>
                                    @if(!$year->is_active && !$year->is_archived)
                                        <form method="POST" action="{{ route('admin.arsip-periode.archive', $year) }}">
                                            @csrf
                                            <button class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-black text-white transition hover:bg-blue-800">Arsipkan</button>
                                        </form>
                                    @elseif($year->is_archived)
                                        <form method="POST" action="{{ route('admin.arsip-periode.restore', $year) }}">
                                            @csrf
                                            <button class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-black text-amber-800 transition hover:bg-amber-100">Buka</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-16 text-center text-sm font-semibold text-slate-500">Belum ada periode yang dibuat.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($years->hasPages())
            <div class="border-t border-slate-100 px-6 py-4">
                <div class="spmb-pagination-links">{{ $years->links() }}</div>
            </div>
        @endif
    </section>
</div>
@endsection
