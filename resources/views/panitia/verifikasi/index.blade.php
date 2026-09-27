@extends(request()->routeIs('admin.*') ? 'layouts.admin' : 'layouts.panitia')

@section('title', 'Verifikasi Berkas')
@section('page_title', 'Verifikasi Berkas Pendaftar')

@section('content')
{{-- Table --}}
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="p-6 border-b border-slate-100 flex items-center justify-between">
        <h3 class="font-bold text-slate-900">Pendaftar yang Perlu Diverifikasi</h3>
        <span class="text-xs font-medium text-slate-500 bg-slate-100 px-2.5 py-1 rounded-full">{{ $pendaftars->total() }} pendaftar</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="bg-slate-50 text-slate-400 font-bold uppercase text-[10px] tracking-wider border-b border-slate-100">
                    <th class="p-4 pl-6">Nama Pendaftar</th>
                    <th class="p-4">No. Pendaftaran</th>
                    <th class="p-4">Status Pendaftaran</th>
                    <th class="p-4">Dokumen</th>
                    <th class="p-4 pr-6 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                @forelse($pendaftars as $p)
                @php
                    $totalDocs = $p->dokumenPendaftars->count();
                    $approvedDocs = $p->dokumenPendaftars->where('status', 'approved')->count();
                    $pendingDocs = $p->dokumenPendaftars->where('status', 'pending')->count();
                    $rejectedDocs = $p->dokumenPendaftars->where('status', 'rejected')->count();
                @endphp
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <td class="p-4 pl-6 font-semibold text-slate-900">{{ $p->biodata?->full_name ?? '-' }}</td>
                    <td class="p-4 font-mono text-xs text-emerald-600 font-bold">{{ $p->registration_number ?? '-' }}</td>
                    <td class="p-4">
                        @php
                            $statusColors = [
                                'submitted' => 'bg-amber-100 text-amber-700',
                                'verified' => 'bg-emerald-100 text-emerald-700',
                            ];
                            $statusLabels = [
                                'submitted' => 'Pending',
                                'verified' => 'Approved',
                            ];
                        @endphp
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $statusColors[$p->registration_status] ?? 'bg-slate-100 text-slate-600' }}">
                            {{ $statusLabels[$p->registration_status] ?? ucfirst($p->registration_status) }}
                        </span>
                    </td>
                    <td class="p-4">
                        <div class="flex items-center gap-2">
                            @if($totalDocs > 0)
                                <div class="w-24 h-2 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-emerald-500 rounded-full" style="width: {{ ($approvedDocs / $totalDocs) * 100 }}%"></div>
                                </div>
                                <span class="text-xs text-slate-500">{{ $approvedDocs }}/{{ $totalDocs }}</span>
                                @if($pendingDocs > 0)
                                    <span class="text-[10px] font-bold text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded">{{ $pendingDocs }} pending</span>
                                @endif
                            @else
                                <span class="text-xs text-slate-400">Belum ada</span>
                            @endif
                        </div>
                    </td>
                    <td class="p-4 pr-6 text-right">
                        <a href="{{ route((request()->routeIs('admin.*') ? 'admin.' : 'panitia.') . 'verifikasi.show', $p->id) }}" class="inline-flex items-center gap-1 text-xs font-bold text-emerald-600 hover:underline">
                            Verifikasi
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="p-8 text-center text-slate-500 font-medium">Tidak ada pendaftar yang perlu diverifikasi.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($pendaftars->hasPages())
    <div class="p-4 border-t border-slate-100">
        {{ $pendaftars->links() }}
    </div>
    @endif
</div>
@endsection
