@extends(request()->routeIs('admin.*') ? 'layouts.admin' : 'layouts.panitia')

@section('title', 'Verifikasi Dokumen')
@section('page_title', 'Verifikasi: ' . ($pendaftar->biodata?->full_name ?? 'Pendaftar'))

@section('content')
<div class="max-w-4xl space-y-6">

    {{-- Back --}}
    <a href="{{ route((request()->routeIs('admin.*') ? 'admin.' : 'panitia.') . 'verifikasi.index') }}" class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-slate-700 font-medium">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7"/></svg>
        Kembali ke Daftar Verifikasi
    </a>

    {{-- Header --}}
    <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl p-6 text-white shadow-xl">
        <h2 class="text-xl font-bold">{{ $pendaftar->biodata?->full_name ?? '-' }}</h2>
        <p class="text-amber-100 text-sm mt-1">No. Pendaftaran: <strong>{{ $pendaftar->registration_number ?? '-' }}</strong></p>
        @php
            $totalDocs = $pendaftar->dokumenPendaftars->count();
            $approvedDocs = $pendaftar->dokumenPendaftars->where('status', 'approved')->count();
        @endphp
        <div class="mt-4 flex items-center gap-3">
            <div class="flex-1 h-2 bg-white/20 rounded-full overflow-hidden">
                <div class="h-full bg-white rounded-full transition-all" style="width: {{ $totalDocs > 0 ? ($approvedDocs / $totalDocs) * 100 : 0 }}%"></div>
            </div>
            <span class="text-sm font-bold">{{ $approvedDocs }}/{{ $totalDocs }} disetujui</span>
        </div>
    </div>

    {{-- Document Cards --}}
    @forelse($pendaftar->dokumenPendaftars as $doc)
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <div class="flex items-center gap-3">
                @php
                    $docIcon = ['pending' => 'text-amber-500', 'approved' => 'text-emerald-500', 'rejected' => 'text-rose-500'];
                    $docBg = ['pending' => 'bg-amber-50', 'approved' => 'bg-emerald-50', 'rejected' => 'bg-rose-50'];
                    $docColors = ['pending' => 'bg-amber-100 text-amber-700', 'approved' => 'bg-emerald-100 text-emerald-700', 'rejected' => 'bg-rose-100 text-rose-700'];
                @endphp
                <div class="w-10 h-10 rounded-xl {{ $docBg[$doc->status] ?? 'bg-slate-50' }} flex items-center justify-center">
                    @if($doc->status === 'approved')
                        <svg class="w-5 h-5 {{ $docIcon[$doc->status] }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
                    @elseif($doc->status === 'rejected')
                        <svg class="w-5 h-5 {{ $docIcon[$doc->status] }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                    @else
                        <svg class="w-5 h-5 {{ $docIcon[$doc->status] }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    @endif
                </div>
                <div>
                    <h4 class="font-bold text-slate-800 text-sm">{{ $doc->jenisDokumen?->name ?? 'Dokumen' }}</h4>
                    <p class="text-xs text-slate-400">Diunggah: {{ $doc->created_at?->format('d M Y H:i') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold {{ $docColors[$doc->status] ?? '' }}">{{ ucfirst($doc->status) }}</span>
                @if($doc->file_path)
                    <a href="{{ route((request()->routeIs('admin.*') ? 'admin.' : 'panitia.') . 'dokumen.show', $doc) }}" target="_blank" class="text-xs font-bold text-sky-600 hover:underline">Lihat File </a>
                @endif
            </div>
        </div>

        {{-- Verification Form --}}
        <div class="px-6 py-4">
            @if($doc->notes)
                <div class="mb-3 text-xs text-slate-500 bg-slate-50 rounded-lg p-3">
                    <strong>Catatan sebelumnya:</strong> {{ $doc->notes }}
                </div>
            @endif

            <form method="POST" action="{{ route((request()->routeIs('admin.*') ? 'admin.' : 'panitia.') . 'verifikasi.verify', $doc->id) }}" class="flex flex-wrap items-end gap-3">
                @csrf
                @method('PUT')
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Catatan (Opsional)</label>
                    <input type="text" name="notes" placeholder="Alasan approve/reject..."
                           class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500">
                </div>
                <button type="submit" name="status" value="approved"
                        class="inline-flex items-center gap-1 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-lg transition-colors shadow-sm">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
                    Approve
                </button>
                <button type="submit" name="status" value="rejected"
                        class="inline-flex items-center gap-1 px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-lg transition-colors shadow-sm">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                    Reject
                </button>
            </form>
        </div>
    </div>
    @empty
    <div class="bg-white border border-slate-200 rounded-2xl p-8 text-center shadow-sm">
        <p class="text-slate-500 font-medium">Pendaftar ini belum mengunggah dokumen apapun.</p>
    </div>
    @endforelse

</div>
@endsection
